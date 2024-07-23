<?php

namespace Jou\AccessLog\Renderable;

use App\Models\Order;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\LazyRenderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Jou\AccessLog\Helper;
use Jou\AccessLog\Metrics\DateRangeHelper;

class OrderRepeatTable extends LazyRenderable
{

    public function grid(): Grid
    {
        $range =  Cache::get('order_conversion_range','customize');
        $dateRange = DateRangeHelper::getDateRange($range);


        $order = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where('status','>',0);

        $phone2 = Order::where('created_at','<',$dateRange['start'])->where('status','>',0)->pluck('phone');
        if($phone2){
            $phone2 = $phone2->unique();
        }


        $order_repeat = Order::select('phone', \DB::raw('COUNT(*) as count'))->groupBy('phone')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where('status','>',0)->get()->keyBy('phone');


        $new_cus_phone = [];
        $grid = Grid::make($order, function (Grid $grid) use($order_repeat,$phone2,$new_cus_phone) {


            $grid->column('name','收货人')->display(function ()use ($order_repeat,$phone2,&$new_cus_phone){
                $new_cus = '';
                if(!$phone2->contains($this->phone) && !in_array($this->phone,$new_cus_phone)){
                    $new_cus = '<span class="badge" style="background:#f17070">新客</span>';
                    $new_cus_phone[] = $this->phone;
                }

                $repeat_count = $order_repeat->get($this->phone)->count;
                $repeat_html = '<span class="badge" style="background:#43a9cc">'.$repeat_count.'单</span>';


                return $this->name.' '.$this->phone.' '.$repeat_html.' '.$new_cus;
            });
            $grid->column('no','订单号');
            $grid->column('total_price','订单金额')->display(function ($val){
                return round($val);
            });
            $grid->column('user_agent','载具')->display(function ($val){
                return Helper::device($val);
            });
            $grid->column('ip','IP');
            $grid->column('status','状态')->display(function ($val){
                return Arr::get(Order::STATUS_TXT,$val);
            })->badge('success');
            $grid->column('created_at');

            $grid->paginate(10);
            $grid->disableActions();

            $grid->disableRefreshButton();
            $grid->disableRowSelector();


        });

        $cache_key = md5('order_repeat'.$dateRange['start'].$dateRange['end']);
        if(Cache::has($cache_key)){
            $cache_data = Cache::get($cache_key);

            $data = $cache_data['data'];

            $new_customer = $cache_data['new_customer'];

            $order_total_price = isset($cache_data['order_total_price'])?round($cache_data['order_total_price']):0;
            $order_pc_total_price = isset($cache_data['order_pc_total_price'])?round($cache_data['order_pc_total_price']):0;
            $order_m_total_price = isset($cache_data['order_m_total_price'])?round($cache_data['order_m_total_price']):0;

            $html = $this->withContent($data,$new_customer,$order_total_price,$order_pc_total_price,$order_m_total_price);

            $grid->header($html);

        }

        return $grid;
    }

    /**
     * 设置统计内容.
     *
     * @param array $data
     * @param integer $new_customer
     * @param string $order_total_price
     * @return $this
     */
    public function withContent($data,$new_customer,$order_total_price,$order_pc_total_price,$order_m_total_price)
    {

        $count = array_sum($data);
        $rate = 0;
        if($count){
            $rate = round($new_customer / $count *100,2);
        }

        if($order_total_price){
            $order_total_price = round($order_total_price);
        }


        $html = '';
        $new_html = '';
        if($new_customer){
            $new_html = '<div class="tfo"><span>新客占：<b>'.$rate.'%</b></span><span>新客：<b>'.$new_customer.'</b></span><span>人数：<b>'.$count.'</b></span></div>';
            $new_html .= '<div class="tfo"><span>总金额：<b>'.$order_total_price.'</b></span><span>PC：<b>'.$order_pc_total_price.'</b></span><span>M：<b>'.$order_m_total_price.'</b></span></div>';
        }



        foreach ($data as $k=>$v){
            $html .= '<p>'.$k.'單：'.$v.'</p>';
        }
        if(!$html){
            $html = '<div style="width: 100%;color: #999;margin-left: 10px">暂无数据</div>';
        }

        return <<<HTML
<style>
    .repeat{
        display: flex;
        flex-wrap: wrap;
        padding: 0.6rem 1.1rem;
    }
    .repeat p{
        margin-right: 5px;
        border: 1px solid #eee;
        padding: 0.2rem 0.4rem;
        border-radius: 0.2rem;
        margin-bottom: 5px;
        background-color: #eee;
        font-size: 12px;
    }
    .new-cus{
       padding: 0 1.1rem;
    }
    .new-cus .tfo span{
       display: inline-block;
       margin-right: 20px;
       font-weight: 400;
       font-size: 11px;
       color: #999;
    }
    .new-cus .tfo span b{
        font-size: 14px;
        color: #333;
    }
</style>
<div class="new-cus">
$new_html
</div>
<div class="repeat">
$html
</div>
HTML;

    }
}
<?php


namespace Jou\AccessLog\Metrics\Access;



use Dcat\Admin\Widgets\Modal;
use Illuminate\Support\Facades\Cache;
use Jou\AccessLog\AccessLogServiceProvider;
use Jou\AccessLog\Helper;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Metrics\Card;
use Jou\AccessLog\Metrics\DateRangeHelper;
use Jou\AccessLog\Renderable\OrderRepeatTable;


class OrderRepeat extends Card
{
    protected $start = 1;

    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('订单回购统计');


        //$dropdown['today'] = '今日';
        //$dropdown['yesterday'] = '昨日';
        $dropdown['customize'] = '自定义';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';

        $this->height(173);

        $this->dropdown($dropdown);

        $modal = Modal::make()
            ->id('orderrepeat')
            ->lg()
            ->title('详细报表')
            ->body(OrderRepeatTable::make())
            ->button('<span style="position: absolute;right: 4px;top: 55px;font-size: 12px;padding: .5rem 1rem !important;"><i class="feather icon-bar-chart-2"></i> 报表</span>')->xl();
        $this->header($modal);

    }

    /**
     * 处理请求
     *
     * @param Request $request
     *
     * @return mixed|void
     */
    public function handle(Request $request)
    {

        $range = $request->get('option','customize');
        Cache::set('order_repeat_range',$range);
        $dateRange = DateRangeHelper::getDateRange($range);

        $cache_key = md5('order_repeat'.$dateRange['start'].$dateRange['end']);

        if(!Cache::has($cache_key)){
            $order_model = AccessLogServiceProvider::setting('order_model');
            $order_status = AccessLogServiceProvider::setting('order_status','status');
            $orders = app($order_model)->select('phone', \DB::raw('COUNT(*) as count'))->groupBy('phone')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where($order_status,'>',0)->get();
            $phone2 = app($order_model)->where('created_at','<',$dateRange['start'])->where($order_status,'>',0)->pluck('phone');

            $order_ps = app($order_model)->select('total_price','user_agent')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where($order_status,'>',0)->get();
            $order_total_price = 0;
            $order_pc_total_price = 0;
            $order_m_total_price = 0;
            $pc_order_count = 0;
            $m_order_count = 0;

            foreach ($order_ps as $item){
                $order_total_price += $item->total_price;
                $device_type = Helper::device($item->user_agent);
                if(in_array($device_type,['iphone','android','ipad'])){
                    $order_m_total_price += $item->total_price;
                    $m_order_count++;
                }else{
                    $order_pc_total_price += $item->total_price;
                    $pc_order_count++;
                }
            }
            $order_count = $order_ps->count();


            $phone = [];
            $data = [];
            foreach ($orders as $item){
                if(!isset($data[$item->count])){
                    $data[$item->count] = 1;
                }else{
                    $data[$item->count]++;
                }

                $phone[] = $item->phone;
            }

            $new_customer = 0;
            if($phone){
                $phone2->unique();
                $new_customer = collect(array_unique($phone))->diff($phone2)->count();
            }


            ksort($data);


            $average_price = $order_count>0?round($order_total_price/$order_count):0;
            $pc_average_price = $pc_order_count>0?round($order_pc_total_price/$pc_order_count):0;
            $m_average_price = $m_order_count>0?round($order_m_total_price/$m_order_count):0;

            Cache::set($cache_key,[
                'data'=>$data,
                'new_customer'=>$new_customer,
                'order_total_price'=>round($order_total_price),
                'order_m_total_price'=>round($order_m_total_price),
                'order_pc_total_price'=>round($order_pc_total_price),
                'average_price' => $average_price,
                'pc_average_price' => $pc_average_price,
                'm_average_price' => $m_average_price,
            ],1800); //缓存半小时
        }else{
            $cache_data = Cache::get($cache_key);

            $data = $cache_data['data'];

            $new_customer = $cache_data['new_customer'];

            $order_total_price = isset($cache_data['order_total_price'])?round($cache_data['order_total_price']):0;
            $order_pc_total_price = isset($cache_data['order_pc_total_price'])?round($cache_data['order_pc_total_price']):0;
            $order_m_total_price = isset($cache_data['order_m_total_price'])?round($cache_data['order_m_total_price']):0;

            $average_price = isset($cache_data['average_price'])?round($cache_data['average_price']):0;
            $pc_average_price = isset($cache_data['pc_average_price'])?round($cache_data['pc_average_price']):0;
            $m_average_price = isset($cache_data['m_average_price'])?round($cache_data['m_average_price']):0;
        }

        $this->withContent(
            $data,
            $new_customer,
            $order_total_price,
            $order_pc_total_price,
            $order_m_total_price,
            $average_price,
            $pc_average_price,
            $m_average_price
        );

    }



    /**
     * 设置卡片内容
     * @param $data
     * @param $new_customer
     * @param $order_total_price
     * @param $order_pc_total_price
     * @param $order_m_total_price
     * @param $average_price
     * @param $pc_average_price
     * @param $m_average_price
     * @return OrderRepeat
     */
    public function withContent($data,$new_customer,$order_total_price,$order_pc_total_price,$order_m_total_price,$average_price,$pc_average_price,$m_average_price)
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
            $new_html .= '<div class="tfo"><span>均客价：<b>'.$average_price.'</b></span><span>PC：<b>'.$pc_average_price.'</b></span><span>M：<b>'.$m_average_price.'</b></span></div>';
        }



        foreach ($data as $k=>$v){
            $html .= '<p>'.$k.'單：<b>'.$v.'</b></p>';
        }
        if(!$html){
            $html = '<div style="width: 100%;color: #999;margin-left: 10px">暂无数据</div>';
        }

        return $this->content(
            <<<HTML
<style>
    .repeat{
        display: flex;
        flex-wrap: wrap;
        padding: 0.6rem 1.1rem;
    }
    .repeat p{
        margin-right: 5px;
        border: 1px solid #eee;
        padding: 0.1rem 0.2rem;
        border-radius: 0.2rem;
        margin-bottom: 5px;
        background-color: #eee;
        font-size: 11px;
        color: #333;
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
HTML
        );
    }
}

<?php


namespace Jou\AccessLog\Metrics\Access;



use Dcat\Admin\Widgets\Modal;
use Illuminate\Support\Facades\Cache;
use Jou\AccessLog\AccessLogServiceProvider;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Metrics\Card;
use Jou\AccessLog\Metrics\DateRangeHelper;


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

        $dateRange = DateRangeHelper::getDateRange($range);

        $cache_key = md5('order_repeat'.$dateRange['start'].$dateRange['end']);
        if(!Cache::has($cache_key)){
            $order_model = AccessLogServiceProvider::setting('order_model');
            $orders = app($order_model)->select('phone', \DB::raw('COUNT(*) as count'))->groupBy('phone')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where('status','>',0)->get();
            $phone2 = app($order_model)->where('created_at','<',$dateRange['start'])->pluck('phone');


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

            Cache::set($cache_key,[
                'data'=>$data,'new_customer'=>$new_customer
            ],1800); //缓存半小时
        }else{
            $cache_data = Cache::get($cache_key);

            $data = $cache_data['data'];

            $new_customer = $cache_data['new_customer'];
        }

        $this->withContent($data,$new_customer);

    }


    /**
     * 设置卡片内容.
     *
     * @param array $data
     * @param integer $new_customer
     *
     * @return $this
     */
    public function withContent($data,$new_customer)
    {
        $html = '';
        if($new_customer){
            $html .= '<p>新客：'.$new_customer.'</p>';
        }
        foreach ($data as $k=>$v){
            $html .= '<p>'.$k.'單：'.$v.'</p>';
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
        padding: 0.6rem;
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
</style>
<div class="repeat">
$html
</div>
HTML
        );
    }
}

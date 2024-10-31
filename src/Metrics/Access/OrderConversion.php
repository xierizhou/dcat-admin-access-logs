<?php


namespace Jou\AccessLog\Metrics\Access;



use Dcat\Admin\Widgets\Modal;
use Dcat\Admin\Widgets\Tooltip;
use Illuminate\Support\Facades\Cache;
use Jou\AccessLog\AccessLogServiceProvider;
use Jou\AccessLog\Helper;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Metrics\Card;
use Jou\AccessLog\Metrics\DateRangeHelper;
use Jou\AccessLog\Renderable\OrderConversionBarLazy;


class OrderConversion extends Card
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

        $this->title('订单转化率 <i class="feather text-80 font-md-1 icon-help-circle order_coversion_icon"></i>');
        Tooltip::make('.order_coversion_icon')->purple()->top()->title('有效订单数 / 访客数');
        $dropdown['customize'] = '自定义';
        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';

        $chart = Modal::make()
            ->id('ordermodal')
            ->delay(10)
            ->lg()
            ->title('详细报表')
            ->body(OrderConversionBarLazy::make())
            ->button('<button class="btn btn-primary" style="position: absolute;right: 10px;top: 70px;font-size: 12px;padding: .5rem 1rem !important;"><i class="feather icon-bar-chart-2"></i> 详细报表</button>');
        $this->header($chart);

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

        Cache::set('order_conversion_range',$range);

        $dateRange = DateRangeHelper::getDateRange($range);

        $cache_key = md5('order_conversion'.$dateRange['start'].$dateRange['end']);
        if(!Cache::has($cache_key)){
            $access_log = AccessLog::whereNull('crawler')->where('device','<>','unknown')->where('method', 'GET')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->select(['ip'])->groupBy('ip')->get();
            $access_count = $access_log->count();

            $access_pc_log = AccessLog::whereNull('crawler')->whereIn('device',['windows','mac','linux'])->where('method', 'GET')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->select(['ip'])->groupBy('ip')->get();
            $access_pc_count = $access_pc_log->count();

            $access_m_log = AccessLog::whereNull('crawler')->whereIn('device',['iphone','android','ipad'])->where('method', 'GET')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->select(['ip'])->groupBy('ip')->get();
            $access_m_count = $access_m_log->count();


            $order_model = AccessLogServiceProvider::setting('order_model');
            $order_status = AccessLogServiceProvider::setting('order_status','status');

            $orders = app($order_model)->select(['ip','user_agent'])->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where($order_status,'>',0)->get();
            $pc_order_count = 0;
            $m_order_count = 0;
            foreach ($orders as $order){
                $device_type = Helper::device($order->user_agent);
                if(in_array($device_type,['iphone','android','ipad'])){
                    $m_order_count++;
                }else{
                    $pc_order_count++;
                }


            }
            $rate = 0;
            $order_count = $orders->count();
            if($orders->count() && $access_count){
                $rate = round($orders->count()/$access_count*100,2);
            }

            $pc_rate = 0;
            if($pc_order_count && $access_pc_count){
                $pc_rate = round($pc_order_count/$access_pc_count * 100,2);
            }

            $m_rate = 0;
            if($m_order_count && $access_m_count){
                $m_rate = round($m_order_count/$access_m_count * 100,2);
            }

            Cache::set($cache_key,[
                'rate'=>$rate,'order_count'=>$order_count,'pc_order_count'=>$pc_order_count,'m_order_count'=>$m_order_count,'pc_rate'=>$pc_rate,'m_rate'=>$m_rate
            ],1800); //缓存半小时
        }else{
            $cache_data = Cache::get($cache_key);
            $rate = $cache_data['rate'];
            $order_count = $cache_data['order_count'];
            $pc_order_count = $cache_data['pc_order_count'];
            $m_order_count = $cache_data['m_order_count'];
            $pc_rate = $cache_data['pc_rate'];
            $m_rate = $cache_data['m_rate'];

        }



        $this->withContent($rate.'<span class="font-md-2"> %</span>',$order_count,$pc_order_count,$m_order_count,$pc_rate,$m_rate);
    }


    /**
     * 设置卡片内容.
     *
     * @param string $content
     *
     * @return $this
     */
    public function withContent($content,$order_count,$pc_order_count,$m_order_count,$pc_rate,$m_rate)
    {
        return $this->content(
            <<<HTML
<div class="d-flex justify-content-between align-items-center" style="margin-bottom: 2px">
    <div>
        <p class="ml-1 font-lg-1">{$content}</p>
        <p class="ml-1 font-sm-4">桌面版：{$pc_rate}% &nbsp;&nbsp; 移动版：{$m_rate}%</p>
    </div>
</div>
<p class="ml-1 text-80 font-sm-3">
订单总量: {$order_count}，PC: {$pc_order_count}，M: {$m_order_count}
</p>
<p class="ml-1 text-70 font-sm-1" style="position: absolute;right: 10px;bottom: 0">
*当天数据有延迟 
</p>
HTML
        );
    }
}

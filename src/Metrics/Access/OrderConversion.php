<?php


namespace Jou\AccessLog\Metrics\Access;



use Dcat\Admin\Widgets\Modal;
use Jou\AccessLog\AccessLogServiceProvider;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Metrics\Card;
use Jou\AccessLog\Metrics\DateRangeHelper;


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

        $this->title('订单转化率');

        $dropdown['customize'] = '自定义';
        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';

        $chart = Modal::make()
            ->id('ordermodal')
            ->delay(3)
            ->lg()
            ->title('详细报表')
            ->body(OrderConversionBar::make())
            ->button('<button class="btn btn-primary" style="position: absolute;right: 10px;top: 70px"><i class="feather icon-bar-chart-2"></i> 详细报表</button>');
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

        $access_log = new AccessLog();

        $range = $request->get('option','customize');

        $dateRange = DateRangeHelper::getDateRange($range);

        $access_log = $access_log->where('crawler',null)->where('device','<>','unknown')->where('method', 'GET')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->select('ip')->groupBy('ip')->get();

        $access_count = $access_log->count();

        $order_model = AccessLogServiceProvider::setting('order_model');
        $order_count = app($order_model)->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where('status',2)->count();

        if($order_count && $order_count){
            $rate = ($order_count/$access_count)*100;
            $this->withContent(round($rate,2).'<span class="font-md-2"> %</span>');
        }else{
            $this->withContent('0'.'<span class="font-md-2"> %</span>');
        }
    }


    /**
     * 设置卡片内容.
     *
     * @param string $content
     *
     * @return $this
     */
    public function withContent($content)
    {
        return $this->content(
            <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <p class="ml-1 font-lg-1">{$content}</p>
</div>
<div class="ml-1 mt-1 text-80 font-sm-2">
  订单数量 / IP访问量
</div>

HTML
        );
    }
}

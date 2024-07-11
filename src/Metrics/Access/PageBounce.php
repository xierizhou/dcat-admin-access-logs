<?php


namespace Jou\AccessLog\Metrics\Access;


use Dcat\Admin\Widgets\Metrics\Card;
use Jou\AccessLog\Metrics\DateRangeHelper;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;
use Jou\AccessLog\Renderable\BounceBar;
use Dcat\Admin\Widgets\Modal;
class PageBounce extends Card
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('网站跳出率');
        $dropdown['customize'] = '自定义';
        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';

        $chart = Modal::make()
            ->id('bouncemodal')
            ->delay(3)
            ->lg()
            ->title('详细报表')
            ->body(BounceBar::make())
            ->button('<button class="btn btn-primary" style="position: absolute;right: 10px;top: 70px"><i class="feather icon-bar-chart-2"></i> 详细报表</button>');
        $this->header($chart);



        $this->dropdown($dropdown);



    }

    public function render()
    {

        return parent::render(); // TODO: Change the autogenerated stub
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


        $access_log = $access_log->where('method','GET')->where('crawler',null)->where('device','<>','unknown')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->select('ip','url','crawler')->get();


        // 初始化跳出会话和总会话数量的数组
        $bounceRates = [];

        // 遍历访问记录
        foreach ($access_log as $log) {
            $uri = $log->url;
            $ipAddress = $log->ip;

            // 初始化页面和 IP 地址的跳出会话和总会话数量
            if (!isset($bounceRates[$ipAddress][$uri])) {
                $bounceRates[$ipAddress][$uri] = 1;
            }

            // 记录总的访问会话数量
            $bounceRates[$ipAddress][$uri]++;

        }



        $out = 0;
        $count = count($bounceRates);
        foreach ($bounceRates as $key=>$value){
            if(count($value) <= 1){
                $out++;
            }
        }

        $rate = ($out/$count)*100;
        $this->withContent(round($rate,2).'<span class="font-md-2"> %</span>');



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
    例如有1000IP访问了网站，其中50个没有二次浏览行为，50/1000=5%
</div>
HTML
        );
    }
}

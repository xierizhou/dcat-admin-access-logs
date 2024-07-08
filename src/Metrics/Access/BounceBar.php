<?php


namespace Jou\AccessLog\Metrics\Access;


use Jou\AccessLog\Metrics\Bar;
use Jou\AccessLog\Metrics\DateRangeHelper;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;

class BounceBar extends Bar
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('跳出最多的页面（前10个）<br>');

        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';
        $this->dropdown($dropdown);

        $this->chartHeight(250);

        $this->content('<span style="padding: 16px;color: #999">统计哪些页面只浏览一次，例如：100个独立IP访问了首页，其中50个没有第二次预览，则为50</span>');
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

        $range = $request->get('option',date('n'));
        $dateRange = DateRangeHelper::getDateRange($range);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $uniqueIpLogs = AccessLog::select('ip', 'method','url', 'created_at')
            ->where('method', 'GET')
            ->where('crawler',null)
            ->whereBetween('created_at', [$start, $end])
            ->get();




        // 初始化跳出会话和总会话数量的数组
        $bounceRates = [];

        // 遍历访问记录
        foreach ($uniqueIpLogs as $log) {
            $uri = $log->url;
            $ipAddress = $log->ip;

            // 初始化页面和 IP 地址的跳出会话和总会话数量
            if (!isset($bounceRates[$ipAddress][$uri])) {
                $bounceRates[$ipAddress][$uri] = 1;
            }


        }

        $bounceData = [];
        foreach ($bounceRates as $item){
            if(count($item)<=1){
                $key = array_key_first($item);
                if (!isset($bounceData[$key])) {
                    $bounceData[$key] = 1;
                }else{
                    $bounceData[$key]++;
                }


            }
        }

        arsort($bounceData);

        $bounceData = array_slice($bounceData,0,10);

        $categories = array_keys($bounceData);
        $data = array_values($bounceData);

        $this->withCategories($categories);
        $this->withChart($data);
    }

    /**
     * 设置图表数据.
     *
     * @param array $data
     *
     * @return \App\Admin\Metrics\Dashboard\AccessPage
     */
    public function withChart(array $data)
    {
        return $this->chart([
            'series' => [
                [
                    'name' => $this->title,
                    'data' => $data,
                ],
            ],
        ]);
    }

}

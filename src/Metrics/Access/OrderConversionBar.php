<?php


namespace Jou\AccessLog\Metrics\Access;


use App\Models\Order;
use Jou\AccessLog\AccessLogServiceProvider;
use Jou\AccessLog\Metrics\Bar;
use Jou\AccessLog\Metrics\DateRangeHelper;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;

class OrderConversionBar extends Bar
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('订单流失页面报表（前10个）<br>');
        $dropdown['customize'] = '自定义';
        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';
        $this->dropdown($dropdown);

        $this->chartHeight(250);

        $this->content('<span style="padding: 16px;color: #999">统计没有下单的用户，最终在哪个页面跑了</span>');
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
        $start = $dateRange['start'];
        $end = $dateRange['end'];



        $order_model = AccessLogServiceProvider::setting('order_model');

        $order_ips = app($order_model)->whereBetween('created_at', [$start, $end])->pluck('ip');


        // 分页查询参数
        $pageSize = 20000; // 每次查询1000条记录

        // 初始化跳出会话和总会话数量的数组
        $bounceRates = [];
        $page = 1;


        //通过分页查询处理防止内存溢出
        do{
            $logs = AccessLog::where('method', 'GET')
                ->whereNull('crawler')
                ->where('device', '<>', 'unknown')
                ->whereBetween('created_at', [$start, $end])
                ->select('ip', 'url')
                ->orderBy('created_at','desc')
                ->skip(($page - 1) * $pageSize)
                ->take($pageSize)
                ->get();

            // 遍历访问记录
            foreach ($logs as $log) {
                if($order_ips->contains($log->ip)){
                    continue;
                }

                if (!isset($bounceRates[$log->ip])){
                    $bounceRates[$log->ip] = $log->url;
                }


            }

            $page++;

        }while(!$logs->isEmpty());




        $bounceData = [];
        foreach ($bounceRates as $item){
            if (!isset($bounceData[$item])){
                $bounceData[$item] = 1;
            }else{
                $bounceData[$item]++;
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

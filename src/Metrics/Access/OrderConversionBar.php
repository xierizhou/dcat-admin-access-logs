<?php


namespace Jou\AccessLog\Metrics\Access;


use Illuminate\Support\Facades\Cache;
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

        $this->title('订单流失页面报表（前15个）<br>');
        $dropdown['all'] = '全部';
        $dropdown['pc'] = '桌面版';
        $dropdown['m'] = '移动版';

        $this->dropdown($dropdown);

        $this->chartHeight(350);

        $this->content('<span style="padding: 16px;color: #999">统计没有下单的用户，最终在哪个页面退出了网站</span>');
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
        set_time_limit(0);
        ini_set('memory_limit', '512m');
        $option = $request->get('option','all');
        $range =  Cache::get('order_conversion_range','customize');
        $dateRange = DateRangeHelper::getDateRange($range);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $order_model = AccessLogServiceProvider::setting('order_model');

        $order_ips = app($order_model)->whereBetween('created_at', [$start, $end])->pluck('ip');


        // 分页查询参数
        $pageSize = 100000; // 每次查询1000条记录

        // 初始化跳出会话和总会话数量的数组
        $bounceRates = [];
        $page = 1;


        //通过分页查询处理防止内存溢出
        do{
            $logs = AccessLog::where('method', 'GET')
                ->whereNull('crawler');

            if($option == 'pc'){
                $logs = $logs->whereIn('device', ['windows','mac','linux']);
            }elseif($option == 'm'){
                $logs = $logs->whereIn('device', ['iphone','android','ipad']);
            }else{
                $logs = $logs->where('device', '<>', 'unknown');
            }

            $logs = $logs->whereBetween('created_at', [$start, $end])
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
                    $bounceRates[$log->ip][] = $log->url;
                }else{
                    if(!in_array($log->url,$bounceRates[$log->ip])){
                        $bounceRates[$log->ip][] = $log->url;
                    }

                }


            }

            $page++;

        }while(!$logs->isEmpty());


        $bounceData = [];
        foreach ($bounceRates as $item){
            if(count($item) > 1){
                $uri = array_get($item,0);
                if (!isset($bounceData[$uri])){
                    $bounceData[$uri] = 1;
                }else{
                    $bounceData[$uri]++;
                }
            }

        }

        unset($bounceData['/captcha/flat']);

        unset($bounceData['/get/carts']);

        arsort($bounceData);

        $bounceData = array_slice($bounceData,0,15);

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

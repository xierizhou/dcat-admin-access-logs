<?php


namespace Jou\AccessLog\Metrics\Access;


use Illuminate\Support\Facades\Cache;
use Jou\AccessLog\Helper;
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
        $dropdown['all'] = '全部';
        $dropdown['pc'] = '桌面版';
        $dropdown['m'] = '移动版';
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
        set_time_limit(0);
        ini_set('memory_limit', '512m');
        $option = $request->get('option','all');
        $range = Cache::get('page_bounce_range','customize');
        $dateRange = DateRangeHelper::getDateRange($range);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // 分页查询参数
        $pageSize = 80000; // 每次查询1000条记录

        // 初始化跳出会话和总会话数量的数组
        $bounceRates = [];
        $page = 1;


        //通过分页查询处理防止内存溢出
        do{
            $uniqueIpLogs = AccessLog::where('method', 'GET')
                ->whereNull('crawler')
                ->where('device', '<>', 'unknown')
                ->whereBetween('created_at', [$start,$end])
                ->select('ip', 'url','user_agent')
                ->skip(($page - 1) * $pageSize)
                ->take($pageSize)
                ->get();


            // 遍历访问记录
            foreach ($uniqueIpLogs as $log) {
                $uri = $log->url;
                $ipAddress = $log->ip;

                $device_type = Helper::device($log->user_agent);


                $o = 'pc';
                if(in_array($device_type,['iphone','android','ipad'])){
                    $o = 'm';
                }

                // 初始化页面和 IP 地址的跳出会话和总会话数量
                if (!isset($bounceRates[$ipAddress][$o][$uri])) {
                    $bounceRates[$ipAddress][$o][$uri] = 1;
                }


            }
            $page++;

        }while(!$uniqueIpLogs->isEmpty());



        $bounceData = [];
        foreach ($bounceRates as $item){
            $key = null;
            if($option == 'pc'){

                if(isset($item['pc']) && count($item['pc']) <= 1){
                    $key = array_key_first($item['pc']);
                }

            }else if($option == 'm'){

                if(isset($item['m']) && count($item['m']) <= 1){
                    $key = array_key_first($item['m']);
                }

            }else{

                $item = array_merge(...array_values($item));
                if(count($item)<=1){
                    $key = array_key_first($item);
                }
            }

            if($key){
                if (!isset($bounceData[$key])) {
                    $bounceData[$key] = 1;
                }else{
                    $bounceData[$key]++;
                }
            }



        }
        unset($bounceData['/robots.txt']);
        unset($bounceData['/sitemap.xml']);




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

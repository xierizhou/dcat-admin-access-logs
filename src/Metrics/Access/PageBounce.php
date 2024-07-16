<?php


namespace Jou\AccessLog\Metrics\Access;


use Dcat\Admin\Widgets\Metrics\Card;
use Dcat\Admin\Widgets\Tooltip;
use Illuminate\Support\Facades\Cache;
use Jou\AccessLog\Helper;
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

        $this->title('网站跳出率 <i class="feather  text-80 font-md-1 icon-help-circle jump_out"></i>');
        $dropdown['customize'] = '自定义';
        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';
        Tooltip::make('.jump_out')->purple()->top()->title('在某一段时间内有1000不同访客进入网站，同时这些访客中有50个人没有二次浏览行为，直接退出网站，那么跳出率就是 50/1000=5%。');
        $chart = Modal::make()
            ->id('bouncemodal')
            ->delay(10)
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

        set_time_limit(0);
        ini_set('memory_limit', '512m');
        $range = $request->get('option','customize');
        Cache::set('page_bounce_range',$range);
        $dateRange = DateRangeHelper::getDateRange($range);

        $cache_key = md5('page_bounce'.$dateRange['start'].$dateRange['end']);


        if(!Cache::has($cache_key)){

            // 分页查询参数
            $pageSize = 80000; // 每次查询100000条记录

            // 初始化跳出会话和总会话数量的数组
            $bounceRates = [];
            $page = 1;


            //通过分页查询处理防止内存溢出
            do{
                $access_log = AccessLog::where('method', 'GET')
                    ->whereNull('crawler')
                    ->where('device', '<>', 'unknown')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->select('ip', 'url','user_agent')
                    ->skip(($page - 1) * $pageSize)
                    ->take($pageSize)
                    ->get();


                // 遍历访问记录
                foreach ($access_log as $log) {
                    $uri = $log->url;
                    $ipAddress = $log->ip;

                    $device_type = Helper::device($log->user_agent);


                    $o = 'pc';
                    if(in_array($device_type,['iphone','android','ipad'])){
                        $o = 'm';
                    }


                    // 初始化页面和 IP 地址的跳出会话和总会话数量
                    if (!isset($bounceRates[$ipAddress][$uri])) {
                        $bounceRates[$ipAddress][$o][$uri] = 1;
                    }

                    // 记录总的访问会话数量
                    $bounceRates[$ipAddress][$o][$uri]++;

                }
                $page++;

            }while(!$access_log->isEmpty());



            $out = 0;
            $pc_out = 0;
            $m_out = 0;
            $pc_count = 0;
            $m_count = 0;


            $count = count($bounceRates);

            foreach ($bounceRates as $value){
                if(isset($value['pc'])){
                    if(count($value['pc']) <= 1){
                        $pc_out++;
                    }
                    $pc_count++;
                }
                if(isset($value['m']) && count($value['m'])){
                    if(count($value['m']) <= 1){
                        $m_out++;
                    }
                    $m_count ++;
                }

                $value = array_merge(...array_values($value));

                if(count($value) <= 1){
                    $out++;
                }
            }


            $rate = 0;
            if($out && $count){
                $rate = $out / $count * 100;
            }

            $pc_rate = 0;
            if($pc_out && $pc_count){
                $pc_rate = round($pc_out/$pc_count * 100,2);
            }

            $m_rate = 0;
            if($m_out && $m_count){
                $m_rate = round($m_out/$m_count * 100,2);
            }


            Cache::set($cache_key,[
                'rate'=>$rate,'pc_rate'=>$pc_rate,'m_rate'=>$m_rate,'out'=>$out,'count'=>$count,'pc_out'=>$pc_out,'pc_count'=>$pc_count,'m_out'=>$m_out,'m_count'=>$m_count
            ],1800); //缓存半小时
        }else{
            $cache_data = Cache::get($cache_key);

            $rate = $cache_data['rate'];
            $pc_rate = $cache_data['pc_rate'];
            $m_rate = $cache_data['m_rate'];
            $out = $cache_data['out'];
            $count = $cache_data['count'];
            $pc_out = $cache_data['pc_out'];
            $pc_count = $cache_data['pc_count'];
            $m_out = $cache_data['m_out'];
            $m_count = $cache_data['m_count'];
        }



        $this->withContent(round($rate,2).'<span class="font-md-2"> %</span>',$pc_rate,$m_rate,$out,$count,$pc_out,$pc_count,$m_out,$m_count);

    }


    /**
     * 设置卡片内容.
     *
     * @param string $content
     *
     * @return $this
     */
    public function withContent($content,$pc_rate,$m_rate,$out,$count,$pc_out,$pc_count,$m_out,$m_count)
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
跳出总数: {$out}，PC: {$pc_out}，M: {$m_out}
</p>
<p class="ml-1 text-70 font-sm-1" style="position: absolute;right: 10px;bottom: 0">
*当天数据有延迟 
</p>
HTML
        );
    }
}

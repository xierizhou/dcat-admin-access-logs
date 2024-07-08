<?php


namespace Jou\AccessLog\Metrics\Access;


use Jou\AccessLog\Metrics\DateRangeHelper;
use Jou\AccessLog\Metrics\Line;
use Carbon\Carbon;
use Jou\AccessLog\Models\AccessLog;
use Illuminate\Http\Request;

class MonthAccess extends Line
{
    protected $chartData = [];

    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();


        $this->title('訪問統計表');


        $this->withDropdown();


    }

    public function withDropdown(){
        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';

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


        $range = $request->get('option','today');

        $dateRange = DateRangeHelper::getDateRange($range);
        $start = $dateRange['start'];
        $end = $dateRange['end'];
        if($range == 'today' || $range == 'yesterday'){
            $this->withDayCategories();
            $format = 'H';
        }else{
            $m = date('m',strtotime($start));
            $this->withCategories($m);
            $format = 'd';
        }


        $access_logs = AccessLog::whereBetween('created_at',[$start,$end])->get();


        list($counts,$ip_counts) = $this->getDaysGroupCount($access_logs,$format);

        $chart_data = array_values($counts);


        $chart_data_ip = array_values($ip_counts);


        $this->withContent(array_sum($chart_data),$access_logs->groupBy('ip')->count());

        $this->withChart($chart_data,$chart_data_ip);

    }

    public function getDaysGroupCount($access_logs,$format='d'){
        $new_access_logs = [];
        foreach($access_logs as $item){
            $key = $item->created_at->format($format);
            $new_access_logs[$key][] = $item;
        }

        $count = $this->chartData;
        $temp_ip_data = [];
        foreach($new_access_logs as $key=>$item){
            $nk = (int)$key;
            $count[$nk] = count($item);

            foreach($item as $vv){
                $temp_ip_data[$key][$vv->ip][] = $vv;
            }
        }



        $ip_count = $this->chartData;

        foreach($temp_ip_data as $key=>$item){
            $nk = (int)$key;
            $ip_count[$nk] = count($item);
        }

        return [$count,$ip_count];
    }


    /**
     * 设置图表数据.
     *
     * @param array $data
     * @param array $data2
     * @return $this
     */
    public function withChart(array $data,array $data2)
    {
        return $this->chart([
            'series' => [
                [
                    'name' => "縂訪問量",
                    'data' => $data,
                ],
                [
                    'name' => "IP數量",
                    'data' => $data2,
                ],
            ]
        ]);
    }

    /**
     * 按日分类
     * @param int $month
     */
    public function withCategories(int $month){
        $carbon = new Carbon();
        $d = $carbon->month($month)->lastOfMonth()->format('d');
        $categories = [];
        for ($i=0;$i<$d;$i++){
            //$day = $carbon->now()->month($month)->firstOfMonth()->addDays($i)->format('d');
            $categories[] = str_pad(($i+1),2,'0',STR_PAD_LEFT).'日';
            $this->chartData[$i+1] = 0;
        }


        $this->chart->option('xaxis.categories',$categories);
    }



    /**
     * 按時分类
     */
    public function withDayCategories(){
        $categories = [];
        for ($i=0;$i<24;$i++){
            $k = (int)str_pad($i,2,'0',STR_PAD_LEFT);
            $categories[] = $i.'時';
            $this->chartData[$k] = 0;
        }
        $this->chart->option('xaxis.categories',$categories);
    }

    /**
     * 设置卡片内容.
     *
     * @param string $content
     * @param string $content2
     * @return $this
     */
    public function withContent($content,$content2)
    {
        return $this->content(
            <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <h3 class="ml-2 xie-count">縂訪問量:{$content} &nbsp; IP數量：{$content2}</h3>

</div>
<script>
    $(function(){
        checkAutoUpdate();
    })
</script>
HTML
        );
    }
}

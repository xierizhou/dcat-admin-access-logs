<?php


namespace Jou\AccessLog\Metrics\Access;



use Dcat\Admin\Widgets\Modal;
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


        $order_model = AccessLogServiceProvider::setting('order_model');
        $orders = app($order_model)->select('phone', \DB::raw('COUNT(*) as count'))->groupBy('phone')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->where('status',2)->get();
        $data = [];
        foreach ($orders as $item){
            if(!isset($data[$item->count])){
                $data[$item->count] = 1;
            }else{
                $data[$item->count]++;
            }
        }
        ksort($data);

        $this->withContent($data);
        /*if($order_count && $order_count){
            $rate = ($order_count/$access_count)*100;
            $this->withContent(round($rate,2).'<span class="font-md-2"> %</span>');
        }else{
            $this->withContent('0'.'<span class="font-md-2"> %</span>');
        }*/
    }


    /**
     * 设置卡片内容.
     *
     * @param string $data
     *
     * @return $this
     */
    public function withContent($data)
    {
        $html = '';
        foreach ($data as $k=>$v){
            $html .= '<p>'.$k.'單：'.$v.'</p>';
        }
        if(!$html){
            $html = '<div style="width: 100%;color: #999">暂无数据</div>';
        }

        return $this->content(
            <<<HTML
<style>
    .repeat{
        display: flex;
        flex-wrap: wrap;
        padding: 1rem;
    }
    .repeat p{
        margin-right: 5px;
        border: 1px solid #eee;
        padding: 0.4rem;
        border-radius: 0.2rem;
        margin-bottom: 5px;
        background-color: #eee;
    }
</style>
<div class="repeat">
$html
</div>
HTML
        );
    }
}

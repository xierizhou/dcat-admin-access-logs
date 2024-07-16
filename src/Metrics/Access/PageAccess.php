<?php


namespace Jou\AccessLog\Metrics\Access;


use Jou\AccessLog\Metrics\Bar;
use Jou\AccessLog\Metrics\DateRangeHelper;
use Jou\AccessLog\Models\AccessLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PageAccess extends Bar
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('页面最多訪問(前10個)');
        $dropdown['customize'] = '自定义';
        $dropdown['today'] = '今日';
        $dropdown['yesterday'] = '昨日';
        $dropdown['week'] = '本周';
        $dropdown['last_week'] = '上周';
        $dropdown['month'] = '本月';
        $dropdown['last_month'] = '上月';
        $this->dropdown($dropdown);

        $this->chartHeight(250);


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
        $access_log = $access_log->where('method','GET')->whereBetWeen('created_at',[$dateRange['start'],$dateRange['end']])->selectRaw('url, count(id) as num')->groupBy('url')->orderBy('num','desc')->limit(10)->get();
        $categories = [];
        $data = [];
        foreach($access_log as $item){
            $categories[] = $item->url;
            $data[] = $item->num;
        }
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
<div class="d-flex justify-content-between align-items-center" style="margin-bottom: 2px">
    <h2 class="ml-1 font-lg-1">{$content}</h2>
    <span class="mb-0 mr-1 text-80">{$this->title}</span>
</div>
HTML
        );
    }
}

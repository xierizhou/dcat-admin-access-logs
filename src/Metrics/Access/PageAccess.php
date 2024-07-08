<?php


namespace Jou\AccessLog\Metrics\Access;


use Jou\AccessLog\Metrics\Bar;
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
        $this->dropdown([
            '7' => '最近7天',
            '15' => '最近15天',
            '30' => '最近1個月',
        ]);

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

        $access_log = $access_log->where('method','GET')->whereBetWeen('created_at',[Carbon::now()->subDays($request->get('option',7)),Carbon::now()->endOfDay()])->selectRaw('url, count(id) as num')->groupBy('url')->orderBy('num','desc')->limit(10)->get();
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
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <h2 class="ml-1 font-lg-1">{$content}</h2>
    <span class="mb-0 mr-1 text-80">{$this->title}</span>
</div>
HTML
        );
    }
}

<?php


namespace Jou\AccessLog\Metrics;


use Dcat\Admin\Widgets\Metrics\Card;

/**
 * 矩形圖
 *
 * Class Line
 */
class Bar extends Card
{
    /**
     * 图表默认高度.
     *
     * @var int
     */
    protected $chartHeight = 300;

    /**
     * 图表默认配置.
     *
     * @var array
     */
    protected $chartOptions = [
        'chart' => [
            'type' => 'bar',
            'toolbar' => [
                'show' => false,
            ],
            'grid' => [
                'show' => false,
                'padding' => [
                    'left' => 0,
                    'right' => 0,
                ],
            ],
        ],
        'plotOptions' => [
            'bar' => [
                'horizontal' => true,
                'dataLabels' => [
                    'position' => 'top',
                ],
            ]
        ],


        'xaxis' => [
            'labels' => [
                'show' => true,
            ],


        ],
        'dataLabels' => [
            'enabled' => true,
            'offsetX' => -6,
            'style' => [
                'fontSize' => '12px',
                'colors' => ['#fff']
            ]
        ],

    ];

    /**
     * 初始化.
     */
    protected function init()
    {
        parent::init();

        // 使用图表
        $this->useChart();
        $this->chart->options($this->chartOptions);
        // 兼容图表显示不全问题
        $this->chart->style('margin-right:-6px;');

    }



    /**
     * 设置图表类别.
     *
     * @param array $data
     *
     * @return $this
     */
    public function withCategories(array $data)
    {
        $categories['categories'] = $data;
        $xaxis = array_merge($this->chartOptions['xaxis'],$categories);
        $this->chartOptions['xaxis'] = $xaxis;

    }


    /**
     * 渲染内容，加上图表.
     *
     * @return string
     */
    public function renderContent()
    {
        $content = parent::renderContent();

        return <<<HTML
{$content}
<div class="card-content">
    {$this->renderChart()}
</div>
HTML;
    }
}

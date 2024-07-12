<?php
namespace Jou\AccessLog\Renderable;

use Dcat\Admin\Support\LazyRenderable;
class OrderConversionBarLazy extends LazyRenderable
{
    public function render()
    {

        // 这里可以返回内置组件，也可以返回视图文件或HTML字符串
        return \Jou\AccessLog\Metrics\Access\OrderConversionBar::make();
    }

}
<?php

namespace Jou\AccessLog;

use Dcat\Admin\Extend\Setting as Form;

class Setting extends Form
{

    public function form()
    {
        $this->textarea('except','Except')->help('过滤器，每行代表一条');
        $this->checkbox('methods')->options([ 'GET'=> 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE']);
        $this->text('order_model','指定订单模型')->help('用于计算订单转化率');
    }
}

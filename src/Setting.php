<?php

namespace Jou\AccessLog;

use Dcat\Admin\Extend\Setting as Form;

class Setting extends Form
{

    public function form()
    {
        $this->textarea('except','Except')->help('过滤器，每行代表一条');
        $this->checkbox('methods')->options([ 'GET'=> 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE']);
    }
}

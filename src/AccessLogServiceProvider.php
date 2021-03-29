<?php

namespace Jou\AccessLog;

use Dcat\Admin\Extend\ServiceProvider;
use Dcat\Admin\Admin;
use Jou\AccessLog\Http\Middleware\AccessLogMiddleware;
use Jou\AccessLog\Providers\MiddlewareServiceProvider;

class AccessLogServiceProvider extends ServiceProvider
{

    protected $menu = [
        [
            'title' => '訪問記錄',
            'uri'   => 'access-log',
            'icon'  => '', // 图标可以留空
        ]
    ];

	protected $js = [
        'js/jquery.cookie.js',
        'js/access.js',
    ];
	protected $css = [
		'css/index.css',
	];

	public function register()
	{

        $this->app->register(MiddlewareServiceProvider::class);

	}


	public function init()
	{
		parent::init();

		//

	}

	public function settingForm()
	{
		return new Setting($this);
	}

}

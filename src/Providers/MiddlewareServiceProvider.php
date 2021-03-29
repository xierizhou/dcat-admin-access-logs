<?php
namespace Jou\AccessLog\Providers;
use Illuminate\Support\ServiceProvider;
use Jou\AccessLog\Http\Middleware\AccessLogMiddleware;

class MiddlewareServiceProvider extends  ServiceProvider
{
    public function boot(){
        $this->addMiddlewareAlias('access.log',AccessLogMiddleware::class);

    }

    protected function addMiddlewareAlias($name, $class)
    {
        $router = $this->app['router'];

        // 判断aliasMiddleware是否在类中存在
        if (method_exists($router, 'aliasMiddleware')) {
            // aliasMiddleware 顾名思义,就是给中间件设置一个别名
            $router->aliasMiddleware($name, $class);
        }
        return $router->pushMiddlewareToGroup('web',$class);

    }
}

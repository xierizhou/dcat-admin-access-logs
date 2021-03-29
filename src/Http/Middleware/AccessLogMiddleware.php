<?php

namespace Jou\AccessLog\Http\Middleware;


use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Jou\AccessLog\Jobs\AccessLog;
use Jou\AccessLog\AccessLogServiceProvider;
class AccessLogMiddleware
{
    private $request;

    private $response;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->request = $request;
        $this->response = $next($this->request);
        if(!$this->exceptRoute() && $this->allowedMethod()){

            $this->dispatch();
        }
        return $this->response;
    }

    public function exceptRoute(){
        $except = AccessLogServiceProvider::setting('except');

        $excepts = explode(PHP_EOL,$except);
        $excepts = array_filter($excepts);
        $prefix = config('admin.route.prefix');
        array_unshift($excepts,$prefix);
        array_unshift($excepts,$prefix.'/*');

        $is_except = false;
        foreach($excepts as $except){
            $except = trim($except);
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($this->request->fullUrlIs($except) || $this->request->is($except)) {
                $is_except = true;
                break;
            }

        }

        return $is_except;

    }

    public function allowedMethod(){
        $methods = AccessLogServiceProvider::setting('methods');

        if($methods && in_array($this->request->method(),$methods)){
            return true;
        }else{
            return false;
        }
    }

    public function dispatch(){
        $request = $this->request;

        $response = $this->response;

        AccessLog::dispatch(
            $request->path(),
            $request->method(),
            $request->getHost(),
            Arr::get($_SERVER,'HTTP_REFERER'),$request->header('cf-connecting-ip',$request->ip()),
            $request->userAgent() ,
            $request->toArray(),
            $request->header() ,
            $response->content()
        )->onQueue('access');
    }
}

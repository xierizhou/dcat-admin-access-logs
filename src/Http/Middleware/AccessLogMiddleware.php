<?php

namespace Jou\AccessLog\Http\Middleware;


use Closure;
use Illuminate\Support\Arr;
use Jou\AccessLog\Jobs\AccessLog;
class AccessLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        AccessLog::dispatch(
            $request->path(),
            $request->method(),
            $request->getHost(),
            Arr::get($_SERVER,'HTTP_REFERER'),$request->header('cf-connecting-ip',$request->ip()),
            $request->userAgent() ,
            $request->toArray(),
            $request->header() ,
            $response->content()
        );
        return $response;
    }
}

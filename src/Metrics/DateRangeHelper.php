<?php

namespace Jou\AccessLog\Metrics;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class DateRangeHelper
{
    /**
     * 获取指定时间范围的开始和结束时间
     *
     * @param string|null $range 今日，昨日，本周，上周，本月，上月
     * @return array 开始时间和结束时间
     */
    public static function getDateRange($range = null){
        $now = Carbon::now();


        switch ($range) {
            case 'customize':
                $time = Redis::get('access_request_time');
                if($time){
                    $time2 = json_decode($time,true);
                    $start = Carbon::parse($time2['start']);
                    $end = Carbon::parse($time2['end']);
                }else{
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                }
                break;
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'today':
            default:
                $start = $now->copy()->startOfDay();

                $end = $now->copy()->endOfDay();
        }

        return [
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
        ];
    }
}
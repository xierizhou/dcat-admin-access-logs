<?php

namespace Jou\AccessLog\Models;

use Illuminate\Database\Eloquent\Model;
use Dcat\Admin\Traits\HasDateTimeFormatter;

/**
 * Class AccessLog
 * @package Jou\AccessLog\Models
 */
class AccessLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'jou_access_logs';

    protected $fillable = [
        'url','method','host','referer','ip','ipcountry','user_agent','device','crawler','parameter','headers','response'
    ];

    protected $casts = [
        'parameter' => 'json',
        'headers' => 'json',
    ];
}

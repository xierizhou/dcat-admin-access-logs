<?php

use Jou\AccessLog\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('access-log', Controllers\AccessLogController::class.'@index');

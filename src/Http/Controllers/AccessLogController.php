<?php

namespace Jou\AccessLog\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Support\Facades\Redis;
use Jou\AccessLog\AccessLogServiceProvider;
use Jou\AccessLog\Metrics\Access\MonthAccess;
use Jou\AccessLog\Metrics\Access\OrderConversion;
use Jou\AccessLog\Metrics\Access\OrderRepeat;
use Jou\AccessLog\Metrics\Access\PageAccess;
use Jou\AccessLog\Metrics\Access\PageBounce;
use Jou\AccessLog\Metrics\Access\Statistics;
use Jou\AccessLog\Models\AccessLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Row;
use Illuminate\Routing\Controller;


class AccessLogController extends Controller
{
    public function index(Content $content)
    {
       set_time_limit(0);
        $request = request();
       if(!$request->created_at){
            $start_time=Carbon::now()->startOfDay();
            $end_time=Carbon::now()->endOfDay();
            $url = admin_url('access-log').'?created_at[start]='.$start_time.'&created_at[end]='.$end_time;
            return redirect()->to($url);
        }

        Redis::set('access_request_time',json_encode($request->created_at));


        ini_set('memory_limit', '512m');

        Admin::style(
            <<<STYLE
.develop{
    font-size: .9rem;
}
.custom-data-table-header{
    position: absolute;
    right: 20px;
    top:120px;
    z-index: 10;
}

STYLE
        );
        /*Admin::js(asset('static/js/jquery.cookie.js'));
        Admin::js(asset('static/admin/js/access.js'));*/
        /*Admin::js(['@jou.access-log/js/jquery.cookie.js']);
        Admin::js(['@jou.access-log/js/access.js']);*/

        Admin::requireAssets('@jou.access-log');

        $content->row(function(Row $row){
            $row->column(6,new MonthAccess());
            $row->column(6,new PageAccess());
        });
        $content->row(function(Row $row){
            $row->column(3,new PageBounce());

            $order_model = AccessLogServiceProvider::setting('order_model');
            if($order_model){
                $row->column(3,new OrderConversion());
                $row->column(3,new OrderRepeat());
            }


            $row->column(3,new Statistics());



        });
        $content->row(function(Row $row){
            //$row->column(12,$this->rightStatistics());
            $row->column(12,$this->lists());
        });
        return $content;
    }

    protected function lists(){


        return Grid::make(new AccessLog(), function (Grid $grid) {
            $grid->model()->orderBy('created_at','desc');
            $grid->column('created_at',"時間");
            $grid->column('ip','IP')->filter('ip');
            $grid->column('url','URL');
            $grid->column('method','請求方式');
            $grid->column('referer','來源');
            $grid->column('user_agent','載具')->width("300px");
            $grid->column('host','域名');
            $grid->disableFilterButton();
            $grid->disableRefreshButton();
            //$grid->disableToolbar();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->between('created_at', "時間")->datetime()->width(4);
                $filter->equal('ip')->width(3);
                $filter->equal('url')->width(3);
                $filter->equal('method','請求方式')->select(['GET'=>'GET','POST'=>'POST'])->width(2);
                $filter->equal('host','域名')->width(3);
                $filter->in('device','設備')->width(3)->multipleSelect(['iphone' => 'iphone','android' => 'android','ipad' => 'ipad','windows' => 'windows','mac' => 'Mac','linux'=>'linux','unknown'=>'unknown']);
                $filter->where('crawler',function ($query){
                    $input = $this->input;
                    if($input == 'googlebot'){
                        $query->where('crawler','googlebot');
                    }elseif ($input == 'other_bot'){
                        $query->whereNull('crawler')->where('device','unknown');
                    }elseif($input == 'all_bot'){
                        $query->where(function ($query){
                            $query->where('crawler','googlebot')->orWhere('device','unknown');
                        });

                    }elseif($input == 'exp_bot'){
                        $query->whereNull('crawler')->where('device','<>','unknown');
                    }
                },'搜索引擎')->select([
                    'all'=>'全部',
                    'googlebot'=>'Googlebot',
                    'other_bot'=>'其它的蜘蛛',
                    'all_bot'=>'所有蜘蛛访问',
                    'exp_bot'=>'正常用户访问',
                ])->width(3);
                $filter->panel();
                $filter->expand();
            });

            $grid->export();

            $grid->toolsWithOutline(false);
            $grid->export()->disableExportCurrentPage();
            $grid->export()->disableExportSelectedRow();

            $grid->disableRowSelector();
            $grid->disableActions();
            $grid->disableCreateButton();

        });
    }

}

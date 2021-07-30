<?php

namespace Jou\AccessLog\Http\Controllers;

use Carbon\Carbon;
use Jou\AccessLog\Metrics\Access\MonthAccess;
use Jou\AccessLog\Metrics\Access\PageAccess;
use Jou\AccessLog\Models\AccessLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Row;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class AccessLogController extends Controller
{
    public function index(Content $content)
    {
        $request = request();
       if(!$request->created_at){
            $start_time=Carbon::now()->startOfDay();
            $end_time=Carbon::now()->endOfDay();
            $url = admin_url('access-log').'?created_at[start]='.$start_time.'&created_at[end]='.$end_time;
            return redirect()->to($url);
        }
        ini_set('memory_limit', '256m');

        Admin::style(
            <<<STYLE
.develop{
    font-size: .9rem;
}
STYLE
        );
        /*Admin::js(asset('static/js/jquery.cookie.js'));
        Admin::js(asset('static/admin/js/access.js'));*/
        /*Admin::js(['@jou.access-log/js/jquery.cookie.js']);
        Admin::js(['@jou.access-log/js/access.js']);*/

        Admin::requireAssets('@jou.access-log');

        $content->row(function(Row $row){
            $row->column(12,new MonthAccess());
            $row->column(12,new PageAccess());
        });

        $content->row(function(Row $row){
            $row->column(12,$this->rightStatistics());
            $row->column(12,$this->lists());
        });
        return $content;
    }

    protected function lists(){
        return Grid::make(new AccessLog(), function (Grid $grid) {
            $grid->model()->orderBy('created_at','desc');
            $grid->column('created_at',"時間");
            $grid->column('ip','IP');
            $grid->column('url','URL');
            $grid->column('method','請求方式');
            $grid->column('referer','來源');
            $grid->column('user_agent','載具')->width("300px");
            $grid->column('host','域名');
            $grid->disableFilterButton();
            $grid->disableToolbar();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->between('created_at', "訪問時間")->datetime()->width(4);
                $filter->equal('ip')->width(3);
                $filter->equal('url')->width(3);
                $filter->equal('method','請求方式')->select(['GET'=>'GET','POST'=>'POST'])->width(2);
                $filter->equal('host','域名')->width(3);
                $filter->in('device','設備')->width(4)->multipleSelect(['iphone' => 'iphone','android' => 'android','ipad' => 'ipad','windows' => 'windows','mac' => 'MAC','linux'=>'linux','unknown'=>'unknown']);
                $filter->panel();
                $filter->expand();
            });

            $grid->disableRowSelector();
            $grid->disableActions();
            $grid->disableCreateButton();

        });
    }

    protected function rightStatistics(){
        $request = request();
        $access_log = new AccessLog();
        if($request->get('ip')){
            $access_log = $access_log->where('ip',$request->get('ip'));
        }
        if($request->get('url')){
            $access_log = $access_log->where('url',$request->get('url'));
        }

        if($request->get('method')){
            $access_log = $access_log->where('method',$request->get('method'));
        }

        if($request->get('host')){
            $access_log = $access_log->where('host',$request->get('host'));
        }

        if($request->get('created_at')){
            if(Arr::get($request->get('created_at'),'start')){
                $access_log = $access_log->where('created_at','>=',Arr::get($request->get('created_at'),'start'));
            }

            if(Arr::get($request->get('created_at'),'end')){
                $access_log = $access_log->where('created_at','<=',Arr::get($request->get('created_at'),'end'));
            }
        }

        if($request->get('device')){
            $access_log = $access_log->whereIn('device',$request->get('device'));
        }

        $access = $access_log->get();

        $total_count = count($access);
        $total_ip_count = [];

        $mobile_data = [];
        $pc_data = [];
        foreach($access as $item){
            $total_ip_count[$item->ip] = 1;

            if(in_array($item->device,['iphone','android'])){
                $mobile_data[] = $item;
            }else{
                $pc_data[] = $item;
            }

        }

        $total_ip_count = count($total_ip_count);

        $m_count = count($mobile_data);
        $m_ip_count = count(array_unique(array_column($mobile_data,'ip')));

        $pc_count = count($pc_data);
        $pc_ip_count = count(array_unique(array_column($pc_data,'ip')));



        return <<<HTML
<div style="float:right">
    IP數量:$total_ip_count 訪問數:$total_count  |  PC IP數:$pc_ip_count 訪問數:$pc_count  |  m版 IP數:$m_ip_count  訪問數:$m_count
</div>
HTML;
    }
}

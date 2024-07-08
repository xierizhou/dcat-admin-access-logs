<?php

namespace Jou\AccessLog\Http\Controllers;

use Carbon\Carbon;
use Dcat\Admin\Widgets\Modal;
use Jou\AccessLog\AccessLogServiceProvider;
use Jou\AccessLog\Metrics\Access\MonthAccess;
use Jou\AccessLog\Metrics\Access\OrderConversion;
use Jou\AccessLog\Metrics\Access\PageAccess;
use Jou\AccessLog\Metrics\Access\PageBounce;
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
       set_time_limit(0);
        $request = request();
       if(!$request->created_at){
            $start_time=Carbon::now()->startOfDay();
            $end_time=Carbon::now()->endOfDay();
            $url = admin_url('access-log').'?created_at[start]='.$start_time.'&created_at[end]='.$end_time;
            return redirect()->to($url);
        }
        ini_set('memory_limit', '512m');

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
        $chart = Modal::make()
            ->lg()
            ->title('异步加载 - 图表')
            ->body("123123")
            ->button('<button class="btn btn-white"><i class="feather icon-bar-chart-2"></i> 异步加载</button>');
        $content->row(function(Row $row){
            $row->column(6,new MonthAccess());
            $row->column(6,new PageAccess());
        });
        $content->row(function(Row $row) use ($chart){
            $row->column(4,new PageBounce());

            $order_model = AccessLogServiceProvider::setting('order_model');
            if($order_model){
                $row->column(4,new OrderConversion());
            }
            $row->column(4,$this->rightStatistics());

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
            $grid->disableToolbar();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->between('created_at', "訪問時間")->datetime()->width(4);
                $filter->equal('ip')->width(3);
                $filter->equal('url')->width(3);
                $filter->equal('method','請求方式')->select(['GET'=>'GET','POST'=>'POST'])->width(2);
                $filter->equal('host','域名')->width(3);
                $filter->in('device','設備')->width(3)->multipleSelect(['iphone' => 'iphone','android' => 'android','ipad' => 'ipad','windows' => 'windows','mac' => 'MAC','linux'=>'linux','unknown'=>'unknown']);
                $filter->like('user_agent','载具')->width(3);
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
        if($request->get('filter-ip')){
            $request->merge(['ip'=>$request->get('filter-ip')]);
        }
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

        $access = $access_log->select('id','url','method','host','ip','referer','user_agent','device')->get();

        $total_count = count($access);
        $total_ip_count = [];

        $mobile_data = [];
        $pc_data = [];
        $device_count = [
            'windows'=>[],
            'mac'=>[],
            'iphone'=>[],
            'android'=>[],
            'ipad'=>[],
            'unknown'=>[],
        ];
        foreach($access as $item){
            $total_ip_count[$item->ip] = 1;


            $agent = strtolower($item->user_agent);

            $device_type = 'unknown';


            $device_type = (strpos($agent, 'windows')) ? 'windows' : $device_type;

            $device_type = (strpos($agent, 'mac')) ? 'mac' : $device_type;

            $device_type = (strpos($agent, 'iphone')) ? 'iphone' : $device_type;

            $device_type = (strpos($agent, 'ipad')) ? 'ipad' : $device_type;

            $device_type = (strpos($agent, 'android')) ? 'android' : $device_type;


            $device_count[$device_type][] = $item->ip;

            if(in_array($device_type,['iphone','android','ipad'])){
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

        $mac_count = count($device_count['mac']);
        $mac_count_ip = count(array_unique($device_count['mac']));

        $win_count = count($device_count['windows']);
        $win_count_ip = count(array_unique($device_count['windows']));

        $iphone_count = count($device_count['iphone']);
        $iphone_count_ip = count(array_unique($device_count['iphone']));

        $android_count = count($device_count['android']);
        $android_count_ip = count(array_unique($device_count['android']));

        $ipad_count = count($device_count['ipad']);
        $ipad_count_ip = count(array_unique($device_count['ipad']));

        $unknown_count = count($device_count['unknown']);
        $unknown_count_ip = count(array_unique($device_count['unknown']));
        return <<<HTML
<style>
.statices{

}
.statices .flex{
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.statices-block{
    margin-bottom: 10px;
    width: 49%;
    background-color: #fff;
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, .05);

    border-radius: .25rem;
    color: #333;
    text-align: left;
    padding: .5rem 1.1rem;
}
.statices-block .lab{
    font-size: 12px;
    color: #333;
    display: block;
    text-align: left;
    border-bottom: 1px solid #eee;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    
}
.statices-block .text{
    display: block;
    font-size: 12px;
}
</style>
<div class="statices">
    <div class="statices-block" style="width: 100%;padding: .7rem;font-size: 14px">
        <span style="margin-right: 10px">总访问量：$total_count</span> <span>独立访问量：$total_ip_count</span>
    </div>
    <div class="flex">
        <div class="statices-block" >
            <span class="lab">桌面版（PC）</span>
            <span class="text">总量：$pc_count ｜ IP：$pc_ip_count </span>
            <span class="text">Mac：$mac_count ｜ IP：$mac_count_ip </span>
            <span class="text">Win：$win_count ｜ IP：$win_count_ip </span>
            <span class="text">其它：$unknown_count ｜ IP：$unknown_count_ip </span>
        </div>
        <div class="statices-block">
            <span class="lab">移动版（Mobile & ipad）</span>
            <span class="text">总量：$m_count ｜ IP：$m_ip_count </span>
            <span class="text">iPhone：$iphone_count ｜ IP：$iphone_count_ip </span>
            <span class="text">Android：$android_count ｜ IP：$android_count_ip </span>
            <span class="text">iPad：$ipad_count ｜ IP：$ipad_count_ip </span>
        </div>
    </div>
</div>


HTML;
    }
}

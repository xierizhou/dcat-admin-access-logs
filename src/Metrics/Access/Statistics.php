<?php

namespace Jou\AccessLog\Metrics\Access;

use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Jou\AccessLog\Models\AccessLog;

class Statistics extends Card
{
    /**
     * 卡片底部内容.
     *
     * @var string|Renderable|\Closure
     */
    protected $footer;

    // 保存自定义参数
    protected $data = [];

    // 构造方法参数必须设置默认值
    public function __construct(array $data = [])
    {

        $this->data = [];

        parent::__construct();
    }

    protected function init()
    {
        parent::init();



    }

    /**
     * 处理请求.
     *
     * @param Request $request
     *
     * @return void
     */
    public function handle(Request $request)
    {
        ini_set('memory_limit', '512m');
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


        // 分页查询参数
        $pageSize = 5000; // 每次查询1000条记录

        // 初始化分页
        $page = 1;

        $total_count = 0;
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
        do{
            $access = $access_log
                ->select('id','ip','user_agent')
                ->skip(($page - 1) * $pageSize)
                ->take($pageSize)
                ->get();
            $total_count += $access->count();

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


            $page++;
        }while(!$access->isEmpty());




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

        return $this->withContent($total_count,$total_ip_count,$m_count,$m_ip_count,$pc_count,$pc_ip_count,$mac_count,$mac_count_ip,$win_count,$win_count_ip,$iphone_count,$iphone_count_ip,$android_count,$android_count_ip,$ipad_count,$ipad_count_ip,$unknown_count,$unknown_count_ip);

    }

    // 传递自定义参数到 handle 方法
    public function parameters() : array
    {

        return request()->all();
    }


    /**
     * 渲染卡片内容
     * 在这里即可加上卡片底部内容
     *
     * @return string
     */
    public function render()
    {
        $content = parent::render();


        return <<<HTML
    <style>
    .Jou_AccessLog_Metrics_Access_Statistics .card-header{
        display: none!important;
    }
    
</style>
        {$content}

HTML;
    }


    /**
     * 渲染卡片内容.
     *
     * @return string
     */
    public function withContent($total_count,$total_ip_count,$m_count,$m_ip_count,$pc_count,$pc_ip_count,$mac_count,$mac_count_ip,$win_count,$win_count_ip,$iphone_count,$iphone_count_ip,$android_count,$android_count_ip,$ipad_count,$ipad_count_ip,$unknown_count,$unknown_count_ip)
    {


        $this->content( <<<HTML
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

    width: 48%;
    background-color: #fff;


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
    <div class="statices-block" style="width: 100%;padding: .7rem;font-size: 14px;">
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


HTML
        );
    }

}
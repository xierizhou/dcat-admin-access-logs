<?php


namespace Jou\AccessLog\Handlers;


class DeviceTypeHandlers
{
    public static function getDevice($agent = null){
        if(!$agent){
            $agent  = $_SERVER['HTTP_USER_AGENT'];
        }
        $agent = strtolower($agent);

        $device_type = 'unknown';

        $device_type = (strpos($agent, 'windows')) ? 'windows' : $device_type;

        $device_type = (strpos($agent, 'mac')) ? 'mac' : $device_type;

        $device_type = (strpos($agent, 'iphone')) ? 'iphone' : $device_type;

        $device_type = (strpos($agent, 'ipad')) ? 'ipad' : $device_type;

        $device_type = (strpos($agent, 'android')) ? 'android' : $device_type;

        $device_type = (strpos($agent, 'linux')) ? 'linux' : $device_type;

        return $device_type;

    }


    /**
     *    判断是否为搜索引擎蜘蛛
     *
     * @param null $agent
     * @return  string
     * @author
     */
    public static function getCrawler($agent = null) {
        if($agent){
            $agent = strtolower($agent);
        }else{
            $agent= strtolower($_SERVER['HTTP_USER_AGENT']);
        }
        if (!empty($agent)) {
            $spiderSite= array(
                "TencentTraveler",
                "Baiduspider+",
                "BaiduGame",
                "Googlebot",
                "msnbot",
                "Sosospider+",
                "Sogou web spider",
                "ia_archiver",
                "Yahoo! Slurp",
                "YoudaoBot",
                "Yahoo Slurp",
                "MSNBot",
                "Java (Often spam bot)",
                "BaiDuSpider",
                "Voila",
                "Yandex bot",
                "BSpider",
                "twiceler",
                "Sogou Spider",
                "Speedy Spider",
                "Google AdSense",
                "Heritrix",
                "Python-urllib",
                "Alexa (IA Archiver)",
                "Ask",
                "Exabot",
                "Custo",
                "OutfoxBot/YodaoBot",
                "yacy",
                "SurveyBot",
                "legs",
                "lwp-trivial",
                "Nutch",
                "StackRambler",
                "The web archive (IA Archiver)",
                "Perl tool",
                "MJ12bot",
                "Netcraft",
                "MSIECrawler",
                "WGet tools",
                "larbin",
                "Fish search",
            );
            foreach($spiderSite as $val) {
                $str = strtolower($val);
                if (strpos($agent, $str) !== false) {
                    return $str;
                }
            }
            return null;
        } else {
            return null;
        }
    }
}

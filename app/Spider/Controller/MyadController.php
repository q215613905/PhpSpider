<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Spider\Controller;

use GuzzleHttp\Client;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Redis\Redis;


/**
 * Class IndexController
 * @Controller(prefix="spider")
 * @package App\Controller
 */
class MyadController extends SpiderController
{

    protected Client $client;
    protected string $siteUrl = "https://ip880110127.mobgslb.tbcache.com";

    protected $classes = '[{"type_id":"txsp","type_name":"腾讯"},{"type_id":"iqiyi","type_name":"爱奇艺"},{"type_id":"bilibili","type_name":"bilibili"},{"type_id":"mgtv","type_name":"芒果"},{"type_id":"ixigua","type_name":"西瓜"}]';
    protected $filterstring = '{"txsp":[{"key":"cid","name":"分类","value":[{"n":"电视剧","v":"tv"},{"n":"电影","v":"movie"},{"n":"综艺","v":"variety"},{"n":"动漫","v":"cartoon"},{"n":"纪录片","v":"doco"}]},{"key":"sort","name":"排序","value":[{"n":"最新","v":"19"},{"n":"最热","v":"18"},{"n":"好评","v":"16"},{"n":"口碑好剧","v":"21"},{"n":"高分好评","v":"54"},{"n":"知乎高分","v":"22"}]},{"key":"year","name":"年份","value":[{"n":"全部","v":"-1"},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"4061"},{"n":"2018","v":"4060"},{"n":"2017","v":"2017"},{"n":"2016","v":"859"},{"n":"2015","v":"860"},{"n":"2014","v":"861"},{"n":"2013","v":"862"},{"n":"2012","v":"863"},{"n":"2011","v":"864"},{"n":"2010","v":"865"},{"n":"其他","v":"866"}]},{"key":"feature","name":"类型","value":[{"n":"全部","v":"-1"},{"n":"偶像爱情","v":"1"},{"n":"古装历史","v":"2"},{"n":"玄幻史诗","v":"3"},{"n":"都市生活","v":"4"},{"n":"当代主旋律","v":"14"},{"n":"罪案谍战","v":"5"},{"n":"历险科幻","v":"6"},{"n":"军旅抗战","v":"7"},{"n":"喜剧","v":"8"},{"n":"武侠江湖","v":"9"},{"n":"青春校园","v":"10"},{"n":"时代传奇","v":"11"},{"n":"体育电竞","v":"12"},{"n":"真人动漫","v":"13"},{"n":"网络剧","v":"10471"},{"n":"独播","v":"44"}]},{"key":"iarea","name":"地区","value":[{"n":"全部","v":"-1"},{"n":"内地","v":"814"},{"n":"美国","v":"815"},{"n":"英国","v":"816"},{"n":"韩国","v":"818"},{"n":"泰国","v":"9"},{"n":"日本","v":"10"},{"n":"中国香港","v":"14"},{"n":"中国台湾","v":"817"},{"n":"其他","v":"819"}]}],"iqiyi":[{"key":"cid","name":"分类","value":[{"n":"电视剧","v":"2"},{"n":"电影","v":"1"},{"n":"综艺","v":"6"},{"n":"动漫","v":"4"},{"n":"纪录片","v":"3"}]},{"key":"sort","name":"排序","value":[{"n":"综合排序","v":"24"},{"n":"热播榜","v":"4"},{"n":"新上线","v":"11"}]},{"key":"year","name":"年份","value":[{"n":"全部","v":""},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016-2011","v":"2016-2011"},{"n":"2010-2000","v":"2010-2000"},{"n":"90年代","v":"1990-1999"},{"n":"80年代","v":"1980-1989"},{"n":"更早","v":"1964-1979"}]},{"key":"feature","name":"类型","value":[{"n":"全部","v":"-1"},{"n":"喜剧","v":"8"},{"n":"爱情","v":"6"},{"n":"动作","v":"11"},{"n":"枪战","v":"131"},{"n":"犯罪","v":"291"},{"n":"惊悚","v":"128"},{"n":"恐怖","v":"10"},{"n":"悬疑","v":"289"},{"n":"动画","v":"12"},{"n":"家庭","v":"27356"},{"n":"奇幻","v":"1284"},{"n":"魔幻","v":"129"},{"n":"科幻","v":"9"},{"n":"战争","v":"7"},{"n":"青春","v":"130"}]},{"key":"iarea","name":"地区","value":[{"n":"全部","v":""},{"n":"华语","v":"1"},{"n":"香港地区","v":"28997"},{"n":"美国","v":"2"},{"n":"欧洲","v":"3"},{"n":"韩国","v":"4"},{"n":"日本","v":"308"},{"n":"泰国","v":"1115"},{"n":"印度","v":"28999"},{"n":"其他","v":"5"}]}],"bilibili":[{"key":"cid","name":"分类","value":[{"n":"番剧","v":"1"},{"n":"动漫","v":"4"},{"n":"电影","v":"2"},{"n":"电视剧","v":"5"},{"n":"纪录片","v":"3"}]},{"key":"sort","name":"排序","value":[{"n":"追番人数","v":"3"},{"n":"更新时间","v":"0"},{"n":"最高评分","v":"4"},{"n":"播放数量","v":"2"},{"n":"开播时间","v":"5"}]},{"key":"year","name":"年份","value":[{"n":"全部","v":"-1"},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016","v":"2016"},{"n":"2015","v":"2015"},{"n":"2014-2010","v":"2014-2010"},{"n":"2009-2005","v":"2009-2005"},{"n":"更早","v":"更早"}]},{"key":"iarea","name":"地区","value":[{"n":"全部","v":"-1"},{"n":"日本","v":"2"},{"n":"美国","v":"3"},{"n":"其他","v":"1,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52"}]}],"mgtv":[{"key":"cid","name":"分类","value":[{"n":"综艺","v":"1"},{"n":"电视剧","v":"2"},{"n":"电影","v":"3"},{"n":"少儿","v":"10"},{"n":"动漫","v":"50"},{"n":"纪录片","v":"51"}]},{"key":"sort","name":"排序","value":[{"n":"最热","v":"c2"},{"n":"最新","v":"c1"},{"n":"知乎高分","v":"c4"}]},{"key":"year","name":"年份","value":[{"n":"全部","v":"all"},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016","v":"2016"},{"n":"2015","v":"2015"},{"n":"2014-2010","v":"2014t2010"},{"n":"2009-2000","v":"2009t2000"},{"n":"90年代","v":"1990s"}]},{"key":"feature","name":"类型","value":[{"n":"全部","v":"a1"},{"n":"甜蜜互宠","v":"14"},{"n":"虐恋情深","v":"15"},{"n":"青涩校园","v":"16"},{"n":"仙侠玄幻","v":"17"},{"n":"都市职场","v":"19"},{"n":"快意江湖","v":"20"},{"n":"悬疑推理","v":"21"},{"n":"家长里短","v":"22"},{"n":"芒果出品","v":"23"},{"n":"轻松搞笑","v":"24"},{"n":"铁血战争","v":"25"},{"n":"偶像","v":"147"},{"n":"古装","v":"148"},{"n":"其他","v":"26"}]},{"key":"iarea","name":"地区","value":[{"n":"全部","v":"a1"},{"n":"内地","v":"10"},{"n":"港台","v":"12"},{"n":"日韩","v":"11"},{"n":"泰国","v":"193"}]}],"ixigua":[{"key":"cid","name":"分类","value":[{"n":"电视剧","v":"dianshiju"},{"n":"电影","v":"dianying"},{"n":"综艺","v":"zongyi"},{"n":"少儿","v":"shaoer"},{"n":"动漫","v":"dongman"},{"n":"纪录片","v":"jilupian"}]},{"key":"sort","name":"排序","value":[{"n":"综合排序","v":"综合排序"},{"n":"最热","v":"最热"},{"n":"最新","v":"最新"},{"n":"高分","v":"高分"},{"n":"最多评论","v":"最多评论"}]},{"key":"year","name":"年份","value":[{"n":"全部","v":"全部年份"},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016-2011","v":"2016-2011"},{"n":"2010-2000","v":"2010-2000"},{"n":"90年代","v":"90年代"},{"n":"80年代","v":"80年代"},{"n":"更早","v":"更早"}]},{"key":"feature","name":"类型","value":[{"n":"全部类型","v":"全部类型"},{"n":"爱情","v":"爱情"},{"n":"喜剧","v":"喜剧"},{"n":"动作","v":"动作"},{"n":"古装","v":"古装"},{"n":"惊悚","v":"惊悚"},{"n":"武侠","v":"武侠"},{"n":"犯罪","v":"犯罪"},{"n":"战争","v":"战争"},{"n":"奇幻","v":"奇幻"},{"n":"动画","v":"动画"},{"n":"家庭","v":"家庭"},{"n":"伦理","v":"伦理"},{"n":"冒险","v":"冒险"},{"n":"科幻","v":"科幻"},{"n":"历史","v":"历史"}]},{"key":"iarea","name":"地区","value":[{"n":"全部","v":"全部地区"},{"n":"内地","v":"内地"},{"n":"港台","v":"港台"},{"n":"美国","v":"美国"},{"n":"泰国","v":"泰国"},{"n":"日本","v":"日本"},{"n":"韩国","v":"韩国"}]}],"youku":[{"key":"cid","name":"分类","value":[{"n":"电视剧","v":"97"},{"n":"电影","v":"96"},{"n":"综艺","v":"85"},{"n":"动漫","v":"100"},{"n":"纪录片","v":"84"}]},{"key":"sort","name":"排序","value":[{"n":"最多播放","v":"1"},{"n":"最近更新","v":"6"},{"n":"最好评","v":"4"},{"n":"最新上映","v":"5"},{"n":"最多评论","v":"2"}]},{"key":"year","name":"年份","value":[{"n":"全部","v":""},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016","v":"2016"},{"n":"2015","v":"2015"},{"n":"2014","v":"2014"},{"n":"2013","v":"2013"},{"n":"2012","v":"2012"},{"n":"2011","v":"2011"},{"n":"00年代","v":"2000"},{"n":"90年代","v":"1990"},{"n":"80年代","v":"1980"},{"n":"70年代","v":"1970"},{"n":"更早","v":"-1969"}]},{"key":"feature","name":"类型","value":[{"n":"全部","v":""},{"n":"古装","v":"古装"},{"n":"武侠","v":"武侠"},{"n":"警匪","v":"警匪"},{"n":"军事","v":"军事"},{"n":"神话","v":"神话"},{"n":"科幻","v":"科幻"},{"n":"悬疑","v":"悬疑"},{"n":"历史","v":"历史"},{"n":"儿童","v":"儿童"},{"n":"农村","v":"农村"},{"n":"都市","v":"都市"},{"n":"家庭","v":"家庭"},{"n":"搞笑","v":"搞笑"},{"n":"偶像","v":"偶像"},{"n":"时装","v":"时装"},{"n":"优酷出品","v":"优酷出品"}]},{"key":"iarea","name":"地区","value":[{"n":"全部","v":""},{"n":"中国大陆","v":"中国"},{"n":"中国香港","v":"中国香港"},{"n":"中国台湾","v":"中国台湾"},{"n":"韩国","v":"韩国"},{"n":"日本","v":"日本"},{"n":"美国","v":"美国"},{"n":"英国","v":"英国"},{"n":"泰国","v":"泰国"},{"n":"新加坡","v":"新加坡"}]}]}';
    /**
     * @Inject()
     * @var Redis
     */
    protected Redis $redis;
    protected array $type_source;

    public function __construct()
    {
        $this->type_source=array_column(json_decode($this->classes,true),'type_name','type_id');
    }

    /**
     * 爬虫headers
     * @return
     */
    protected function getHeaders(string $url): array
    {
        $headers = [];
        if (!empty($refererUrl)) {
            $headers[] = "Referer: " . $refererUrl;
        }
        $headers[] = "token: " . $this->token_str();
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        $headers[] = "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36";
        return $headers;
    }

    /**
     * @Cacheable(prefix="myad_category", ttl=43200)
     */
    public function category(string $type_id, int $page, string $ext): array
    {

        // 判断必需的参数是否存在
        if (!isset($type_id)) {
            return [];
        }
        if(!$page)$page=1;

        switch ($type_id){
            case 'txsp':
                $res=$this->tx((int)$page, $ext);
                break;
            case 'iqiyi':
                $res=$this->iqiyi((int)$page, $ext);
                break;
            case 'youku':
                $res=$this->youku((int)$page, $ext);
                break;
            case 'bilibili':
                $res=$this->bili((int)$page, $ext);
                break;
            case 'mgtv':
                $res=$this->mgtv((int)$page, $ext);
                break;
            case 'ixigua':
                $res=$this->xigua((int)$page, $ext);
                break;
        }
        return $res;
    }

    public function homeContent()
    {
        $this->token_string=$this->login_token();
        $index_data = $this->curl_post($this->siteUrl.'/video/load_channel', "channel=all&order=new", $this->getHeaders(""));
        $response=json_decode($index_data, true);
        if($response['code']!=200){
            $this->redis->del('myad_token');//失效清空
            //再次获取首页数据
            $index_data = $this->curl_post($this->siteUrl.'/video/load_channel', "channel=all&order=new", ["token: " . $this->login_token(),"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36"]);
            $response=json_decode($index_data, true);
        }
        $arr = $response['alist']??[];
        $result = [];
        $rs=[];
        foreach ($arr as $key => $value) {
            $items = $value["new"];
            foreach ($items as $item) {
                if($item['source']=='youku')continue;
                $data = [];
                $data['vod_id'] = $item['mkey'];
                $data['vod_name'] = $item['title'];
                if (stripos($item['pic'], 'http') !== 0) {
                    $data['vod_pic'] = "http:" . $item['pic'];
                } else {
                    $data['vod_pic'] = $item['pic'];
                }
                $data['vod_remarks'] = $item['status'];
                $rs[] = $data;
            }
        }
        $result['list'] = $rs;
        $result['class'] = json_decode($this->classes, true);
        $result['filters'] = json_decode($this->filterstring, true);
        return $result;
    }

    /**
     * @Cacheable(prefix="myad_detailContent", ttl=43200)
     */
    function detailContent($ids)
    {
        $url = $this->siteUrl.'/collect?url=' . urlencode($ids);
        $location = $this->getLocation($url, $this->getHeaders(""));

        $start = stripos($location, 'album');
        $vstart = stripos($location, 'vplay');
        if ($start > 0) {
            $mkey = substr($location, $start + 6, -5);
            return $this->getdetail($mkey);
        } else if ($vstart > 0) {
            $id = substr($location, $vstart + 6, -5);
            $json = $this->getById($id);
            $mkey = $json -> video -> mkey;
            return $this->getdetail($mkey);
        }else{
            return $this->getdetail($ids);
        }
    }

    /**
     * @Cacheable(prefix="myad_playerContent", ttl=43200)
     */
    public function playerContent($id, $flag)
    {
        $result=[];
        if ($id) {
            $result = [];
            $result['parse'] = "0";
            $json = $this->getById($id);
            $vid = $json->video->vid;
            if(!$vid)return [];
            $url = $this->siteUrl.'/?code=' . $vid . '&lang=&token=ok&sign=fast';
            $m3u8r = $this->curl_get($url, $this->getHeaders(""));
            $m3u8d = json_decode($m3u8r, true);
            if(!isset($m3u8d['url'])){
                $m3u8 = $m3u8d['m3u8']??"";
                // 解密
                $b = (int)substr($vid, 0, 1);
                $ps = "";
                for ($i = strlen($m3u8) - 1; $i >= 0; $i--) {
                    $v = ord($m3u8[$i]) ^ $b;
                    $c = chr($v + 1);
                    $ps = $ps . $c;
                }
                $b64 = base64_decode($ps);
                $content = gzuncompress($b64);
                $host = base64_decode($m3u8d['base']);
                if(strpos($host,'mgtv.com')!==false){
                    $result['header'] = json_encode([
                        "User-Agent"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36",
                        "Origin"=>"https://www.mgtv.com",
                        "Referer"=>"https://www.mgtv.com/"
                    ]);
                }
                if(strpos($host,'cibntv.net')!==false){
                    $result['header'] = json_encode([
                        "Origin" =>"https://www.meiyouad.com",
                    ],256);
                }
                $content = str_replace("d::", $host, $content);
                $result['url'] = 'data:application/vnd.apple.mpegurl;base64,' . base64_encode($content);
            }else{
                $content = $m3u8d['m3u8']??"";
                if(strlen($content)>20){
//                    echo $content;

//                    file_put_contents(BASE_PATH . '/public/'.md5($id).'.mpd',$content);

                    $result['url'] = 'data:application/dash+xml;base64,' . base64_encode($content);
//                    $result['url'] = 'http://home.jundie.top:9520/'.md5($id).'.mpd';
//                    $result['url'] = 'https://dash.akamaized.net/dash264/TestCases/2c/qualcomm/1/MultiResMPEG2.mpd';
                    $result['header'] = json_encode([
                        "User-Agent"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36",
                        "Origin"=>"https://www.bilibili.com",
                        "Referer"=>"https://www.bilibili.com/"
                    ]);
                }else{
                    $result['url'] = $m3u8d['url']??"";
                }
            }

        }
        return json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function searchContent($wd): array
    {
        $url = $this->siteUrl."/video/search";
        $content = $this->curl_post($url, "kw=" . $wd . "&source=all&type=title&order=default&page=1", $this->getHeaders(""));
        $arr = json_decode($content, true)['alist']??[];
        $result = [];
        $rs = [];
        for ($i = 0; $i < count($arr); $i++) {
            $dataj = $arr[$i];
            if($dataj['source']=='youku')continue;
            $item["vod_id"] = $dataj['mkey'];
            $source=$this->type_source[$dataj['source']]??"";
            $item["vod_name"] = $source.' '.$dataj['title'];
            $item["vod_pic"] = "http:" . $dataj['pic'];
            $item["vod_remarks"] = $dataj['status'];
            $rs[] = $item;
        }
        $result['list'] = $rs;
        return $result;
    }


    function bili($pg, $extend)
    {
        $result = [];
        $page = 1;
        if (!empty($pg) && intval($pg) > 1) {
            $page = intval($pg);
        }

        $extend = base64_decode($extend);
        $extend = json_decode($extend, true);

        $year=$extend["year"]??-1;
        $sort = $extend["sort"]??0;
//    $feature=$extend["feature"]??-1;
        $area=$extend["iarea"]??-1;
        $cid=$extend["cid"]??1;
//    $data="season_version=-1&area=$area&is_finish=-1&copyright=-1&season_status=-1&season_month=-1&pub_date=$year&style_id=-1&order=3&order=2&st=5&sort=$sort&page=$page&season_type=1&pagesize=24";
        $data="area=$area&style_id=-1&year=$year&sort=$sort&page=$page&season_type=$cid&pagesize=24";

        $form = 'list=1&data=' . urlencode($data);
        $url = 'https://www.meiyouad.com/pk/bilibili/?cid=' . $cid;
        // 发送 HTTP 请求获取数据
        $catedata = $this->curl_post($url, $form, $this->getHeaders(""));
        $arr = json_decode($catedata, true)['result']['data'];
        // 解析数据并构建返回结果
        $rs=[];
        foreach ($arr as $item) {
            $data = [];
            $data['vod_id'] = $item['link'];
            $data['vod_name'] = $item['title'];
            if (stripos($item['cover'], 'http') !== 0) {
                $data['vod_pic'] = 'http:' . $item['cover'];
            } else {
                $data['vod_pic'] = $item['cover'];
            }
            $data['vod_remarks'] = $item['index_show'];
            $rs[] = $data;
        }

        $result['list'] = $rs;
        $result['page'] = $page;
        $result['pagecount'] = count($arr) == 24 ? $page + 1 : $page;
        $result['limit'] = count($arr);
        $result['total'] = count($arr);

        return $result;
    }

    function mgtv($pg, $extend)
    {
        $result = [];
        $page = 1;
        if (!empty($pg) && intval($pg) > 1) {
            $page = intval($pg);
        }

        $extend = base64_decode($extend);
        $extend = json_decode($extend, true);

        $year=$extend["year"]??'all';
        $sort = $extend["sort"]??'c2';
        $feature=$extend["feature"]??'a1';
        $area=$extend["iarea"]??'a1';
        $cid=$extend["cid"]??1;
        $data="kind=$feature&area=$area&year=$year&sort=$sort&chargeInfo=a1&&pn=$page";

        $form = 'list=1&data=' . urlencode($data);
        $url = 'https://www.meiyouad.com/pk/mgtv/?cid=' . $cid;
        // 发送 HTTP 请求获取数据
        $catedata = $this->curl_post($url, $form, $this->getHeaders(""));
        $arr = json_decode($catedata, true)['data']['hitDocs'];
        // 解析数据并构建返回结果
        foreach ($arr as $item) {
            $data = [];
            $data['vod_id'] = "https://www.mgtv.com/b/".$item['clipId']."/".$item['playPartId'].".html";
            $data['vod_name'] = $item['title'];
            if (stripos($item['img'], 'http') !== 0) {
                $data['vod_pic'] = 'http:' . $item['img'];
            } else {
                $data['vod_pic'] = $item['img'];
            }
            $data['vod_remarks'] = $item['updateInfo'];
            $rs[] = $data;
        }

        $result['list'] = $rs;
        $result['page'] = $page;
        $result['pagecount'] = count($arr) == 80 ? $page + 1 : $page;
        $result['limit'] = count($arr);
        $result['total'] = count($arr);

        return $result;
    }

    function xigua($pg, $extend)
    {
        $result = [];
        $page = 1;
        if (!empty($pg) && intval($pg) > 1) {
            $page = intval($pg);
        }

        $extend = base64_decode($extend);
        $extend = json_decode($extend, true);

        $year=$extend["year"]??'全部年份';
        $sort = $extend["sort"]??'综合排序';
        $feature=$extend["feature"]??'全部类型';
        $area=$extend["iarea"]??'全部地区';
        $cid=$extend["cid"]??'dianshiju';
        $offset=($page-1)*18;
        $data='{"pinyin":"{type}","filters":{"type":"全部","area":"{area}","tag":"{feature}","year":"{year}","sort":"{sort}","paid":"全部资费"},"offset":{offset},"limit":18}';
        $data=str_replace(['{type}','{area}','{feature}','{year}','{sort}','{offset}'],[$cid,$area,$feature,$year,$sort,$offset],$data);
        $form = 'data=' . $data;
//    echo $data;exit;
        $url = 'https://www.meiyouad.com/pk/ixigua/?type=' . $cid;

//    var_dump($data);
        // 发送 HTTP 请求获取数据
        $catedata = $this->curl_post($url, $form, $this->getHeaders(""));
        $arr = json_decode($catedata, true)['data']['albumList'];
        // 解析数据并构建返回结果
        $rs=[];
        foreach ($arr as $item) {
            $data = [];

            $data['vod_id'] = "https://www.ixigua.com/".$item['albumId'];
            $data['vod_name'] = $item['title'];
            $data['vod_pic'] = $item['coverList'][1]['url']??$item['coverList'][0]['url'];
            $data['vod_remarks'] = $item['subTitle'];
            $rs[] = $data;
        }

        $result['list'] = $rs;
        $result['page'] = $page;
        $result['pagecount'] = count($arr) == 18 ? $page + 1 : $page;
        $result['limit'] = count($arr);
        $result['total'] = count($arr);

        return $result;
    }

    function youku($pg, $extend)
    {
        $result = (object)[];
        $page = 1;
        if (!empty($pg) && intval($pg) > 1) {
            $page = intval($pg);
        }

        $extend = base64_decode($extend);
        $extend = json_decode($extend, true);

        if (empty($extend["year"])) {
            $extend["year"] = "";
        }

        if (empty($extend["sort"])) {
            $extend["sort"] = "1";
        }
        if (empty($extend["cid"])) {
            $extend["cid"] = "97";
        }
        if (!empty($extend["feature"])) {
            $extend["feature"] = "";
        }
        if (!empty($extend["iarea"])) {
            $extend["iarea"] = "";
        }

        $data = 'a='.$extend['iarea'].'&g='.$extend['feature'].'&r='.$extend['year'].'&u=&pt=&s='.$extend['sort'].'&&type=show&p=' . $page;
        $form = 'list=1&data=' . urlencode($data);
        $url = 'https://www.meiyouad.com/pk/youku/?cid=' . $extend["cid"];
        // 发送 HTTP 请求获取数据
        $catedata = $this->curl_post($url, $form, $this->getHeaders(""));
        $arr = json_decode($catedata, true)['data'];
        // 解析数据并构建返回结果
        foreach ($arr as $item) {
            $data = (object)[];
            $data->vod_id = 'https://v.youku.com/v_show/id_' .$item['videoId']. '.html';
            $data->vod_name = $item['title'];
            if (stripos($item['img'], 'http') !== 0) {
                $data->vod_pic = 'http:' . $item['img'];
            } else {
                $data->vod_pic = $item['img'];
            }
            $data->vod_remarks = $item['summary'];
            $rs[] = $data;
        }

        $result->list = $rs;
        $result->page = $page;
        $result->pagecount = count($arr) == 84 ? $page + 1 : $page;
        $result->limit = count($arr);
        $result->total = count($arr);

        return $result;
    }

    function iqiyi($pg, $extend)
    {
        $result = [];
        $page = 1;
        if (!empty($pg) && intval($pg) > 1) {
            $page = intval($pg);
        }

        $extend = base64_decode($extend);
        $extend = json_decode($extend, true);

        if (empty($extend["year"])) {
            $extend["year"] = "";
        }

        if (empty($extend["sort"])) {
            $extend["sort"] = "24";
        }
        if (empty($extend["cid"])) {
            $extend["cid"] = "2";
        }

        $mode = "";
        if (!empty($extend["feature"])) {
            $mode =  $mode . $extend["feature"] . ";must,";
        }
        if (!empty($extend["iarea"])) {
            $mode =  $mode . $extend["iarea"] . ";must,";
        }

        $data = 'access_play_control_platform=14&channel_id=' . $extend["cid"] . '&data_type=1&from=pcw_list&is_album_finished=&is_purchase=&key=&market_release_date_level=' . $extend['year'] . '&mode=' . $extend['sort'] . '&pageNum=' . $page . '&pageSize=48&site=iqiyi&source_type=&three_category_id=' . $mode;
        $form = 'list=1&data=' . urlencode($data);
        $url = 'https://www.meiyouad.com/pk/iqiyi/?cid=' . $extend["cid"];
        // 发送 HTTP 请求获取数据
        $catedata = $this->curl_post($url, $form, $this->getHeaders(""));
        $arr = json_decode($catedata, true)['data']['list'];
        // 解析数据并构建返回结果
        $rs=[];
        foreach ($arr as $item) {
            $data = [];
            $data['vod_id'] = $item['playUrl'];
            $data['vod_name'] = $item['name'];
            if (stripos($item['imageUrl'], 'http') !== 0) {
                $data['vod_pic'] = 'http:' . $item['imageUrl'];
            } else {
                $data['vod_pic'] = $item['imageUrl'];
            }
            $data['vod_pic']=str_replace('.jpg', '_255_340.jpg?caplist=jpg,webp', $data['vod_pic']);
            $data['vod_remarks'] = $item['score'];
            $rs[] = $data;
        }

        $result['list'] = $rs;
        $result['page'] = $page;
        $result['pagecount'] = count($arr) == 48 ? $page + 1 : $page;
        $result['limit'] = count($arr);
        $result['total'] = count($arr);

        return $result;
    }


    function tx($pg, $extend)
    {
        $result = [];
        $page = 1;
        if (!empty($pg) && intval($pg) > 1) {
            $page = intval($pg);
        }

        $extend = base64_decode($extend);
        $extend = json_decode($extend, true);

        if (empty($extend["iarea"])) {
            $extend["iarea"] = "-1";
        }

        if (empty($extend["feature"])) {
            $extend["feature"] = "-1";
        }

        if (empty($extend["year"])) {
            $extend["year"] = "-1";
        }

        if (empty($extend["sort"])) {
            $extend["sort"] = "18";
        }

        $form = 'list=1&data=' . urlencode('sort=' . $extend["sort"] . '&feature=' . $extend["feature"] . '&iarea=' . $extend["iarea"] . '&year=' . $extend["year"] . '&pay=-1&listpage=' . $page . '&pagesize=30&offset=' . ($page - 1) * 30);

        if (empty($extend["cid"])) {
            $extend["cid"] = "tv";
        }
        $url = 'https://www.meiyouad.com/pk/txsp/?cid=' . $extend["cid"];
        // 发送 HTTP 请求获取数据
        $catedata = $this->curl_post($url, $form, $this->getHeaders(""));
        $arr = json_decode($catedata, true)['list'];
        // 解析数据并构建返回结果
        foreach ($arr as $item) {
            $data = [];
            $data['vod_id'] = $item['url'];
            $data['vod_name'] = $item['title'];
            if (stripos($item['img'], 'http') !== 0) {
                $data['vod_pic'] = 'http:' . $item['img'];
            } else {
                $data['vod_pic'] = $item['img'];
            }
            $data['vod_remarks'] = $item['summary'];
            $rs[] = $data;
        }

        $result['list'] = $rs;
        $result['page'] = $page;
        $result['pagecount'] = count($arr) == 30 ? $page + 1 : $page;
        $result['limit'] = count($arr);
        $result['total'] = count($arr);

        return $result;
    }


    function getById($id)
    {
        $url = $this->siteUrl.'/video/get_video';
        $token = 'wu24XBT5DnfE2w_3wA0FlLcPIHKfWHSOG7dlRsR8TBxfXvOr4GWvnQobbXA_uejM31aIaMXP42fgPofipiIeUu06sjDJts1Kag8HQbrhXD-_dUOWNrKQg-3EE9XrtxZb';
        $sign = md5($token . "|" . $id . "|" . "album");
        $form = "id=" . $id . "&token=" . $token . "&sign=" . $sign;
        $content = $this->curl_post($url, $form, $this->getHeaders(""));
        return json_decode($content);
    }

    function getdetail($key)
    {
        $url = $this->siteUrl.'/video/get_album';
        $rjson = $this->curl_post($url, "mkey=" . $key, $this->getHeaders(""));
        $rjsono = json_decode($rjson, true);
        $album = $rjsono['album'];
        $result = [];
        $data = [];
        $data['vod_id'] = $album['mkey'];
        $data['vod_name'] = $album['title'];
        $data['vod_pic'] = 'http:' . $album['pic'];
        $data['vod_remarks'] = $album['status'];

        $data['type_name'] = $this->type_source[$album['source']]??"";
        $data['vod_actor'] = join(",", array_values($album['actor']??[]));
        $data['vod_director'] = join("','", array_values($album['director']??[]));
        $data['vod_content'] = $album['desc'];
        if(is_array($album['year'])){
            $data['vod_year'] = join("','", array_values($album['year']??[]));
        }else{
            $data['vod_year'] = $album['year'];
        }
        $data['vod_area'] = join("','", array_values($album['area']??[]));
        $data['vod_play_from'] = "牛逼";
        $eplist = $rjsono['vlist'];
        $playUrl = "";
        foreach ($eplist as $key) {
            $eptitle = $key['title'];
            $epurl = $key['id'];
            $playUrl = $playUrl . $eptitle . "$" . $epurl . "#";
        }
        $pc = substr($playUrl, 0, strlen($playUrl) - 1);

        $data['vod_play_url'] = $pc;

        $rs[] = $data;

        $result['list'] = $rs;
        return $result;
    }


    function getLocation($url, $headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);  //返回重定向url
        curl_exec($curl);
        $return_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL); //获取重定向url
        if (curl_error($curl)) {
            return "Error: " . curl_error($curl);
        } else {
            curl_close($curl);
            return $return_url;
        }
    }

    function curl_get($url, $headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($curl);
        if (curl_error($curl)) {
            return "Error: " . curl_error($curl);
        } else {
            curl_close($curl);
            return $data;
        }
    }

    function curl_post($url, $postdata, $headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($curl);
        $rheader = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        if (curl_error($curl)) {
            return "Error: " . curl_error($curl);
        } else {
            curl_close($curl);
            return $data;
        }
    }

    function login_token($expiredDay=1):string
    {
        $token_string=$this->redis->get('myad_token');
        if($token_string){
            $expire=(int)explode('_',$token_string)[1];
            //还未过期
            if(time()<=$expire){
                return explode('_',$token_string)[0];
            }
        }
        $url = $this->siteUrl.'/user/login';
        $data = [
            'email' => 'ami-1113@outlook.com',
            'passwd' => 'ami123456'
        ];
        $postData = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if(curl_errno($ch)){
            return "";
        }
        // 关闭cURL
        curl_close($ch);
        $response=json_decode($response,true);
        $token=$response['token'];
        if($token){
            $expire=time()+60*60*24*(int)$expiredDay;
            $this->redis->set('myad_token',$token.'_'.$expire);
            return $token;
        }else{
            return  "";
        }
    }

    public function token_str():string
    {
        $token_string=$this->redis->get('myad_token');
        if($token_string){
            $expire=(int)explode('_',$token_string)[1];
            //还未过期
            if(time()<=$expire){
                return explode('_',$token_string)[0];
            }
        }
        return "";
    }


    /**
     * @GetMapping(path="csp_Myad")
     */
    public function index()
    {
        return parent::index();
    }
}

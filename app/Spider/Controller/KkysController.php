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
use Hyperf\Guzzle\ClientFactory;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;


/**
 * Class IndexController
 * @Controller(prefix="spider")
 * @package App\Controller
 */
class KkysController extends SpiderController
{

    protected Client $client;


    private static string $siteUrl = "https://api1.baibaipei.com:8899";
    private static string $devceid;

    public function __construct()
    {
        $this->clientFactory = new ClientFactory($this->container);
        self::$devceid = $this->randStr(33);
    }

    private function postHeaders(): array
    {
        $rand = $this->randStr(32);
        $microtime = microtime(true) * 1000; // 以浮点数形式返回微秒时间戳，并乘以1000
        $time = (string)round($microtime);
        $sign = md5("abcdexxxdd2daklmn25129_" . $time . "_" . $rand);

        return [
            "system-brand" => php_uname('s'),
            "User-Agent" => "okhttp/4.1.0",
            "sign" => $sign,
            "system-model" => php_uname('m'),
            "time" => $time,
            "device-id" => self::$devceid,
            "push-token" => "",
            "version" => "2.1.0",
            "system-version" => php_uname('r'),
            "md5" => $rand,
        ];
    }

    /**
     * @Cacheable(prefix="kkys:classes", ttl=1296000)
     */
    protected function classes(): array
    {
        $response = $this->okhttpString(self::$siteUrl . "/api.php/Index/getTopVideoCategory",[], $this->postHeaders(),"post");

        $jsonData = json_decode($response, true);
        $classes = [];
        $filters = [];
        foreach ($jsonData['data'] as $blockObj) {
            $type_id = $blockObj['nav_type_id'];
            $type_name = $blockObj['nav_name'];
            if (strpos($type_name, '推荐') !== false) {
                continue;
            }
            $classes[] = [
                'type_id' => $type_id,
                'type_name' => $type_name,
            ];
            $filter = $this->okhttp(self::$siteUrl . "/api.php/Video/getFilterType",["type"=>$type_id], $this->postHeaders(),"post")['data']??[];
            $filter_arr=[];
            $arr=[];
            foreach ($filter as $key => $item){
                $typeExtendName = '';
                if ($key === 'plot') $key = 'class';
                switch ($key) {
                    case 'class':
                        $typeExtendName = '类型';
                        break;
                    case 'area':
                        $typeExtendName = '地区';
                        break;
                    case 'lang':
                        $typeExtendName = '语言';
                        break;
                    case 'year':
                        $typeExtendName = '年代';
                        break;
                    case 'sort':
                        $typeExtendName = '排序';
                        break;
                }
                $filter_value=[];
                $filter_arr['key']=$key;
                $filter_arr['name']=$typeExtendName;
                foreach ($item as $sort=>$v){
                    $v_v=$v=='全部'?"0":($key=="sort"?$sort:$v);
                    $filter_value[]=['n'=>$v,'v'=>$v_v];
                }
                $filter_arr['value']=$filter_value;
                $arr[]=$filter_arr;
            }
            if($filter_arr)$filters[$type_id]=$arr;
        }
        return [$classes,$filters];
    }

    public function homeContent(): string
    {
        try {
            $response = $this->OkhttpString(self::$siteUrl . "/api.php/Index/getHomePage",[
                'type' => '1',
                'p' => '1',
            ], $this->postHeaders(),"post");

            $jsonData = json_decode($response, true);
            $videos = [];
            foreach ($jsonData['data']['video'] as $jsonArray) {
                foreach ($jsonArray['list'] as $blockObj) {
                    $videos[] = [
                        'vod_id' => $blockObj['vod_id'],
                        'vod_name' => $blockObj['vod_name'],
                        'vod_pic' => $blockObj['vod_pic'],
                        'vod_remarks' => $blockObj['vod_remarks'].($blockObj["vod_score"]?" ".$blockObj["vod_score"]:""),
                    ];
                }
            }

            [$classes,$filters]=$this->classes();
            $result = [
                'class' => $classes,
                'filters' => $filters,
                'list' => $videos,
            ];

            return json_encode($result);
        } catch (\Throwable $th) {
        }

        return "";
    }


    /**
     * @Cacheable(prefix="kkys:category", ttl=43200)
     */
    public function category(string $tid, int $pg, string $ext): string
    {
        $extend = base64_decode($ext);
        $extend = json_decode($extend, true);
        try {
            $url = self::$siteUrl . "/api.php/Video/getFilterVideoList";
            $hashMap = array(
                "type" => $tid,
                "p" => $pg,
                "sort" => $extend['sort']??"",
                "area" => $extend['area']??"",
                "class" => $extend['class']??"",
                "year" => $extend['year']??""
            );
            $content = $this->okhttpString($url, $hashMap, self::postHeaders(),"post");
            $dataObject = json_decode($content, true)["data"];
            // echo "dat" . $content;
            $jsonArray = $dataObject["data"];
            $videos = array();
            for ($i = 0; $i < count($jsonArray); $i++) {
                $vObj = $jsonArray[$i];
                $v = array();
                $v["vod_id"] = $vObj["vod_id"];
                $v["vod_name"] = $vObj["vod_name"];
                $v["vod_pic"] = $vObj["vod_pic"];
                $v["vod_remarks"] = $vObj["vod_remarks"].($vObj["vod_score"]?" ".$vObj["vod_score"]:"");
                $videos[] = $v;
            }

            $result = array();
            $limit = 12;
            $page = intval($pg);
            $pageCount = count($jsonArray) == $limit ? $page + 1 : $page;
            $result["page"] = $page;
            $result["pagecount"] = $pageCount;
            $result["limit"] = $limit;
            $result["total"] = $pageCount <= 1 ? count($videos) : $pageCount * 20;
            $result["list"] = $videos;
            return json_encode($result);
        } catch (\Throwable $th) {

        }
        return "";
    }


    /**
     * @Cacheable(prefix="kkys:detailContent", ttl=43200)
     */
    public function detailContent($ids): string
    {
        $from=[
            "chaoxing"=>"超星",
            "jp"=>"荐片",
            "lzm3u8"=>"普清",
        ];
        try {
            $url = self::$siteUrl . "/api.php/Video/getVideoInfo";

            $hashMap = array("video_id" => $ids);
            $content = $this->okhttpString($url, $hashMap, self::postHeaders(),"post");
            $dataObject = json_decode($content, true)["data"];
            $vodAtom = array();

            $videoInfo = $dataObject["video"];

            $vodAtom["vod_id"] = $videoInfo["vod_id"];
            $vodAtom["vod_name"] = $videoInfo["vod_name"];
            $vodAtom["vod_pic"] = $videoInfo["vod_pic"];
            //   $vodAtom["type_name"] = $videoInfo["subCategory"];
            $vodAtom["vod_year"] = $videoInfo["vod_year"];
            $vodAtom["vod_area"] = $videoInfo["vod_area"];
            $vodAtom["vod_remarks"] = $videoInfo["vod_remarks"];
            $vodAtom["vod_actor"] = $videoInfo["vod_actor"];
            $vodAtom["vod_director"] = $videoInfo["vod_director"];
            $vodAtom["vod_content"] = trim($videoInfo["vod_content"]);
            $vod_play = array();
            $episodes = $dataObject["video"]["vod_play"];
            for ($i = 0; $i < count($episodes); $i++) {
                $playObject = $episodes[$i];
                $playerForm = $playObject["playerForm"];
                $playerForm =$from[$playerForm]??"官解";
                $playList = "";
                $urlArray = $playObject["url"];
                $vodItems = array();
                for ($j = 0; $j < count($urlArray); $j++) {
                    $urlObject = $urlArray[$j];
                    $title = $urlObject["title"];
                    $playUrl = $urlObject["play_url"];
                    if($playObject["playerForm"]=="jp")$playUrl="tvbox-xg:".$playUrl;
                    $vodItems[] = $title . "$" . $playUrl;
                }
                if (count($vodItems) > 0) {
                    $playList = implode("#", $vodItems);
                }
                if (strlen($playList) == 0) {
                    continue;
                }
                $vod_play[$playerForm] = $playList;
            }
            if (count($vod_play) > 0) {
                $vod_play_from = implode("$$$", array_keys($vod_play));
                $vod_play_url = implode("$$$", array_values($vod_play));
                $vodAtom["vod_play_from"] = $vod_play_from;
                $vodAtom["vod_play_url"] = $vod_play_url;
            }

            $result = array("list" => array($vodAtom));
            return json_encode($result);
        } catch (\Throwable $th) {

        }
        return "";
    }

    /**
     * @Cacheable(prefix="kkys:playerContent", ttl=1296000)
     */
    public function playerContent($id, $flag): string
    {
        try {
            $result = array();
            if (strpos($id, 'jqq-') !== false || strpos($id, 'rr-') !== false) {
                $jqqHeader = $this->okhttpString(self::$siteUrl . "/jqqheader.json", [],self::postHeaders());
                $jqqHeaders = json_decode($jqqHeader, true);
                $jqqHeaderss = array(
                    "clientVersion" => $jqqHeaders["clientVersion"],
                    "clientType" => $jqqHeaders["clientType"],
                    "token" => $jqqHeaders["token"],
                    "User-Agent" => $jqqHeaders["User-Agent"],
                    "aliid" => $jqqHeaders["aliid"],
                    "p" => $jqqHeaders["p"],
                    "pkt" => $jqqHeaders["pkt"]
                );
                $ids = explode("-", $id);
                $jxJqq = $this->okhttpString("https://api.juquanquanapp.com/app/drama/detail?dramaId=" . $ids[1] . "&episodeSid=" . $ids[2] . "&quality=LD",[], $jqqHeaderss);
                $jxjqq = json_decode($jxJqq, true)["data"]["playInfo"];
                $videourl = $jxjqq["url"];
                $headers = array("User-Agent" => "Dalvik/2.1.0 (Linux; U; Android 9; PCRT00 Build/PQ3B.190801.07101926)");
                $result["parse"] = 0;
                $result["playUrl"] = "";
                $result["url"] = $videourl;
                $result["header"] = json_encode($headers);
                return json_encode($result);
            } else if (strpos($id, 'youku') !== false || strpos($id, 'iqiyi') !== false || strpos($id, 'v.qq.com') !== false || strpos($id, 'pptv') !== false || strpos($id, 'le.com') !== false || strpos($id, '1905.com') !== false || strpos($id, 'mgtv') !== false) {
//                $parse = "https://jx.g2.pub/?url=";
//                $connect = $this->okhttpString($parse . $id,[], ["User-Agent"=>"okhttp/3.15.0"]);
//                $videocon = json_decode($connect, true);
//                $videourl = $videocon["url"];
                // echo "cnn" . $videourl;
                $headers = array("User-Agent" => "Dalvik/2.1.0 (Linux; U; Android 9; PCRT00 Build/PQ3B.190801.07101926)");
                $result["parse"] = 1;
                $result["jx"] = 1;
                $result["playUrl"] = "";
                $result["url"] = $id;
                $result["header"] = json_encode($headers);
                return json_encode($result);
            }else if(strpos($id, 'ftp://') !== false){
                $result["parse"] = 0;
                $result["playUrl"] = "";
                $result["url"] = $id;
                return json_encode($result);
            } else {
                $url = self::$siteUrl . "/video.php";
                $hashMap = array("url" => $id);
                $connect = $this->okhttpString($url, $hashMap, self::postHeaders(),"post");
                $playconnect = json_decode($connect, true)["data"];
                $videourl = $playconnect["url"];
                $headers = array(
                    "referer" => "https://mooc2-ans.chaoxing.com/visit/interaction?s=566c917cfb6633ec6b8ed44eecaaeb3f",
                    "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36"
                );
                $result["parse"] = 0;
                $result["playUrl"] = "";
                $result["url"] = $videourl;
                $result["header"] = json_encode($headers);
                return json_encode($result);
            }
        } catch (\Throwable $e) {

        }
        return "";
    }

    public function searchContent($wd): string
    {
        try {
            $url = self::$siteUrl . "/api.php/Search/getSearch";
            $hashMap = array(
                "type_id" => "0",
                "p" => "1",
                "key" => $wd
            );

            $content = $this->okhttpString($url, $hashMap, self::postHeaders(),'post');
            // 注意：这里的OkHttp::post()是假设已经有一个名为OkHttp的类，具有静态post方法用于执行HTTP POST请求

            $dataObject = json_decode($content, true);

            $jsonArray = $dataObject['data']['data'];
            $videos = array();
            foreach ($jsonArray as $vObj) {
                $v = array(
                    "vod_id" => $vObj["vod_id"],
                    "vod_name" => $vObj["vod_name"],
                    "vod_pic" => $vObj["vod_pic"],
                    "vod_remarks" => $vObj["vod_remarks"].($vObj["vod_score"]?" ".$vObj["vod_score"]:"")
                );
                $videos[] = $v;
            }

            $result = array("list" => $videos);
            return json_encode($result);
        } catch (\Throwable $th) {

        }
        return "";
    }

    function randStr($length) {
        $characters = "abacdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ0123456789";
        $randomString = "";

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomChar = $characters[$index];
            $randomString .= $randomChar;
        }

        return $randomString;
    }

    /**
     * @GetMapping(path="csp_Kkys")
     */
    public function index()
    {
        return parent::index();
    }
}

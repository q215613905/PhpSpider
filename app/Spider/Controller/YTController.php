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

use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Utils\ApplicationContext;

/**
 * Class YTController
 * @Controller(prefix="spider")
 * @package App\Controller
 */
class YTController extends SpiderController
{
    protected static string $siteUrl = "";
    protected string $token = "";
    protected string $jxConfig = "";

    protected $classes_str = '{"class":[{"type_id":"2","type_name":"电视剧"},{"type_id":"1","type_name":"电影"},{"type_id":"4","type_name":"综艺"},{"type_id":"3","type_name":"动漫"}],"filters":{"1":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"2":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"3":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"4":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}]}}';

    public function __construct()
    {
        $this->clientFactory = new ClientFactory($this->container);
        self::$siteUrl = $this->api();
    }


    private $uAgent = "Dart/2.14 (dart:io)";

    private function postHeaders()
    {
        $headers = array();
        $headers["User-Agent"] = "okhttp/4.1.0";
        if($this->token){
            $headers["token"] = $this->token;
        }
        return $headers;
    }

    private function getHeaders($url)
    {
        $headers = array();
        $headers["User-Agent"] = $this->uAgent;
        return $headers;
    }

    /**
     * Cacheable(prefix="yt:api", ttl=7200)
     */
    public function api():string
    {
        try {
            $body["version"] = "2.2.230611";
            $encryptedStr = $this->okhttpString("http://lf.lyyytv.cn/api/main/init", $body, $this->postHeaders(),"post");
            $decryptedStr = self::Kydecrypt(str_replace("\"", "", $encryptedStr), "lf.lyyytv.cn");
            $coo = json_decode($decryptedStr, true)["data"];
            $this->jxConfig=$coo["godown"][0]['url'];
            $jsonArray = $coo["docking"];
            $jsonObject = $jsonArray[0];
            return $jsonObject["ext"]?:"";
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return  "";
    }

    /**
     * @Cacheable(prefix="yt:token", ttl=2592000)
     */
    public function token():string
    {
        try {
            $body["account"] = "abctop928";
            $body["username"] = "abctop928";
            $body["password"] = "123456789";
            $body["markcode"] = "abctop928";
            $encryptedStr = $this->okhttpString("http://lf.lyyytv.cn/api/user/login", $body,["User-Agent"=>"okhttp/3.12.11"],"post");
            $decryptedStr = self::Kydecrypt(str_replace("\"", "", $encryptedStr), "lf.lyyytv.cn");
            $coo = json_decode($decryptedStr, true)["data"];
            return $coo["userinfo"]["token"]??"";
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return "";
    }

    /**
     * @Cacheable(prefix="yt:homeContent", ttl=86400)
     */
    public function homeContent()
    {
        $this->token=$this->token();
        try {
            $url = self::$siteUrl . "/nav?token=";
            $classes = array();
            $content = $this->okhttpString($url, [],$this->getHeaders($url));
            $jsonObject = json_decode(self::convertUnicodeToCh($content), true);
            $list = $jsonObject["list"];
            foreach ($list as $item) {
                $type_id = $item["type_id"];
                $type_name = $item["type_name"];
                $class = array("type_id" => $type_id, "type_name" => $type_name);
                $classes[] = $class;
            }
            $result['class'] = $classes;
            $result['list'] = $this->homeVideoContent();
            return json_encode($result);
        } catch (\Exception $ex) {
            return "";
        }
    }

    public function homeVideoContent()
    {
        try {
            $videos = [];

            try {
                $url = self::$siteUrl . "/index_video?token=";
                $content = $this->okhttpString($url,[], $this->getHeaders($url));
                $jsonObject = json_decode(self::convertUnicodeToCh($content), true);
                $jsonArray = $jsonObject["list"];
                foreach ($jsonArray as $item) {
                    $typeName = $item["type_name"];
                    if (
                        strpos($typeName, "福利") !== false ||
                        strpos($typeName, "伦理") !== false ||
                        strpos($typeName, "情色") !== false ||
                        strpos($typeName, "倫理片") !== false
                    ) {
                        continue; // Skip handling data of specific types
                    }
                    $vlist = $item["vlist"];
                    foreach ($vlist as $vod) {
                        $video = array(
                            "vod_id" => $vod["vod_id"],
                            "vod_name" => $vod["vod_name"],
                            "vod_pic" => $vod["vod_pic"],
                            "vod_remarks" => $vod["vod_remarks"]
                        );
                        $videos[] = $video;
                    }
                }
            } catch (\Exception $e) {
            }

            return $videos;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @Cacheable(prefix="yt:category", ttl=86400)
     */
    public function category($tid, $pg, $extend): array
    {
        try {
            $url = self::$siteUrl . "/video?tid=" . $tid . "&class=&area=&lang=&year=&limit=20&pg=" . $pg;
            $content = $this->okhttpString($url, [],$this->getHeaders($url));
            $dataObject = json_decode(self::convertUnicodeToCh($content), true);
            $jsonArray = $dataObject["list"];
            $videos = [];
            foreach ($jsonArray as $vObj) {
                $video = [
                    "vod_id" => $vObj["vod_id"],
                    "vod_name" => $vObj["vod_name"],
                    "vod_pic" => $vObj["vod_pic"],
                    "vod_remarks" => $vObj["vod_remarks"]
                ];
                $videos[] = $video;
            }
            $result = [];
            $limit = 18;
            $page = $dataObject["page"];
            $total = $dataObject["total"];
            $result["page"] = $page;
            $result["pagecount"] = intval($total / 18);
            $result["limit"] = $limit;
            $result["total"] = $total;
            $result["list"] = $videos;
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @Cacheable(prefix="yt:detailContent", ttl=86400)
     */
    public function detailContent($ids): array
    {
        try {
            $url = self::$siteUrl . "/video_detail?id=" . $ids;
            $content = $this->okhttpString($url,[], $this->getHeaders($url));
            $dataObject = json_decode(self::convertUnicodeToCh($content), true);
            $vObj = $dataObject["data"];

            $result = [];
            $list = [];

            $vodAtom = [];
            $vodAtom["vod_id"] = $vObj["vod_id"];
            $vodAtom["vod_name"] = $vObj["vod_name"];
            $vodAtom["vod_pic"] = $vObj["vod_pic"];
            $vodAtom["vod_class"] = $vObj["vod_class"];
            $vodAtom["vod_year"] = $vObj["vod_year"];
            $vodAtom["vod_area"] = $vObj["vod_area"];
            $vodAtom["vod_remarks"] = $vObj["vod_remarks"];
            $vodAtom["vod_actor"] = $vObj["vod_actor"];
            $vodAtom["vod_director"] = $vObj["vod_director"];
            $vodAtom["vod_content"] = str_replace("【影探=lfytv.cn】","",trim($vObj["vod_content"]));
            $playfrom = $vObj["vod_play_from"];
            $vod_play_from = str_replace(
                ["ffm3u8", "bfzym3u8", "YT_4k", "TY-4K","YTyun-4K","TYyun-4K", "qiyi", "youku", "qq", "mgtv"],
                ["非凡", "暴风", "4K_1线","4K_2线", "云4K_1线", "云4K_2线","奇艺", "优酷", "腾讯", "芒果"],
                $playfrom
            );
            $vod_play_from=array_reverse(explode("$$$",$vod_play_from));
            $vodAtom["vod_play_from"] = implode("$$$",$vod_play_from);
            $play_urls=$vObj["vod_play_url"];
            if(strpos($play_urls,'lfytv.cn')){
                $play_urls=preg_replace('/影探lfytv\.cn\$http:\/\/.*?\.mp4#/', "",$play_urls);
            }
            $play_urls=array_reverse(explode("$$$",$play_urls));
            $vodAtom["vod_play_url"] = implode("$$$",$play_urls);
            $list[] = $vodAtom;
            $result["list"] = $list;
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @Cacheable(prefix="yt:getJxUrls", ttl=86400)
     */
    public function getJxUrls()
    {
        $json=$this->okhttp($this->jxConfig, [], $this->postHeaders());
        $parses=$json['parses'];
        $urls=[];
        foreach ($parses as $item){
            if($item['type']==1 && str_contains($item['name'],"影探")){
                $urls[]=$item['url'];
            }
        }
        return $urls;
    }

    public function playerContent($id,$flag): array
    {
        try {
            $result = array();
            $ua = array();
            $ua["User-Agent"] = "okhttp/4.11.0";
            if (in_array($flag,["4K_1线"])) {
                $result["parse"] = 0;
                $result["playUrl"] = "";
                $jxUrls=$this->getJxUrls();

                foreach ($jxUrls as $k=>$jxUrl){
                    if($k==0)continue;
                    $data=$this->okhttp($jxUrl.$id,[]);
                    var_dump($data);
                    if($data['url']){
                        $id=$data['url'];
                        break;
                    }
                }

//                $data=$this->okhttp("http://192.168.31.69:666/jx.php?config=".$this->jxConfig."&url=".$id,[]);
//                $url=$data['url']??"";
                $result["url"] = $id;
            }
            else if (in_array($flag,["奇艺", "优酷", "腾讯", "芒果"])) {
                $result["parse"] = 1;
                $result["jx"] = "1";
                $result["playUrl"] = "";
                $result["url"] = $id;
            } else {
                $result["parse"] = 0;
                $result["playUrl"] = "";
                $result["header"] = $ua;
                $result["url"] = $id;
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function searchContent($key): array
    {
        try {
            $url = self::$siteUrl . "/search?text=" . urlencode($key) . "&pg=";
            $content = $this->okhttpString($url, [],$this->getHeaders($url));
            $dataObject = json_decode(self::convertUnicodeToCh($content), true);
            $jsonArray = $dataObject["list"];
            $videos = [];
            foreach ($jsonArray as $vObj) {
                $video = array(
                    "vod_id" => $vObj["vod_id"],
                    "vod_name" => $vObj["vod_name"],
                    "vod_pic" => $vObj["vod_pic"],
                    "vod_remarks" => $vObj["vod_remarks"]
                );
                $videos[] = $video;
            }
            return ["list" => $videos];
        } catch (\Exception $e) {
            return [];
        }
    }


    private static function convertUnicodeToCh($str)
    {
        $pattern = '/(\\\\u(\\w{4}))/';
        $str = preg_replace_callback($pattern, function ($matches) {
            return mb_convert_encoding(pack('H*', $matches[2]), 'UTF-8', 'UTF-16');
        }, $str);
        return $str;
    }

    public static function Kydecrypt($arg12, $arg13)
    {
        $v0 = null;
        if ($arg12 !== null && $arg13 !== null) {
            $v1 = strlen($arg12) / 2;
            $v2 = [];
            $v12 = str_split($arg12);
            $v3 = 0;
            for ($v4 = 0; $v4 < $v1; ++$v4) {
                $v6 = $v4 * 2;
                $v7 = $v12[$v6];
                $v2[$v4] = (hexdec("0x" . $v12[$v6 + 1]) ^ ((hexdec("0x" . $v7) << 4) & 0xFF));
            }

            $v12_1 = str_split($arg13);
            $v4_1 = [];
            for ($v6_1 = 0; $v6_1 < 0x100; ++$v6_1) {
                $v4_1[$v6_1] = $v6_1;
            }

            if ($v12_1 !== null && count($v12_1) !== 0) {
                $v0_1 = 0;
                $v6_2 = 0;
                $v7_1 = 0;
                while ($v0_1 < 0x100) {
                    $v7_1 = (ord($v12_1[$v6_2]) & 0xFF) + ($v4_1[$v0_1] & 0xFF) + $v7_1 & 0xFF;
                    $v8 = $v4_1[$v0_1];
                    $v4_1[$v0_1] = $v4_1[$v7_1];
                    $v4_1[$v7_1] = $v8;
                    $v6_2 = ($v6_2 + 1) % count($v12_1);
                    ++$v0_1;
                }

                $v0 = $v4_1;
            }

            $v12_2 = [];
            $v13 = 0;
            $v4_2 = 0;
            while ($v3 < $v1) {
                $v13 = ($v13 + 1) & 0xFF;
                $v4_2 = ($v0[$v13] & 0xFF) + $v4_2 & 0xFF;
                $v6_3 = $v0[$v13];
                $v0[$v13] = $v0[$v4_2];
                $v0[$v4_2] = $v6_3;
                $v12_2[$v3] = ($v0[($v0[$v13] & 0xFF) + ($v0[$v4_2] & 0xFF) & 0xFF] ^ $v2[$v3]);
                ++$v3;
            }

            return implode(array_map("chr", $v12_2));
        }

        return null;
    }

    /**
     * @GetMapping(path="csp_YT")
     */
    public function index()
    {
        return parent::index();
    }
}

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
class JPController extends SpiderController
{

    protected Client $client;
    protected string $siteUrl = "http://yjpapipxblwdohpakljwg.hxhzs.com";

    protected $classes_str = '{"class":[{"type_id":"2","type_name":"电视剧"},{"type_id":"1","type_name":"电影"},{"type_id":"4","type_name":"综艺"},{"type_id":"3","type_name":"动漫"}],"filters":{"1":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"2":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"3":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"4":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}]}}';

    public function __construct()
    {
        $this->clientFactory = new ClientFactory($this->container);
    }

    /**
     * @param $cover
     * @param $id
     * @param $title
     * @param $remark
     * @param array $videos
     * @return array
     */
    public static function getVideo($cover, $id, $title, $remark): array
    {
        if ($cover) $cover = self::picture($cover);
        $item["vod_id"] = $id . "$$$" . $title . "$$$" . $cover;
        $item["vod_name"] = $title;
        $item["vod_pic"] = $cover;
        $item["vod_remarks"] = $remark;
        return $item;
    }

    /**
     * 爬虫headers
     * @return
     */
    protected function getHeaders(string $url): array
    {
        $headers["User-Agent"] = "jianpian-android/362";
        $headers["JPAUTH"] = "n6wrYkef3DDRjHeiQqimMJyoInZ6VNBUnyzDaOGZ4Jfx";
        return $headers;
    }

    /**
     * @Cacheable(prefix="jp_category", ttl=43200)
     */
    public function category(string $type_id, int $page, string $ext): array
    {
        $res = [];
        if (!$page) $page = 1;
        try {
            $extend = base64_decode($ext);
            $extend = json_decode($extend, true);
            $url = $this->siteUrl . "/api/crumb/list?area=0&code=unknown66e77c26fa3b1b31&category_id={cateId}&year={year}&limit=24&channel=wandoujia&page=" . $page . "&sort={sort}&type=0";;
            $url = str_replace(["{cateId}", "{year}", "{sort}"], [$type_id, $extend["year"] ?? "", $extend["sort"] ?? ""], $url);
            $json = $this->okhttp($url,[], $this->getHeaders($url));
            $list = $json['data'] ?? [];
            $videos = [];
            foreach ($list as $k => $v) {
                $title = $v["title"] ?? "";
                $cover = $v["path"] ?? "";
                $id = $v["id"] ?? "";
                $remark = $v["playlist"]["title"] ?? "";
                $remark .= $v["score"] ? " ".$v['score']:"";
                $videos[] = self::getVideo($cover, $id, $title, $remark);
            }
            $res['list'] = $videos;
            $res['page'] = $page;
            $res['pagecount'] = count($list) == 24 ? $page + 1 : $page;;
            $res['limit'] = 24;
            $res['total'] = 24;
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        return $res;
    }

    public function homeContent()
    {
        $res = json_decode($this->classes_str, true);
        try {
            $url = $this->siteUrl . "/api/tag/hand?code=unknown66e77c26fa3b1b31&channel=wandoujia";
            $json = $this->okhttp($url,[], $this->getHeaders($url));
            $list = $json['data'][0]['video'] ?? [];
            $videos = [];
            foreach ($list as $k => $v) {
                $title = $v["title"] ?? "";
                $cover = $v["path"] ?? "";
                $id = $v["id"] ?? "";
                $remark = $v["playlist"]["title"] ?? "";
                $remark .= $v["score"] ? " ".$v['score']:"";
                $videos[] = self::getVideo($cover, $id, $title, $remark);
            }
            $res['list'] = $videos;
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        return $res;
    }

    /**
     * @Cacheable(prefix="jp_detailContent", ttl=43200)
     */
    public function detailContent($ids): array
    {
        $res = [];
        try {
            $idInfo = explode("$$$", $ids);
            $url = $this->siteUrl . "/api/node/detail?channel=wandoujia&id=" . $idInfo[0];
            $json = $this->okhttp($url,[], $this->getHeaders($url));
            $data = $json['data'] ?? [];
            $year = $data['year']['title'] ?? "";
            $area = $data['area']['title'] ?? "";
            $actors = $data['actors'] ?? "";
            $directors = $data['directors'] ?? "";
            if($directors)$directors=implode("|",array_column($directors,'name'));
            if($actors)$actors=implode("|",array_column($actors,'name'));
            if(is_array($actors))$actors="";
            if(is_array($directors))$directors="";
            $desc = $data['description'] ?? "";
            $video["vod_year"] = $year;
            $video["vod_area"] = $area;
            $video["vod_id"] = $ids;
            $video["vod_name"] = $idInfo[1];
            $video["vod_pic"] = self::picture($idInfo[2]);
            $video["vod_content"] = $desc;
            $video["type_name"] = "";
            $video["vod_remarks"] = "";
            $video["vod_actor"] = mb_substr($actors,0,20)."...";
            $video["vod_director"] = $directors;

            $m3u8 = $data['m3u8_downlist'] ?? [];
            $m3u82 = $data['new_m3u8_list'] ?? [];
            $play_from = [];
            $play_list = [];
            if ($m3u8) {
                $downlist = [];
                foreach ($m3u8 as $item) {
                    $name = $item["title"] ?? "";
                    $url = $item["url"] ?? "";
                    $downlist[] = $name . '$tvbox-xg:' . $url;
                }
                if ($downlist) {
                    $play_from[] = "p2p超清";
                    $play_list[] = implode("#", $downlist);
                }
            }
            if ($m3u82) {
                $downlist = [];
                foreach ($m3u8 as $item) {
                    $name = $item["title"] ?? "";
                    $url = $item["url"] ?? "";
                    $downlist[] = $name . '$' . $url;
                }
                if ($downlist) {
                    $play_from[] = "普清";
                    $play_list[] = implode("#", $downlist);
                }
            }

            $video["vod_play_from"] = implode("$$$", $play_from);
            $video["vod_play_url"] = implode("$$$", $play_list);

            $res['list'] = [$video];
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $res;
    }

    public function playerContent($play, $flag): array
    {
        $res = [];
        try {
            $res['url'] = $play;
            if (strpos($play, "tvbox-xg:") !== false) {
                $res['parse'] = 0;
            } else {
                $res['parse'] = 1;
                $res['jx'] = 0;
            }
            $res['playUrl'] = "";
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $res;
    }

    public function searchContent($wd): array
    {
        $res=[];
        try {
            $url = $this->siteUrl."/api/video/search?page=1&key=".urlencode($wd);
            $json = $this->okhttp($url,[], $this->getHeaders($url));
            $data = $json['data'] ?? [];
            $videos = [];
            foreach ($data as $v) {
                $title = $v["title"] ?? "";
                $cover = $v["thumbnail"] ?? "";
                $id = $v["id"] ?? "";
                $remark = $v["mask"] ?? "";
                $remark .= $v["score"] ? " ".$v['score']:"";
                $videos[] = self::getVideo($cover, $id, $title, $remark);
            }
            $res['list']=$videos;
        } catch (\Exception $e) {
        echo $e->getMessage();
    }
        return $res;
    }

    protected static function picture(string $cover): string
    {
        // $headers=[
        //     "Referer"=>"www.jianpianapp.com",
        //     "User-Agent"=>"jianpian-version353",
        // ];
        return $cover . "@Referer=www.jianpianapp.com@User-Agent=jianpian-version353";
        // return $cover . urlencode("@Headers=".json_encode($headers);
    }

    /**
     * @GetMapping(path="csp_JP")
     */
    public function index()
    {
        return parent::index();
    }
}

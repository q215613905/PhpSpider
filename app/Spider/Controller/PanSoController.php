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

use DiDom\Document;
use GuzzleHttp\Client;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use QL\QueryList;
use Symfony\Component\HttpClient\HttpClient;


/**
 * Class IndexController
 * @Controller(prefix="spider")
 * @package App\Controller
 */
class PanSoController extends AliYunController
{
//    /**
//     * @Inject()
//     * @var \Goutte\Client
//     */
    protected string $siteURL = "https://www.alipansou.com";
    protected string $pregUrl = '/(https:\/\/www\.aliyundrive\.com\/s\/[^"]+)/i';


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 爬虫headers
     * @return
     */
    protected function getHeaders(string $url): array
    {
        $headers["User-Agent"] = $this->CHROME_UA;
        $headers["Referer"] = $this->siteURL.$url;
        $headers["_bid"] = "6d14a5dd6c07980d9dc089a693805ad8";
        return $headers;
    }


    public function homeContent()
    {
        $res = [];

        return $res;
    }

    /**
     * Cacheable(prefix="jp_detailContent", ttl=1296000)
     */
    public function detailContent($ids): array
    {
        $url = $this->getAliUrl($ids);
        $rel1=parent::detailContent($url);
        $rel1['list'][0]['vod_id']=$ids;

        return $rel1;
    }

    /**
     * Cacheable(prefix="panso:getAliUrl", ttl=172800)
     */
    protected function getAliUrl($ids){
        preg_match($this->pregUrl, $ids, $matches);
        if($matches){
            return $matches[0];
        }else{
            $header = $this->okhttpHeader($this->siteURL . str_replace("/s/","/cv/",$ids),[],$this->getHeaders($ids));
            return $header['location'];
        }
    }


    /**
     * Cacheable(prefix="ali:playerContent", ttl=1296000)
     */
    public function playerContent($play, $flag): array
    {
        return parent::playerContent($play,$flag);
    }

    /**
     * @Cacheable(prefix="psou:searchContent", ttl=43200)
     */
    public function searchContent($wd): array
    {
        $json=[];
        try {
            echo $this->siteURL . "/search?k=" . urlencode($wd) . "&page=" . 1 . "&s=0&t=-1";

            $html = $this->okhttpString($this->siteURL . "/search?k=" . urlencode($wd) . "&page=" . 1 . "&s=0&t=-1");

            $document=new Document($html);
            $items = $document->find('van-row > a');

            $videos=[];
            foreach($items as $a) {
                $title=$a->first('template')->text();
                $title=trim($title);
                $item["vod_id"] = $a->attr("href");
                $item["vod_name"] = $title;
                $item["vod_pic"] = "https://www.alipansou.com/img/folder.png";
                $item["vod_remarks"] = "盘搜";
                $videos[]=$item;
            }
            return ['list'=>$videos];
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $json;
    }


    /**
     * GetMapping(path="csp_PSou")
     */
    public function index()
    {
        return parent::index();
    }
}

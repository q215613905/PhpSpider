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
use Hyperf\Guzzle\ClientFactory;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Utils\Exception\ParallelExecutionException;
use Hyperf\Utils\Parallel;
use QL\QueryList;


/**
 * Class IndexController
 * @Controller(prefix="ali")
 * @package App\Controller
 */
class AliController extends AliYunController
{
    protected string $pregUrl = '/(https:\/\/www\.aliyundrive\.com\/s\/[^"]+)/i';

    public function __construct()
    {
        parent::__construct();

    }

    public function homeContent()
    {
        $res = ["阿里云盘聚合搜索(swoole_cli)"];

        return $res;
    }

    /**
     * @GetMapping(path="csp_detail")
     */
    public function detail(){
        $url=$this->request->query("url");
        return $this->detailContent($url);
    }

    /**
     * @GetMapping(path="csp_play")
     */
    public function play(){
        $url=$this->request->query("url");
        return $this->playerContent($url,"");
    }

    /**
     * 爬虫headers
     * @return
     */
    protected function getHeadersPsou(string $url): array
    {
        $siteURL = "https://www.alipansou.com";
        $headers["User-Agent"] = $this->CHROME_UA;
        $headers["Referer"] = $siteURL.$url;
        $headers["_bid"] = "6d14a5dd6c07980d9dc089a693805ad8";
        return $headers;
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
     * Cacheable(prefix="ali:getAliUrl", ttl=172800)
     */
    protected function getAliUrl($ids){
        preg_match($this->pregUrl, $ids, $matches);
        if($matches){
            return $matches[0];
        }else{
            $siteURL = "https://www.alipansou.com";
            $header = $this->okhttpHeader($siteURL . str_replace("/s/","/cv/",$ids),[],$this->getHeadersPsou($ids));
            return $header['location'];
        }
    }


    protected function getHeadersPanse(bool $bool,$url=""): array
    {
        $headers["User-Agent"] = $this->CHROME_UA;
        if($bool){
            $headers["x-nextjs-data"] = "1";
            $headers["referer"] = $url;
        }
        return $headers;
    }

    /**
     * @Cacheable(prefix="msou:searchContent", ttl=86400)
     */
    public function searchContentMsou($wd): array
    {
        $site_url="https://www.pansearch.me/";
        $html = $this->okhttpString($site_url,[],$this->getHeadersPanse(false));

        $document=new Document($html);
        $items= $document->find("#__NEXT_DATA__")[0]->text();
        $buildId = json_decode($items, true)['buildId'];
        $url = $site_url . "_next/data/" . $buildId . "/search.json?keyword=" . urlencode($wd) . "&pan=aliyundrive";
        $result = $this->okhttpString($url,[],$this->getHeadersPanse(true,$site_url));
        $array = json_decode($result, true)['pageProps']['data']['data'];
        $list = [];
        foreach ($array as $item) {
            $content = $item['content'];
            $split = preg_split('/\n/', $content);
            if (count($split) == 0) continue;
            $doc = new Document($content);
            $vodId = $doc->find('a')[0]->attr('href');
            $name = strip_tags($split[0]);
            $remark = $item['time'];
            $remark = date("Y-m-d H:i:s",strtotime($remark));
            $pic = $item['image'];
            $list[] = ['vod_id' => $vodId, 'vod_name' => str_replace(["名称："," "],["",""],$name), 'vod_pic' => $pic, 'vod_remarks' => "喵搜"];
        }

        return ['list'=>$list];
    }

    /**
     * @Cacheable(prefix="dovx:searchContent", ttl=86400)
     */
    public function searchContentSeven($wd): array
    {
        $siteURL = "https://api.dovx.tk/ali/search";
        $json=[];
        try {
            $json = $this->okhttp($siteURL . "?wd=" . urlencode($wd), ["User-Agent"=>$this->CHROME_UA]);
            if($json['list']){
                foreach ($json['list'] as $k=>$video){
                    $json['list'][$k]['vod_id']=$video['vod_content'];
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $json;
    }

    /**
     * @Cacheable(prefix="psou:searchContent", ttl=43200)
     */
    public function searchContentPsou($wd): array
    {
        $siteURL = "https://www.alipansou.com";
        $json=[];
        try {
            $html = $this->okhttpString($siteURL . "/search?k=" . urlencode($wd) . "&page=" . 1 . "&s=0&t=-1");

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

    public function searchContent($wd): array
    {
        sleep(1);
        $parallel = new Parallel();
        $parallel->add(function ()use ($wd) {
            return $this->searchContentMsou($wd)['list']??[];
        },"msou");
        $parallel->add(function ()use ($wd) {
            return $this->searchContentSeven($wd)['list']??[];
        },"dovx");
        $parallel->add(function ()use ($wd) {
            return $this->searchContentPsou($wd)['list']??[];
        },"psou");

        $results=[];
        try{
            // $results 结果为 [1, 2]
            $results = $parallel->wait();
        } catch(ParallelExecutionException $e){
            // $e->getResults() 获取协程中的返回值。
            // $e->getThrowables() 获取协程中出现的异常。
            $results=$e->getResults();
        }
        $lists=[];
        foreach ($results as $key => $result){
            if($result){
                foreach ($result as $item){
//                    $arr=explode(' ',$item['vod_remarks']);
                    if($item && !$item['vod_remarks']){
                        $item['vod_remarks'].=' 七夜搜';
                    }
                    $lists[]=$item;
                }
            }
        }
        return ['list'=>$lists];
    }

    /**
     * @GetMapping(path="cli_Ali")
     */
    public function index()
    {
        return parent::index();
    }
}

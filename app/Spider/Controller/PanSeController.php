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
use function _PHPStan_b8e553790\React\Promise\Stream\first;


/**
 * Class IndexController
 * @Controller(prefix="spider")
 * @package App\Controller
 */
class PanSeController extends AliYunController
{
//    /**
//     * @Inject()
//     * @var \Goutte\Client
//     */
    protected string $siteURL = "https://www.pansearch.me/";
    protected string $pregUrl = '/(https:\/\/www\.aliyundrive\.com\/s\/[^"]+)/i';


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 爬虫headers
     * @return
     */
    protected function getHeaders(bool $bool): array
    {
        $headers["User-Agent"] = $this->CHROME_UA;
        if($bool){
            $headers["x-nextjs-data"] = "1";
            $headers["referer"] = $this->siteURL;
        }
        return $headers;
    }


    public function homeContent()
    {
        $res = [];

        return $res;
    }

    /**
     * Cacheable(prefix="panse:detailContent", ttl=1296000)
     */
    public function detailContent($ids): array
    {
        return parent::detailContent($ids);
    }

    /**
     * Cacheable(prefix="ali:playerContent", ttl=1296000)
     */
    public function playerContent($play, $flag): array
    {
        return parent::playerContent($play,$flag);
    }

    /**
     * @Cacheable(prefix="msou:searchContent", ttl=43200)
     */
    public function searchContent($wd): array
    {
        $html = $this->okhttpString($this->siteURL,[],$this->getHeaders(false));

        $document=new Document($html);
        $items= $document->find("#__NEXT_DATA__")[0]->text();
        $buildId = json_decode($items, true)['buildId'];
        $url = $this->siteURL . "_next/data/" . $buildId . "/search.json?keyword=" . urlencode($wd) . "&pan=aliyundrive";
        $result = $this->okhttpString($url,[],$this->getHeaders(true));
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
            $list[] = ['vod_id' => $vodId, 'vod_name' => str_replace(["名称："," "],["",""],$name), 'vod_pic' => $pic, 'vod_remarks' => $remark];
        }

        return ['list'=>$list];
    }


    /**
     * GetMapping(path="csp_MSou")
     */
    public function index()
    {
        return parent::index();
    }
}

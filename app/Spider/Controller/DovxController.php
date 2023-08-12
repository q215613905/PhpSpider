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
use QL\QueryList;


/**
 * Class IndexController
 * @Controller(prefix="spider")
 * @package App\Controller
 */
class DovxController extends AliYunController
{

    protected string $siteURL = "https://api.dovx.tk/ali/search";
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
        return $headers;
    }

    /**
     * Cacheable(prefix="jp_category", ttl=43200)
     */
    public function category(string $type_id, int $page, string $ext): array
    {
        $res = [];
        if (!$page) $page = 1;
        return $res;
    }

    public function homeContent()
    {
        $res = [];

        return $res;
    }

    /**
     * Cacheable(prefix="ali:detailContent", ttl=1296000)
     */
    public function detailContent($ids)
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
     * @Cacheable(prefix="dovx:searchContent", ttl=86400)
     */
    public function searchContent($wd): array
    {
        $json=[];
        try {
            $json = $this->okhttp($this->siteURL . "?wd=" . urlencode($wd), ["User-Agent"=>$this->CHROME_UA]);
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
     * GetMapping(path="csp_Dovx")
     */
    public function index()
    {
        return parent::index();
    }
}

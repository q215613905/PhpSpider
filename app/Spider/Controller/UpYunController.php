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
class UpYunController extends AliYunController
{

    protected string $siteURL = "https://zyb.upyunso.com";
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
        $res = ['hello word'];

        return $res;
    }

    /**
     * Cacheable(prefix="upyun:searchContent", ttl=43200)
     */
    public function searchContent($wd): array
    {
        $json=[];
        try {
            $json = $this->okhttpString($this->siteURL . "/v15/search?keyword=" . urlencode($wd),[],["Referer"=>"zyb.upyunso.com/"]);
            $items=json_decode($this->upYun_decrypt($json),true)['result']['items']??[];
            $vedios=[];
            var_dump(json_decode($this->upYun_decrypt($json),true));
            foreach ($items as $item){
               $url=$this->upYun_decrypt($item['page_url']);
                preg_match($this->pregUrl, $url, $matches);
                if ($matches) {
                    $url = $matches[0];
                    $vedio=[];
                    if(strpos($item['title'],$wd)!==false){
                        $vedio['vod_id']=$url;
                        $title=str_replace("<em>","",$item['title']);
                        $title_arr=explode("</em>",$title);
                        $result = $title_arr[0];
                        $vedio['vod_name']=$result;
                        $vedio['vod_pic']="https://www.upyunso.com/static/img/filetype/filetype_video.png";
                        $vedio['vod_remarks']=$item['insert_time'];
                        $vedios[substr(strrchr($url,"/"),1)]=$vedio;
                    }
                }
            }
            return ['list'=>array_values($vedios)];
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return [];
    }


    protected function upYun_decrypt($data,$key="qq1920520460qqzz",$iv="qq1920520460qqzz"): string
    {
        // 对密文进行 base64 解码
//        $data=strtoupper($data);
        $decodedCiphertext = hex2bin($data);
        return openssl_decrypt($decodedCiphertext,  'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @GetMapping(path="csp_UpYun12")
     */
    public function index()
    {
        return parent::index();
    }
}

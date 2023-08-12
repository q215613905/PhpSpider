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
class LezhuController extends SpiderController
{

    protected Client $client;
    protected QueryList $ql;
    protected string $siteUrl = 'http://127.0.0.1:666/psp/lezhu.php';
    protected string $webUrl = 'http://www.lezhutv.com';
    protected string $UserAgent = 'Mozilla/5.0 (Linux; Android 12; M2102K1AC Build/SKQ1.211006.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/97.0.4692.98 Mobile Safari/537.36 T7/13.26 SP-engine/2.64.0 baiduboxapp/13.26.0.10 (Baidu; P1 12) NABar/1.0';

    protected $classes_str = '{"class":[{"type_id":"1","type_name":"电影"},{"type_id":"12","type_name":"国产剧"},{"type_id":"13","type_name":"港台剧"},{"type_id":"14","type_name":"韩国剧"},{"type_id":"15","type_name":"美国剧"},{"type_id":"24","type_name":"日本剧"},{"type_id":"25","type_name":"台湾剧"},{"type_id":"26","type_name":"泰国剧"},{"type_id":"5","type_name":"动作片"},{"type_id":"6","type_name":"喜剧片"},{"type_id":"7","type_name":"爱情片"},{"type_id":"8","type_name":"科幻片"},{"type_id":"9","type_name":"恐怖片"},{"type_id":"10","type_name":"剧情片"},{"type_id":"11","type_name":"战争片"},{"type_id":"18","type_name":"动画片"},{"type_id":"20","type_name":"犯罪片"},{"type_id":"3","type_name":"综艺"},{"type_id":"4","type_name":"动漫"}]}';

    public function __construct()
    {
        $this->clientFactory = new ClientFactory($this->container);
        $this->ql=new QueryList();
    }

    /**
     * 爬虫headers
     * @return
     */
    protected function getHeaders(string $url): array
    {
        $header = [
            'Referer' => $url,
            'User-Agent' => $this->UserAgent
        ];
        return $header;
    }


    public function homeContent()
    {
        $html = [];
        try {
            $html = $this->okhttp($this->siteUrl, [], $this->getHeaders($this->siteUrl));
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        return $html;
    }

    /**
     * @Cacheable(prefix="lezhu:category", ttl=43200)
     */
    public function category(string $type_id, int $page, string $ext): string
    {
        $html = "";

        if (!$page) $page = 1;
        try {
            $params = [
                't' => $type_id,
                'pg' => $page,
                'ext' => $ext
            ];
            $url = $this->siteUrl . '?'.http_build_query($params);
            $html = $this->okhttpString($url, [], $this->getHeaders($this->siteUrl));
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        return $html;
    }


    /**
     * @Cacheable(prefix="lezhu:detailContent", ttl=43200)
     */
    public function detailContent($ids): string
    {
        $html = "";
        try {
            $params = [
                'ids' => $ids,
            ];
            $url = $this->siteUrl . '?'.http_build_query($params);
            $html = $this->okhttpString($url, [], $this->getHeaders($this->siteUrl));
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        return $html;
    }

    /**
     * @Cacheable(prefix="lezhu:playerContent", ttl=7200)
     */
    public function playerContent($play, $flag):array
    {
        $res = [];
        try {
            $html = $this->okhttpString($play, [], $this->getHeaders($this->siteUrl));
            $path1=$this->getSubstr($html,"var view_path = '","';");
            $html2 = $this->okhttpString($this->webUrl."/hls2/index.php?url=".$path1, [], $this->getHeaders($this->siteUrl));
            $ql = $this->ql->html($html2);
            $all_script=$ql->find('script')->text();
            $json=$this->getSubstr($all_script,"var response = '{","}'");
//            echo "{".$json."}";
            $jsonARR=json_decode("{".$json."}",true);
            if($jsonARR){
                $url=$jsonARR['media']['url']??"";
                if($url){
                    $res['parse']=0;
                    $res['url']=$url;
                }else{
                    $res['parse']=1;
                    $res['url']=$play;
                }
                $res['playUrl'] = "";
            }else{
                $res['parse']=1;
                $res['jx']=0;
                $res['playUrl'] = $play;
                $res['url']="";
            }

        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        return $res;
    }

    public function searchContent($wd): string
    {
        $html = "";
        try {
            $params = [
                'wd' => $wd,
            ];
            $url = $this->siteUrl . '?'.http_build_query($params);
            $html = $this->okhttpString($url, [], $this->getHeaders($this->siteUrl));
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        return $html;
    }

    /**
     * @GetMapping(path="csp_Lezhu")
     */
    public function index()
    {
        return parent::index();
    }
}

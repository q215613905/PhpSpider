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
class WoggController extends AliYunController
{

    protected QueryList $ql;
    protected string $siteURL = "https://tvfan.xxooo.cf";
    protected string $pregUrl = '/(https:\/\/www\.aliyundrive\.com\/s\/[^"]+)/i';

    protected $classes_str = '{"class":[{"type_id":"2","type_name":"电视剧"},{"type_id":"1","type_name":"电影"},{"type_id":"4","type_name":"综艺"},{"type_id":"3","type_name":"动漫"}],"filters":{"1":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"2":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"3":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}],"4":[{"key":"year","name":"年代","value":[{"n":"全部","v":"0"},{"n":"2023","v":"153"},{"n":"2022","v":"101"},{"n":"2021","v":"118"},{"n":"2020","v":"16"},{"n":"2019","v":"7"},{"n":"2018","v":"2"},{"n":"2017","v":"3"},{"n":"2016","v":"22"}]},{"key":"sort","name":"排序","value":[{"n":"热门","v":"hot"},{"n":"评分","v":"rating"},{"n":"更新","v":"update"}]}]}}';

    public function __construct()
    {
        parent::__construct();
        $this->ql = new QueryList();
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
        $headers["User-Agent"] = $this->CHROME_UA;
        return $headers;
    }

    /**
     * @Cacheable(prefix="wogg:category", ttl=86400)
     */
    public function category(string $type_id, int $page, string $ext): array
    {
        $res = [];
        try {
            $extend = base64_decode($ext);
            $extend = json_decode($extend, true);
            $path="/index.php/vodshow/".$type_id."-{1}--{3}-----".$page."---{11}.html";
            $path=str_replace(["{1}","{3}","{11}"],[$extend[1]??"",$extend[3]??"",$extend[11]??""],$path);
            var_dump($path);
            $html = $this->okhttpString($this->siteURL.$path, [], $this->getHeaders($this->siteURL));

            //DOM解析规则
            $rules = [
                'vod_id' => ['.module-item-pic a', 'href'],
                'vod_name' => ['.module-item-pic a', 'title'],
                'vod_pic' => ['.module-item-cover img', 'data-src'],
                'vod_remarks' => ['.module-item-text', 'text']
            ];

            $ql = QueryList::html($html)
                ->rules($rules)
                ->range('.module-item')
                ->query();
            $data = $ql->getData();
            if ($data) $res['list'] = $data;
            $res['page'] = $page;
            $res['pagecount'] = count($data) == 72 ? $page + 1 : $page;;
            $res['limit'] = 72;
            $res['total'] = 72;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $res ?: [];
    }

    /**
     * @Cacheable(prefix="wogg:homeContent", ttl=86400)
     */
    public function homeContent()
    {
        co(function (){
            parent::refreshToken();
        });

        $res = json_decode('{"class":[{"type_id":"1","type_name":"自营电影"},{"type_id":"20","type_name":"自营剧集"},{"type_id":"24","type_name":"动漫"},{"type_id":"28","type_name":"综艺"},{"type_id":"32","type_name":"音乐"}],"filters":{"1":[{"key":"3","name":"类型","value":[{"n":"全部","v":""},{"n":"喜剧","v":"喜剧"},{"n":"爱情","v":"爱情"},{"n":"动作","v":"动作"},{"n":"恐怖","v":"恐怖"},{"n":"科幻","v":"科幻"},{"n":"剧情","v":"剧情"},{"n":"犯罪","v":"犯罪"},{"n":"奇幻","v":"奇幻"},{"n":"战争","v":"战争"},{"n":"悬疑","v":"悬疑"},{"n":"武侠","v":"武侠"},{"n":"冒险","v":"冒险"},{"n":"古装","v":"古装"},{"n":"历史","v":"历史"},{"n":"惊悚","v":"惊悚"}]},{"key":"11","name":"年代","value":[{"n":"全部","v":""},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016","v":"2016"},{"n":"2015","v":"2015"},{"n":"2014","v":"2014"},{"n":"2013","v":"2013"},{"n":"2012","v":"2012"},{"n":"2011","v":"2011"}]},{"key":"1","name":"地区","value":[{"n":"全部","v":""},{"n":"大陆","v":"大陆"},{"n":"中国香港","v":"香港"},{"n":"中国台湾","v":"台湾"},{"n":"美国","v":"美国"},{"n":"韩国","v":"韩国"},{"n":"日本","v":"日本"},{"n":"泰国","v":"泰国"},{"n":"法国","v":"法国"},{"n":"英国","v":"英国"},{"n":"德国","v":"德国"},{"n":"印度","v":"印度"},{"n":"其他","v":"其他"}]}],"20":[{"key":"11","name":"年代","value":[{"n":"全部","v":""},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016","v":"2016"},{"n":"2015","v":"2015"},{"n":"2014","v":"2014"},{"n":"2013","v":"2013"},{"n":"2012","v":"2012"},{"n":"2011","v":"2011"}]},{"key":"1","name":"地区","value":[{"n":"全部","v":""},{"n":"大陆","v":"大陆"},{"n":"中国香港","v":"香港"},{"n":"中国台湾","v":"台湾"},{"n":"美国","v":"美国"},{"n":"韩国","v":"韩国"},{"n":"日本","v":"日本"},{"n":"泰国","v":"泰国"},{"n":"法国","v":"法国"},{"n":"英国","v":"英国"},{"n":"德国","v":"德国"},{"n":"印度","v":"印度"},{"n":"其他","v":"其他"}]}],"28":[{"key":"1","name":"地区","value":[{"n":"全部","v":""},{"n":"内地","v":"内地"},{"n":"日韩","v":"日韩"},{"n":"欧美","v":"欧美"}]}],"24":[{"key":"11","name":"年代","value":[{"n":"全部","v":""},{"n":"2023","v":"2023"},{"n":"2022","v":"2022"},{"n":"2021","v":"2021"},{"n":"2020","v":"2020"},{"n":"2019","v":"2019"},{"n":"2018","v":"2018"},{"n":"2017","v":"2017"},{"n":"2016","v":"2016"},{"n":"2015","v":"2015"},{"n":"2014","v":"2014"},{"n":"2013","v":"2013"},{"n":"2012","v":"2012"},{"n":"2011","v":"2011"}]},{"key":"1","name":"地区","value":[{"n":"全部","v":""},{"n":"大陆","v":"大陆"},{"n":"日本","v":"日本"},{"n":"欧美","v":"欧美"},{"n":"其他","v":"其他"}]}]}}',true);
        try {
            $html = $this->okhttpString($this->siteURL, [], $this->getHeaders($this->siteURL));

            //DOM解析规则
            $rules = [
                'vod_id' => ['.module-item-pic a', 'href'],
                'vod_name' => ['.module-item-pic a', 'title'],
                'vod_pic' => ['.module-item-cover img', 'data-src'],
                'vod_remarks' => ['.module-item-caption .video-class:eq(0)', 'text']
            ];

            $ql = QueryList::html($html)
                ->rules($rules)
                ->range('.module-item')
                ->query();
            $data = $ql->getData()->toArray();
            if ($data) $res['list'] = array_slice($data, 0, 25);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $res ?: '';
    }

    /**
     * Cacheable(prefix="jp_detailContent", ttl=1296000)
     */
    public function detailContent($ids): array
    {
        var_dump($ids);
        $url = $this->getAliUrl($ids);
        $url=explode(',',$url);
        if(!$url[0])return [];

        if(count($url)==1){
            $rel1=parent::detailContent($url[0]);
            $rel1['list'][0]['vod_id']=$ids;
        }else{
            $rel1=parent::detailContent($url[0]);
            $from1=explode("$$$",$rel1['list'][0]['vod_play_from']);
            $url1=explode("$$$",$rel1['list'][0]['vod_play_url']);
            $rel2=parent::detailContent($url[1]);
            $from2=explode("$$$",$rel2['list'][0]['vod_play_from']);
            $url2=explode("$$$",$rel2['list'][0]['vod_play_url']);
            $rel1['list'][0]['vod_id']=$ids;
            foreach ($from2 as $k=>$item){
                if($url2[$k]){
                    $from1[]=$item.'2';
                    $url1[]=$url2[$k].'2';
                }
            }
            $rel1['list'][0]['vod_play_from']=implode("$$$",$from1);
            $rel1['list'][0]['vod_play_url']=implode("$$$",$url1);
        }
        return $rel1;
    }

    /**
     * @Cacheable(prefix="wogg:getAliUrl", ttl=86400)
     */
    protected function getAliUrl($ids){
        if(strpos($ids,"aliyundrive")!==false){
            $url[]=$ids;
        }else{
            $htl = $this->okhttpString($this->siteURL . $ids, [], $this->getHeaders($this->siteURL));
//            preg_match_all($this->pregUrl, $htl, $matches);
            //DOM解析规则
            $rules = [
               'url'=> ['.module-row-shortcuts a', 'href'],
            ];

            $ql = QueryList::html($htl)
                ->rules($rules)
                ->range('.module-row-one')
                ->query();
            $data = $ql->getData()->toArray();


            if ($data) {
                $url = array_column($data,"url");
            } else {
                return [""];
            }
        }
        return implode(',',$url);
    }

    public function playerContent($play, $flag): array
    {
        return parent::playerContent($play, $flag);
    }

    /**
     * @Cacheable(prefix="wogg:searchContent", ttl=86400)
     */
    public function searchContent($wd)
    {
        $res = [];
        try {
            $html = $this->okhttpString($this->siteURL . ("/index.php/vodsearch/-------------.html?wd=" . urlencode($wd)), [], $this->getHeaders($this->siteURL));

            //DOM解析规则
            $rules = [
                'vod_id' => ['.video-info-footer a', 'href'],
                'vod_name' => ['h3 a', 'title'],
                'vod_pic' => ['.module-item-pic img', 'data-src'],
                'vod_remarks' => ['.video-tag-icon', 'text']
            ];

            $ql = QueryList::html($html)
                ->rules($rules)
                ->range('.module-search-item')
                ->query();
            $data = $ql->getData();
            if ($data) $res['list'] = $data;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $res ?: '';
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
     * @GetMapping(path="csp_Wogg")
     */
    public function index()
    {
        return parent::index();
    }
}

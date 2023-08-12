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
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Redis\Redis;


/**
 * Class IndexController
 * @package App\Controller
 */
class AliYunController extends SpiderController
{
    /**
     * @Inject()
     * @var Redis
     */
    protected Redis $redis;
    protected string $aes_key = 'jundie';
    protected string $ali_player = 'http://192.168.0.94:9510/spider/AliJx';


    private array $sub_Map = [];

    private string $share_token = "";
    private string $share_id = "";
    private array $access_token = [];
    private array $open_access_token = [];
    private array $drive_id = [];
    private string $clientId = "76917ccccd4441c39457a04f6084fb2f";
    private string $refresh_token = "";
    private array $ali_user = [];

    public function __construct($access_token = "")
    {
        $this->clientFactory = new ClientFactory($this->container);
        if (!$access_token) $access_token = $this->okhttpString("http://192.168.31.69:666/json/token.txt", []);
        $this->refresh_token = $access_token;
    }

    protected function header1(): array
    {
        return [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36",
            "Referer" => "https://www.aliyundrive.com/",
            "Content-Type" => "application/json;charset=UTF-8",
            "Accept" => "*/*",
            "Accept-Language" => "zh-cn",
        ];
    }

    protected function header2(bool $isPlay = false): array
    {
        $token=$this->getToken($this->request->query("token",""));
        $header = [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36",
            "Referer" => "https://www.aliyundrive.com/",
            "Content-Type" => "application/json;charset=UTF-8",
            "X-Canary" => "client=web,app=share,version=v2.3.1",
            "X-share-token" => $this->share_token,
        ];
        if ($this->access_token[$token] && !$isPlay) $header['Authorization'] = $this->access_token[$token];
        return $header;
    }

    protected function header3(): array
    {
        $token=$this->getToken($this->request->query("token",""));
        return [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36",
            "Referer" => "https://www.aliyundrive.com/",
            "Content-Type" => "application/json;charset=UTF-8",
            "Authorization" => $this->open_access_token[$token],
        ];
    }

    protected function header4(): array
    {
        $token=$this->getToken($this->request->query("token",""));
        return [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36",
            "Content-Type" => "application/json;charset=UTF-8",
            "Authorization" => $this->access_token[$token]
        ];
    }

    /**
     * @Cacheable(prefix="ali:refreshToken", ttl=604800)
     * @param string $token
     * @return array
     */
    function refreshToken(string $token = ""): string
    {
        if (!$token) {
            $token = $this->okhttpString("http://192.168.31.69:666/json/token.txt", []);
        }
        $this->refresh_token = trim($token);
        return $this->refresh_token;
    }

    /**
     * @Cacheable(prefix="ali:refreshShareToken", ttl=7200)
     * @param $shareId
     * @return array
     */
    function refreshShareToken($shareId): array
    {
        $body = array(
            "share_id" => $shareId,
            "share_pwd" => ""
        );
        $result = $this->okhttpStringJson("https://api.aliyundrive.com/v2/share_link/get_share_token", $body, $this->header1(), "post");
        # echo $result . "\n";
        $object = json_decode($result, true);
        $share_token = $object['share_token'] ?? "";
        return [$share_token, $shareId];
    }

    /**
     * @Cacheable(prefix="ali:refreshAccessToken", ttl=7200)
     * @param $token
     */
    function refreshAccessToken($token): array
    {
        $body = ['refresh_token' => $token, 'grant_type' => 'refresh_token'];
        $result = $this->okhttpStringJson("https://auth.aliyundrive.com/v2/account/token", $body, $this->header1(), "post");
        # echo $result . "\n";
        $object = json_decode($result, true);
        $ali_user = $object;
        $access_token = $object['access_token'] ?? "";
        return [$ali_user, $access_token];
    }

    function listFiles(&$files, &$subs, $parent_fileId, $marker)
    {
        $body = array();
        $body['limit'] = 200;
        $body['share_id'] = $this->share_id;
        $body['parent_file_id'] = $parent_fileId;
        $body['order_by'] = 'name';
        $body['order_direction'] = 'ASC';
        if (strlen($marker) > 0) {
            $body['marker'] = $marker;
        }
        $object = $this->okhttpJson("https://api.aliyundrive.com/adrive/v3/file/list", $body, $this->header2(true), "post");
        $mark = $object['next_marker'] ?? "";
        $items = $object['items'] ?? "";
        $folders = array();
        $temp = array();
        $tempsub = array();

        foreach ($items as $list) {
            if ($list['type'] == "folder") {
                if ($list['file_id']) $folders[] = $list['file_id'];
            } else if ($list['category'] == "video" || $list['category'] == "audio") {
                if (!$list['file_id'] || strpos($list['name'],".wma")!==false) continue;
                $temp['name'] = $list['name'] . $this->getFileSize($list['size']) . "$" . $this->encrypt($this->share_id . "@@@" . $list['file_id']);
                $files[$this->removeExt($list['name'])] = $temp;

            } else if ($this->isSub($list['file_extension'])) {
                if (!$list['file_id']) continue;
                $tempsub['name'] = $list['name'] . "@@@" . $list['file_extension'] . "@@@" . $list['file_id'];
                $subs[$this->removeExt($list['name'])] = $tempsub;

            }
        }
        if (strlen($mark) > 0) {
            $this->listFiles($files, $subs, $parent_fileId, $mark);
        }
        if (!empty($folders)) {
            foreach ($folders as $folder) {
                $this->listFiles($files, $subs, $folder, "");
            }
        }

    }

    protected function getFileSize($size): string
    {
        return $size == 0 ? "" : "[" . $this->getSize($size) . "]";
    }

    protected function getSize($size)
    {
        if ($size <= 0) return "";
        if ($size > 1024 * 1024 * 1024 * 1024.0) {
            $size /= (1024 * 1024 * 1024 * 1024.0);
            return sprintf("%.2f%s", $size, "TB");
        } else if ($size > 1024 * 1024 * 1024.0) {
            $size /= (1024 * 1024 * 1024.0);
            return sprintf("%.2f%s", $size, "GB");
        } else if ($size > 1024 * 1024.0) {
            $size /= (1024 * 1024.0);
            return sprintf("%.2f%s", $size, "MB");
        } else {
            $size /= 1024.0;
            return sprintf("%.2f%s", $size, "KB");
        }
    }

    /**
     * @Cacheable(prefix="ali:detailFileInfos", ttl=43200)
     */
    protected function getFileEpisode(string $url): array
    {
        preg_match('/www.aliyundrive.com\/s\/(.{11})(\/folder\/(.*)|)/', $url, $str);
        $shareId = $str[1];
        $fileId = $str[3] ?? "";
        $body = array("share_id" => $shareId);
        $json = $this->okhttpStringJson("https://api.aliyundrive.com/adrive/v3/share_link/get_share_by_anonymous", $body, $this->header1(), "post");
        $data_php = json_decode($json, true);
        $object = $data_php['file_infos'] ?? [];
        if (count($object) == 0) {
            return [[],[]];
        }
        [$this->share_token, $this->share_id] = $this->refreshShareToken($shareId);
        var_dump($this->share_id);
        $files = [];
        $subs = [];
        if (!empty($fileId)) {
            $this->listFiles($files, $subs, $fileId, "");
        } else {
            foreach ($object as $fileInfo) {
                if ($fileInfo['type'] == "folder") {
                    $this->listFiles($files, $subs, $fileInfo['file_id'], "");
                }
                if ($fileInfo['type'] == "file" && $fileInfo['category'] == "video") {
                    $this->listFiles($files, $subs, "root", "");
                }
            }
        }

        $episode = [];
        foreach ($files as $k => $file) {
            $episode[] = $file['name'] . "~" . $this->findSubs($k, $subs);
        }
        // 使用自定义排序函数对数组进行排序
        usort($episode, function ($a, $b) {
            // 使用正则表达式提取 $a 和 $b 中小数点前的数字
            preg_match('/\d+(\.\d+)?/', $a, $aNumber);
            preg_match('/\d+(\.\d+)?/', $b, $bNumber);

            // 将提取到的数字转换为浮点数进行比较
            $aNumber = floatval($aNumber[0]);
            $bNumber = floatval($bNumber[0]);

            // 根据小数点前的数字进行比较
            return $aNumber - $bNumber;
        });
        return [$episode, $data_php];
    }

    /**
     * Cacheable(prefix="ali:detailContent", ttl=86400)
     */
    public function detailContent($url)
    {
        if (!$url) return [];
        [$episode, $data_php] = $this->getFileEpisode($url);
        if (!$episode) return [];
        $new_from = [];
        $sort = $this->request->query("sort", "");
        if($sort){
            $play_from = explode(',', $sort);
            foreach ($play_from as $from) {
                if (!in_array($from, ["原画", "蓝光", "超清", "高清"])) continue;
                $new_from[] = $from;
            }
        }

        $playFrom = $new_from?:[ "原画","蓝光", "超清"];
        $playUrl = [];
        $result = [];
        $playF = implode("$$$", $playFrom);
        foreach ($playFrom as $a) {
//            var_dump($a);
//            if (strpos($a,"原画")===false) {
//                $newsode=[];
//                foreach ($episode as $item) {
//                    // 使用正则表达式匹配所有括号及括号以内的内容
//                    $pattern = '/\[[^\]]+\]/';
//                    $newsode[] = preg_replace($pattern, "", $item);
//                }
//                $episode=$newsode;
//            }
            $playUrl[] = implode("#", $episode);
        }
        $now = implode("$$$", $playUrl);
        $data = [];
        $data["vod_id"] = $url;
        $data["vod_name"] = $data_php['share_name'];
        $data["vod_pic"] = $data_php['avatar'];
        $data["vod_remarks"] = $data_php['updated_at'];
        $data["type_name"] = "阿里云盘";
        $data["vod_actor"] = "";
        $data["vod_director"] = "更新于: ".$data_php['updated_at'];
        $data["vod_content"] =PHP_EOL.$url.PHP_EOL.$data_php['display_name'];
        $data["vod_year"] = $data_php['updated_at'];
        $data["vod_area"] = "";
        $data["vod_play_from"] = $playF;
        $data["vod_play_url"] = $now;
        $rs[] = $data;
        $result["list"] = $rs;
        return $result;
    }


    /**
     * @param $play
     * @param $flag
     * @return array
     */
    public function playerContent($play, $flag): array
    {
        $token = $this->request->query("token", "");
        $token = $this->getToken($token);
        var_dump($token);
        if(strlen($token)!=32)return['parse'=>0,'url'=>""];
        [$ali_user, $this->access_token[$token]] = $this->refreshAccessToken($token);
        [$open_refresh_token, $open_access_token] = $this->openAuth($token);
        if (!$open_access_token) {
            $this->redis->del("c:ali:openAuth:" . $this->refresh_token);
            [$open_refresh_token, $open_access_token] = $this->openAuth($token);
        }
        $this->drive_id[$token] = $ali_user['default_drive_id'];
        $this->open_access_token[$token] = $open_access_token;

        #  echo $url;


        $obj = explode("~", $play);
        $urlArr = $this->decrypt($obj[0]);
        $shareid = explode("@@@", $urlArr)[0];
        $fid = explode("@@@", $urlArr)[1];
        if(!$fid)return [];
        $result = [];
        [$this->share_token, $this->share_id] = $this->refreshShareToken($shareid);
        if (count($obj) > 1 && $obj[1]) {
            $subobj = $obj[1];
            $subobj = explode("@@@", $subobj);
            $subname = $subobj[0];
            $subtype = $subobj[1]??"";
            $subfile = $subobj[2]??"";
            if($subfile){
                $type = "application/x-subrip";
                if ($subtype == "ass") $type = "text/x-ssa";
                if ($subtype == "vtt") $type = "text/vtt";
                if (strlen($subtype) > 0 && $subfile) {
                    $result['subf'] = $type;
                    $result['subt'] = $this->getDownloadUrl($subfile);
                    $result['subs'] = [["url" => $this->getDownloadUrl($subfile), "name" => $subname, "lang" => "zh", "format" => $type]];
                }
            }
        }

        $result['parse'] = "0";
        $flag=str_replace("2","",$flag);
        if ($flag == "原画") {
            $url = $this->getDownloadUrl($fid);
            if (!$url) {
                $this->redis->del("c:ali:getDownloadUrl:" . $fid);
                $url = $this->getDownloadUrl($fid);
            }
            $result['url']=$url;
        } else {
            $url = $this->getPreviewUrl($fid, $flag);
            if (!$url) {
                $this->redis->del("c:ali:getPreviewUrl:" . $fid .":".$flag);
                $url = $this->getPreviewUrl($fid, $flag);
            }
            $result['url']=$url;
        }

        return $result;
    }

    /**
     * @Cacheable(prefix="ali:token", ttl=86400)
     */
    protected function getToken($token): string
    {
        if (strpos($token, "http") === 0){
            $refresh_token = trim($this->okhttpString($token, []));
        }else if(strpos($token,"内置token")!==false){
            $refresh_token = trim($this->okhttpString("http://192.168.31.69:666/json/token.txt", []));
        }else{
            $refresh_token = $token;
        }
        return $refresh_token;
    }

    protected function removeExt($filename): string
    {
        return strpos($filename, ".") !== false ? substr($filename, 0, strrpos($filename, ".")) : $filename;
    }

    protected function isSub($ext): bool
    {
        return $ext === "srt" || $ext === "ass" || $ext === "ssa";
    }

    protected function findSubs($name, $subMap): string
    {
        if (!$subMap) return "";
        return $subMap[$name]['name'] ?? "";
    }


    protected function encrypt($data): string
    {
        return base64_encode($data);
//        var_dump($this->aes_key);
//        return bin2hex(openssl_encrypt($data, "AES-128-ECB", $this->aes_key, OPENSSL_RAW_DATA));
    }

    protected function decrypt($data): string
    {
        return base64_decode($data);
//        var_dump($this->aes_key);
//        return openssl_decrypt(hex2bin($data), "AES-128-ECB", $this->aes_key?:"jundie", OPENSSL_RAW_DATA);
    }


    /**
     * Cacheable(prefix="ali:copyfile", ttl=300)
     */
    protected function copyfile($fileId)
    {
        $token=$this->getToken($this->request->query("token",""));
        $json = array(
            "requests" => array(
                array(
                    "body" => array(
                        "file_id" => $fileId,
                        "share_id" => $this->share_id,
                        "auto_rename" => true,
                        "to_parent_file_id" => "root",
                        "to_drive_id" => $this->drive_id[$token]
                    ),
                    "headers" => array(
                        "Content-Type" => "application/json"
                    ),
                    "id" => "0",
                    "method" => "POST",
                    "url" => "/file/copy"
                )
            ),
            "resource" => "file"
        );
        $result = $this->okhttpStringJson("https://api.aliyundrive.com/adrive/v2/batch", $json, $this->header2(), "post");
        if (strpos($result, "ForbiddenNoPermission.File") !== false) return $this->copyfile($fileId);
        return json_decode($result, true)["responses"][0]["body"]["file_id"];
    }

    protected function delete($fileId)
    {
        $token=$this->getToken($this->request->query("token",""));
        $body = array(
            "file_id" => $fileId,
            "drive_id" => $this->drive_id[$token]
        );
        $this->okhttpStringJson("https://open.aliyundrive.com/adrive/v1.0/openFile/delete", $body, $this->header3(), "post");
    }

    /**
     * @Cacheable(prefix="ali:getDownloadUrl", ttl=111600)
     */
    protected function getDownloadUrl($fileId)
    {
        $token=$this->getToken($this->request->query("token",""));
        $tempId = $this->copyfile($fileId);
        $body = array(
            "file_id" => $tempId,
            "drive_id" => $this->drive_id[$token],
            "expire_sec" => 115200
        );
        $response = $this->okhttpStringJson("https://open.aliyundrive.com/adrive/v1.0/openFile/getDownloadUrl", $body, $this->header3(), "post");
//        $response = $this->okhttpStringJson("https://api.aliyundrive.com/adrive/v2/file/get_download_url", $body, $this->header3(), "post");
        co(function () use ($tempId) {
            if ($tempId != null) $this->delete($tempId);
        });
        return json_decode($response, true)["url"];

    }

    /**
     * @Cacheable(prefix="ali:getPreviewUrl", ttl=13500)
     */
    protected function getPreviewUrl($fileId, $flag)
    {
        $token=$this->getToken($this->request->query("token",""));
        $tempId = $this->copyfile($fileId);
        $body = array(
            "file_id" => $tempId,
            "drive_id" => $this->drive_id[$token],
            "category" => "live_transcoding",
            "url_expire_sec" => "14400",
        );
        $response = $this->okhttpStringJson("https://open.aliyundrive.com/adrive/v1.0/openFile/getVideoPreviewPlayInfo", $body, $this->header3(), "post");
        co(function () use ($tempId) {
            if ($tempId != null) $this->delete($tempId);
        });
        $info = json_decode($response, true)["video_preview_play_info"]["live_transcoding_task_list"] ?? [];
        if (!$info) return "";
        $urls = array_column($info, "url", 'template_id');
        $flags = ["超清" => "FHD", "高清" => "HD"];
        if ($flag == "蓝光") return $info[count($info) - 1]['url'];
        return $urls[$flags[$flag]] ?? $info[count($info) - 1]['url'];
    }


    /**
     *
     * @Cacheable(prefix="ali:openAuth", ttl=7200)
     * @param $token
     * @return array
     */
    function openAuth($token): array
    {
        $open_refresh_token = $open_access_token = "";
        $body = array(
            'authorize' => 1,
            'scope' => 'user:base,file:all:read,file:all:write'
        );
        $url = 'https://open.aliyundrive.com/oauth/users/authorize?client_id=' . $this->clientId . '&redirect_uri=https://alist.nn.ci/tool/aliyundrive/callback&scope=user:base,file:all:read,file:all:write&state=';
        $response = $this->okhttpStringJson($url, $body, $this->header4(), "post");
        $object = json_decode($response, true);
        $redirectUri = $object['redirectUri'];
        $code = explode("code=", $redirectUri)[1] ?? "";
        if ($code) {
            $body = array(
                'code' => $code,
                'grant_type' => 'authorization_code'
            );
            $result = $this->okhttpStringJson('https://aliapi.ewwe.gq/alist/ali_open/code', $body, $this->header1(), "post");

            $object = json_decode($result, true);
            $open_refresh_token = $object["refresh_token"] ?? "";
            if ($open_refresh_token) {
                $open_access_token = $object["token_type"] . " " . $object["access_token"];
            }
        }
        return [$open_refresh_token, $open_access_token];
    }

}

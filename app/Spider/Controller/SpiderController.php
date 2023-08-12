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
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;

abstract class SpiderController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    protected ClientFactory $clientFactory;


    protected string $CHROME_UA="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36";

    protected function success(array $data)
    {
        return $this->response->json($data);
    }

    protected function raw(string $data)
    {
        return $this->response->raw($data);
    }

    protected function redirect(string $toUrl, int $status = 302, string $schema = 'http')
    {
        return $this->response->redirect($toUrl,$status,$schema);
    }

    public function index()
    {
        $type_id = $this->request->query("t");
        $page = $this->request->query("pg");
        $ids = $this->request->query("ids");
        $wd = $this->request->query("wd");
        $play = $this->request->query("play");
        $flag = $this->request->query("flag");
        $ext = $this->request->query("ext","");
        if ($type_id && $page) {
            $res = $this->category($type_id, (int)$page, $ext);
        } else if ($ids && strpos($ids, ",") === false && strpos($ids, "%2C") === false) {
            $res = $this->detailContent($ids);
        } else if ($play) {
            $res = $this->playerContent($play, $flag);
        } else if ($wd) {
            if(mb_strlen($wd)>15)return [];
            $res = $this->searchContent($wd);
        } else {
            $res = $this->homeContent();
        }

        return $res;
    }

    function getSubstr($str, $leftStr, $rightStr):string
    {
        if ($leftStr  && $rightStr ) {
            $left = strpos($str, $leftStr);
            $right = strpos($str, $rightStr, $left + strlen($leftStr));
            if ($left < 0 or $right < $left) {
                return '';
            }
            return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
        } else {
            $str2 = $str;
            if ($leftStr) {
                $str2 = str_replace($leftStr, '', $str2);
            }
            if ($rightStr) {
                $str2 = str_replace($rightStr, '', $str2);
            }
            return $str2;
        }
    }

    public function okhttp($url, $data=[], $headers = [], $method = "get")
    {
        $client = $this->clientFactory->create();
        if(!isset($headers['Host'])) $headers['Host'] = parse_url($url)['host'];
        if ($url) {
            try {
                if ($method === "get") {
                    $params = http_build_query($data);
                    $response = $client->get($url, [
                        'headers' => $headers,
                        'body' => $params
                    ]);
                } else {

                    $response = $client->post($url, [
                        'headers' => $headers,
                        'form_params' => $data,
                    ]);
                }
                if ($response->getStatusCode() === 200) {
                    $body = $response->getBody()->getContents();
                    return json_decode($body, true);
                } else {
                    return '异常';
                }
            } catch (\Throwable $e) {
                echo '发生异常 :' . PHP_EOL;
                var_dump($e->getMessage());
                return [];
            }
        }
        return [];
    }

    public function okhttpString($url, $data=[], $headers = [], $method = "get")
    {
        $client = $this->clientFactory->create();
        if(!isset($headers['Host'])) $headers['Host'] = parse_url($url)['host'];
        if ($url) {
            try {
                if ($method === "get") {
                    $response = $client->get($url, [
                        'headers' => $headers
                    ]);
                } else {
//                    $params = http_build_query($data);
                    $response = $client->post($url, [
                        'headers' => $headers,
                        'form_params' => $data
                    ]);
                }
                if ($response->getStatusCode() === 200) {
                    return $response->getBody()->getContents();
                } else {
                    return '异常';
                }
            } catch (\Throwable $e) {
                echo '发生异常 :' . PHP_EOL;
                var_dump($e->getMessage());
                return "异常";
            }
        }
        return [];
    }

    public function okhttpStringJson($url, $data=[], $headers = [], $method = "get")
    {
        $client = $this->clientFactory->create();
        if(!isset($headers['Host'])) $headers['Host'] = parse_url($url)['host'];
        if ($url) {
            try {
                if ($method === "get") {
                    $response = $client->get($url, [
                        'headers' => $headers
                    ]);
                } else {
//                    $params = http_build_query($data);
                    $response = $client->post($url, [
                        'headers' => $headers,
                        'json' => $data
                    ]);
                }
                if ($response->getStatusCode() === 200) {
                    return $response->getBody()->getContents();
                } else {
                    return "{}";
                }
            } catch (\Throwable $e) {
                echo '发生异常 :' . PHP_EOL;
                var_dump($e->getMessage());
                return "{}";
            }
        }
        return "{}";
    }

    public function okhttpJson($url, $data=[], $headers = [], $method = "get")
    {
        $client = $this->clientFactory->create();
        if(!isset($headers['Host'])) $headers['Host'] = parse_url($url)['host'];
        if ($url) {
            try {
                if ($method === "get") {
                    $response = $client->get($url, [
                        'headers' => $headers,
                        'timeout' =>10
                    ]);
                } else {
                    $response = $client->post($url, [
                        'headers' => $headers,
                        'json' => $data
                    ]);
                }
                if ($response->getStatusCode() === 200) {
                    $body = $response->getBody()->getContents();
                    return json_decode($body, true);
                } else {
                    return [];
                }
            } catch (\Throwable $e) {
                echo '发生异常 :' . PHP_EOL;
                var_dump($e->getMessage());
                return [];
            }
        }
        return [];
    }

    public function okhttpHeader($url, $data=[], $headers = [], $method = "get"): array
    {
        $client = $this->clientFactory->create();
        if(!isset($headers['Host'])) $headers['Host'] = parse_url($url)['host'];
        if ($url) {
            try {
                if ($method === "get") {
                    $response = $client->get($url, [
                        'headers' => $headers,
                        'allow_redirects' => false,
                    ]);
                } else {
//                    $params = http_build_query($data);
                    $response = $client->post($url, [
                        'headers' => $headers,
                        'form_params' => $data
                    ]);
                }
                $location = $response->getHeaderLine('Location');
                if(!$location)$location = $response->getHeaderLine('location');
                return ['location' => $location];
            } catch (\Throwable $e) {
                echo '发生异常 :' . PHP_EOL;
                var_dump($e->getMessage());
                return [];
            }
        }
        return [];
    }
}

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

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractController
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
        } else if ($wd <> null) {
            $res = $this->searchContent($wd);
        } else {
            $res = $this->homeContent();
        }

        return $res;
    }

    public function okhttp($url, $data, $headers = [], $method = "get")
    {
        $headers['Host'] = parse_url($url)['host'];
        if ($url) {
            try {
                if ($method === "get") {
                    $response = $this->client->get($url, [
                        'headers' => $headers
                    ]);
                } else {
                    $params = http_build_query($data);
                    $response = $this->client->post($url, [
                        'headers' => $headers,
                        'body' => $params
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
}

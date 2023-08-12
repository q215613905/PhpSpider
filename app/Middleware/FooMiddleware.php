<?php

declare(strict_types=1);

namespace App\Middleware;

use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FooMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
        define('CONSOLE_COLOR_YELLOW',"\033[0;33m");
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $url=$this->request->fullUrl();
        if(count(parse_url($url))<4 && empty($this->request->all())){
            $response=$this->response;
        }else {

        }
//        if(!str_contains($this->request->getRequestTarget(),"YT")){
//            return $this->response;
//        }
        echo CONSOLE_COLOR_YELLOW . '= = = = = = = = = = = = = = = = = = = = = = = = =' . " start @ " . date('Y-m-d H:i:s') . ' = = = = = = = = = = = = = = = = = = = = = = =' . PHP_EOL;
        echo CONSOLE_COLOR_YELLOW . "  URL  @ " . $request->getUri() . PHP_EOL;
        echo CONSOLE_COLOR_YELLOW . "  PATH @ " . $request->getRequestTarget() . PHP_EOL;
        echo CONSOLE_COLOR_YELLOW . "METHOD@ " . $this->request->getMethod() . PHP_EOL;
        echo CONSOLE_COLOR_YELLOW . " PARAMS@ " . json_encode($this->request->all(), JSON_UNESCAPED_UNICODE) . PHP_EOL;
        echo CONSOLE_COLOR_YELLOW . "  TYPE @ " . $this->request->header('Content-Type') . PHP_EOL;
        echo CONSOLE_COLOR_YELLOW . "HEADERS@"  . json_encode($this->request->getHeaders()) . PHP_EOL;
        echo CONSOLE_COLOR_YELLOW . "  BODY @ " . $request->getBody() . PHP_EOL;
        $response = $handler->handle($request);
        if(strlen((string)$response->getBody())>200){
            echo CONSOLE_COLOR_YELLOW . "RESPONSE " .substr((string)$response->getBody(),0,500). PHP_EOL;
        }else{
            echo CONSOLE_COLOR_YELLOW . "RESPONSE " .   $response->getBody(). PHP_EOL;
        }
        echo CONSOLE_COLOR_YELLOW . '= = = = = = = = = = = = = = = = = = = = = = = = =' . "  end  @ " . date('Y-m-d H:i:s') . ' = = = = = = = = = = = = = = = = = = = = = = =' . PHP_EOL;
        return $response;
    }
}
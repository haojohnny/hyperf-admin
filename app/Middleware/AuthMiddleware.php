<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\LoginException;
use App\Util\AccessToken;
use App\Util\Auth;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;

class AuthMiddleware implements MiddlewareInterface
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
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //检查节点
        //检查TOKEN
        $cur_node = $this->request->getUri()->getPath();
        foreach (Auth::ignores() as $ignore) {
            if ($ignore == $cur_node) {
                return $handler->handle($request);//交给下个一个中间件处理
            }
        }

        $token = $this->request->header('token');

        try {
            //todo 检查token
            $instance = new AccessToken();
            $instance->checkToken($token);
        } catch (LoginException $exception) {
            return $this->response->json(
//                $this->error($exception->getMessage(), -1)
                [
                    'msg' => $exception->getMessage()
                ]
            );
        }

        //todo 检查用户与节点权限
        if (!Auth::checkNode($cur_node)) {
            return $this->response->json(
                [
                    'msg' => '您没有访问改节点的权限！'
                ]
            );
        }

        return $handler->handle($request);//交给下个一个中间件处理
    }
}
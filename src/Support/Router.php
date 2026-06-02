<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:array{0:class-string,1:string}} > */
    private array $routes = [];

    /** @param array{0:class-string,1:string} $handler */
    public function get(string $pattern, array $handler): void
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'handler' => $handler];
    }

    /** @param array{0:class-string,1:string} $handler */
    public function post(string $pattern, array $handler): void
    {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'handler' => $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) {
                continue;
            }
            $re = '#^' . $r['pattern'] . '$#';
            if (!preg_match($re, $path, $m)) {
                continue;
            }
            $params = [];
            foreach ($m as $k => $v) {
                if (!is_string($k)) {
                    continue;
                }
                $params[$k] = $v;
            }
            [$class, $action] = $r['handler'];
            $controller = new $class();
            $controller->$action($params);
            return;
        }
        Response::notFound();
    }
}

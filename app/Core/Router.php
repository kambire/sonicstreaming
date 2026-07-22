<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router minimalista con soporte de parametros {id} y middlewares por ruta.
 */
final class Router
{
    /** @var array<int,array{method:string,regex:string,params:array<int,string>,handler:mixed,middleware:array<int,mixed>}> */
    private array $routes = [];

    /** @var array<int,mixed> Middlewares a aplicar al siguiente grupo. */
    private array $groupMiddleware = [];

    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /**
     * Agrupa rutas bajo una lista de middlewares.
     */
    public function group(array $middleware, callable $callback): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($previous, $middleware);
        $callback($this);
        $this->groupMiddleware = $previous;
    }

    private function add(string $method, string $path, mixed $handler, array $middleware): void
    {
        $params = [];
        $regex = preg_replace_callback('#\{(\w+)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method'     => $method,
            'regex'      => $regex,
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $args = [];
            foreach ($route['params'] as $i => $name) {
                $args[$name] = $matches[$i] ?? null;
            }

            // Ejecutar middlewares
            foreach ($route['middleware'] as $mw) {
                $instance = is_string($mw) ? new $mw() : $mw;
                $instance->handle($request);
            }

            $this->invoke($route['handler'], $args, $request);
            return;
        }

        http_response_code(404);
        echo View::render('errors/404', [], 'layouts/blank');
    }

    private function invoke(mixed $handler, array $args, Request $request): void
    {
        if (is_callable($handler)) {
            $handler($request, ...array_values($args));
            return;
        }

        // Formato "Controller@method"
        [$class, $action] = explode('@', $handler);
        $fqcn = 'App\\Controllers\\' . $class;
        $controller = new $fqcn();
        $controller->$action($request, ...array_values($args));
    }
}

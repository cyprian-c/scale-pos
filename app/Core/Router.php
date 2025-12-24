<?php
// app/Core/Router.php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $middleware = [];

    public function add(string $method, string $path, $handler, array $middleware = []): void
    {
        $this->routes[strtoupper($method)][$path] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function dispatch(string $method, string $path)
    {
        $method = strtoupper($method);

        // Remove trailing slash
        $path = rtrim($path, '/') ?: '/';

        // Check for exact match
        if (isset($this->routes[$method][$path])) {
            return $this->handleRoute($this->routes[$method][$path]);
        }

        // Check for dynamic routes (simple version)
        foreach ($this->routes[$method] as $route => $routeData) {
            if (strpos($route, '{') !== false) {
                $pattern = $this->convertRouteToPattern($route);
                if (preg_match($pattern, $path, $matches)) {
                    // Extract parameters
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    return $this->handleRoute($routeData, $params);
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        return $this->renderView('errors/404');
    }

    protected function convertRouteToPattern(string $route): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    protected function handleRoute(array $routeData, array $params = [])
    {
        $handler = $routeData['handler'];

        // Apply middleware
        foreach ($routeData['middleware'] as $middleware) {
            $result = $this->executeMiddleware($middleware);
            if ($result !== null) {
                return $result;
            }
        }

        // Execute handler
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler);
            $controller = "App\\Http\\Controllers\\{$controller}";

            if (class_exists($controller)) {
                $instance = new $controller();
                if (method_exists($instance, $method)) {
                    return call_user_func_array([$instance, $method], $params);
                }
            }
        }

        throw new \Exception("Invalid route handler");
    }

    protected function executeMiddleware(string $middleware)
    {
        $middlewareClass = "App\\Http\\Middleware\\{$middleware}";

        if (class_exists($middlewareClass)) {
            $instance = new $middlewareClass();
            if (method_exists($instance, 'handle')) {
                return $instance->handle();
            }
        }

        return null;
    }

    protected function renderView(string $view, array $data = []): string
    {
        $viewPath = BASE_PATH . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            return "View not found: {$view}";
        }

        extract($data);
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
}

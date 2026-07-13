<?php

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $uri, callable $action): void
    {
        $this->routes['GET'][$this->normalize($uri)] = $action;
    }

    public function post(string $uri, callable $action): void
    {
        $this->routes['POST'][$this->normalize($uri)] = $action;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $basePath = $this->getBasePath();
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = $this->normalize($uri);

        $action = $this->routes[$method][$uri] ?? null;
        $params = [];

        if (!$action) {
            foreach ($this->routes[$method] ?? [] as $route => $candidate) {
                if (!str_contains($route, '{')) {
                    continue;
                }

                $paramNames = [];
                $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($matches) use (&$paramNames) {
                    $paramNames[] = $matches[1];
                    return '___ROUTE_PARAM___';
                }, $route);
                $pattern = preg_quote($pattern, '#');
                $pattern = str_replace('___ROUTE_PARAM___', '([^/]+)', $pattern);
                $pattern = '#^' . $pattern . '$#';
                if (!preg_match($pattern, $uri, $matches)) {
                    continue;
                }

                array_shift($matches);
                $params = array_combine($paramNames, array_map('rawurldecode', $matches)) ?: [];
                $action = $candidate;
                break;
            }
        }

        if (!$action) {
            http_response_code(404);

            $notFoundFile = __DIR__ . '/../../public/404.php';
            if (file_exists($notFoundFile)) {
                require $notFoundFile;
            } else {
                echo '404 - Página não encontrada';
            }
            exit;
        }

        call_user_func_array($action, array_values($params));
    }

    private function normalize(string $uri): string
    {
        $uri = '/' . trim($uri, '/');
        return $uri === '//' ? '/' : $uri;
    }

    private function getBasePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));

        if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
            return '';
        }

        return rtrim($basePath, '/');
    }
}

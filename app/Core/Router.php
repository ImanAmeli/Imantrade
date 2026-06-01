<?php
namespace App\Core;

/**
 * Minimal path router with {param} placeholders.
 */
class Router
{
    /** @var array<int,array{method:string,pattern:string,handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    public function dispatch(string $method, string $uri): void
    {
        // strip query string + base path
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $base = rtrim((string) config('app.base_path'), '/');
        if ($base && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $regex = $this->toRegex($route['pattern']);
            if (preg_match($regex, $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler = $route['handler'];
                // Lazy controller instantiation: [Controller::class, 'method'].
                // This ensures constructors (e.g. auth guards) run ONLY for the
                // matched route, not for every route at registration time.
                if (is_array($handler) && is_string($handler[0])) {
                    $handler = [new $handler[0](), $handler[1]];
                }
                call_user_func_array($handler, array_values($params));
                return;
            }
        }

        http_response_code(404);
        View::render('errors/404', [], 'public');
    }

    private function toRegex(string $pattern): string
    {
        $pattern = '/' . trim($pattern, '/');
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}

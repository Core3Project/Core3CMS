<?php
/**
 * Front-controller router.
 *
 * Parses the request URI, matches it against registered routes,
 * and dispatches to the appropriate controller action.
 *
 * @package Core3
 */
class Router
{
    private $path;
    private $routes = [];

    public function __construct()
    {
        $this->path = $this->parsePath();
        $this->registerRoutes();
    }

    /**
     * Strip the base directory from the request URI to get
     * the path relative to the CMS root.
     */
    private function parsePath()
    {
        $uri  = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $path = parse_url($uri, PHP_URL_PATH);

        if ($base && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }

        return '/' . trim($path, '/');
    }

    /**
     * Define the built-in route table and let modules extend it.
     */
    private function registerRoutes()
    {
        $this->routes = [
            '/'                      => ['controller' => 'BlogController',   'action' => 'index'],
            '/pages/{num}'           => ['controller' => 'BlogController',   'action' => 'index'],
            '/post/{slug}'           => ['controller' => 'BlogController',   'action' => 'single'],
            '/category/{slug}'       => ['controller' => 'BlogController',   'action' => 'category'],
            '/category/{slug}/{num}' => ['controller' => 'BlogController',   'action' => 'category'],
            '/page/{slug}'           => ['controller' => 'PageController',   'action' => 'show'],
            '/register'              => ['controller' => 'AuthController',   'action' => 'register'],
            '/feed'                  => ['controller' => 'FeedController',   'action' => 'rss'],
            '/sitemap.xml'           => ['controller' => 'FeedController',   'action' => 'sitemap'],
        ];

        Modules::hook('routes', $this->routes);
    }

    /**
     * Match the current path to a route and call the handler.
     */
    public function dispatch()
    {
        foreach ($this->routes as $pattern => $target) {
            $params = $this->matchRoute($pattern, $this->path);

            if ($params !== false) {
                $this->callAction($target['controller'], $target['action'], $params);
                return;
            }
        }

        http_response_code(404);
        Theme::render('404', ['pageTitle' => 'Not Found']);
    }

    /**
     * Test whether a route pattern matches the given path.
     *
     * Returns an array of captured parameters or false.
     */
    private function matchRoute($pattern, $path)
    {
        $regex = preg_replace('/\{slug\}/', '([a-zA-Z0-9_-]+)', $pattern);
        $regex = preg_replace('/\{num\}/', '(\d+)', $regex);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            array_shift($matches);
            return $matches;
        }

        return false;
    }

    /**
     * Instantiate a controller and call the specified action.
     */
    private function callAction($class, $action, $params)
    {
        if (!class_exists($class) || !method_exists($class, $action)) {
            http_response_code(404);
            Theme::render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        $controller = new $class();
        call_user_func_array([$controller, $action], $params);
    }

    /**
     * Generate a full URL for a given path.
     */
    public static function url($path = '')
    {
        return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
    }
}

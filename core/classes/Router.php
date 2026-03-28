<?php

defined('C3_ROOT') || exit;

/**
 * Front-controller router
 *
 * Parses the request URI, matches it against a table of route
 * patterns, and dispatches to the appropriate controller action.
 * Modules can register additional routes via the 'routes' hook.
 */
class Router
{
    /**
     * The cleaned request path
     *
     * @var string
     */
    private $path;

    /**
     * Registered route patterns
     *
     * @var array
     */
    private $routes = [];

    public function __construct()
    {
        $this->path = $this->parsePath();
        $this->loadRoutes();
    }

    /**
     * Match the request to a route and call the handler
     *
     * @return void
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
     * Generate a full URL relative to the site root
     *
     * @param string $path
     *
     * @return string
     */
    public static function url($path = '')
    {
        return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
    }

    // ----- internal -----

    /**
     * Extract the request path, stripping the base directory
     *
     * @return string
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
     * Register the built-in routes and let modules add theirs
     *
     * @return void
     */
    private function loadRoutes()
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
     * Test whether a route pattern matches the given path
     *
     * @param string $pattern route pattern with {slug} and {num} placeholders
     * @param string $path    actual request path
     *
     * @return array|false captured parameters or false
     */
    private function matchRoute($pattern, $path)
    {
        $regex = preg_replace('/\{slug\}/', '([a-zA-Z0-9_-]+)', $pattern);
        $regex = preg_replace('/\{num\}/',  '(\d+)', $regex);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            array_shift($matches);
            return $matches;
        }

        return false;
    }

    /**
     * Instantiate a controller and call the action method
     *
     * @param string $class
     * @param string $action
     * @param array  $params
     *
     * @return void
     */
    private function callAction($class, $action, $params)
    {
        if ( !  class_exists($class) || ! method_exists($class, $action)) {
            http_response_code(404);
            Theme::render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        $controller = new $class();
        call_user_func_array([$controller, $action], $params);
    }
}

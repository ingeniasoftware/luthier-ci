<?php

/**
 * RouteBuilder class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Exception\RouteNotFoundException;

class RouteBuilder
{
    const DEFAULT_CONTROLLER = 'Luthier';

    const HTTP_VERBS = ['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS','TRACE'];

    /**
     * Luthier routes
     *
     * (This us used internally by Luthier-CI)
     *
     * @var static $routes
     *
     * @access private
     */
    private static $routes = [];


    /**
     * Luthier routing context
     *
     * (This us used internally by Luthier-CI)
     *
     * @var static $context
     *
     * @access private
     */
    private static $context = [
        'middleware' =>
        [
            'route'  => [],
            'global' =>
            [
                'pre_controller'  => [],
                'controller'      => [],
                'post_controller' => [],
            ],
        ],
        'namespace' => [],
        'prefix'    => [],
        'params'    => [],
    ];


    /**
     * Compiled routes
     *
     * (This us used internally by Luthier-CI)
     *
     * @var static $compiled
     *
     * @access private
     */
    public static $compiled = [
        'routes'   => [],
        'paths'    => [],
        'names'    => [],
        'reserved' => [],
    ];


    /**
     * Current active route
     *
     * (This us used internally by Luthier-CI)
     *
     * @var static $current
     *
     * @access private
     */
    private static $current;


    /**
     * Custom 404 route
     *
     * It could be both a path to a controller or a callback
     *
     *
     * @var static $_404
     *
     * @access private
     */
    private static $_404;

    /**
     * Method overload used to define routes
     *
     * @param  string $callback Callback name (route HTTP verb name)
     * @param  array  $args route args
     *
     * @return Route
     *
     * @access public
     * @static
     */
    public static function __callStatic($callback, array $args)
    {
        if(is_cli() && $callback != 'cli' || !is_cli() && $callback == 'cli' || (!is_cli() && is_array($callback) && in_array('CLI', $callback)))
        {
            show_error('You only can define cli routes in cli context. Please use Route::cli() method in routes/cli.php file instead');
        }

        if($callback == 'match')
        {
            $methods = $args[0];
        }
        else
        {
            $methods = $callback;
        }

        $route = new Route($methods, $args);

        self::$routes[] = $route;

        return $route;
    }


    /**
     * Creates a route group
     *
     * @param  string          $prefix Group path prefix
     * @param  array|callback  $attributes Group shared attributes/Routes
     * @param  callback        $routes (Optional) Routes
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function group($prefix, $attributes, $routes = null)
    {
        if($routes === null && is_callable($attributes))
        {
            $routes     = $attributes;
            $attributes = [];
        }

        self::$context['prefix'][] = $prefix;

        if(isset($attributes['namespace']))
        {
            self::$context['namespace'][] = $attributes['namespace'];
        }

        if(isset($attributes['middleware']))
        {
            if(is_string($attributes['middleware']))
            {
                $attributes['middleware'] = [ $attributes['middleware'] ];
            }
            else
            {
                if(!is_array($attributes['middleware']))
                {
                    show_error('Route group middleware must be an array o a string');
                }
            }
            self::$context['middleware']['route'][] = $attributes['middleware'];
        }

        call_user_func($routes);

        array_pop(self::$context['prefix']);

        if(isset($attributes['namespace']))
        {
            array_pop(self::$context['namespace']);
        }

        if(isset($attributes['middleware']))
        {
            array_pop(self::$context['middleware']['route']);
        }
    }


    /**
     * Defines a route middleware in a global context
     *
     * @param  string|array  $middleware
     * @param  string $point (Optional) the point of execution of the middleware
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function middleware($middleware, $point = 'pre_controller')
    {
        if(!is_array($middleware))
        {
            $middleware = [ $middleware ];
        }

        foreach($middleware as $_middleware)
        {
            if(!in_array($_middleware, self::$context['middleware']['global'][$point]))
            {
                self::$context['middleware']['global'][$point][] = $_middleware;
            }
        }
    }


    /**
     * Returns an array of all compiled Luthier-CI routes, in the native framework format
     *
     * (This us used internally by Luthier-CI)
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function compileAll()
    {
        $routes = [];

        foreach(self::$routes as $route)
        {
            $routeName = $route->getName();

            if($routeName !== null)
            {
                if(!isset(self::$compiled['names'][$routeName]))
                {
                    self::$compiled['names'][$routeName] = clone $route;
                }
                else
                {
                    show_error('Duplicated "<strong>'. $routeName .'</strong>" named route');
                }
            }

            foreach($route->compile() as $compiled)
            {
                foreach($compiled as $path => $action)
                {
                    foreach($action as $method => $target)
                    {
                        $routes[$path][$method] = $target;

                        $routePlaceholders = RouteParam::getPlaceholderReplacements();
                        $regexPath = implode('\\/', explode('/', $path));
                        $regexPath = preg_replace(array_keys($routePlaceholders), array_values($routePlaceholders), $regexPath);
                        self::$compiled['paths']['#^' . $regexPath . '$#'][] = clone $route;
                    }
                }
            }
        }

        $routes['default_controller']   = isset(self::$compiled['reserved']['default_controller']) ?
            self::$compiled['reserved']['default_controller'] : null;

        $routes['translate_uri_dashes'] = isset(self::$compiled['reserved']['translate_uri_dashes']) ?
            self::$compiled['reserved']['translate_uri_dashes'] : FALSE;

        $routes['404_override'] = isset(self::$compiled['reserved']['404_override']) ?
            self::$compiled['reserved']['404_override'] : '';

        self::$compiled['routes'] = $routes;
    }


    /**
     * Add a compiled (CodeIgniter Format) route at boot time
     *
     * (This us used internally by Luthier-CI)
     *
     * @param  string  $path
     * @param  string  $target (Optional)
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function addCompiledRoute($path, $target = null)
    {
        if($target === null)
        {
            $target = self::DEFAULT_CONTROLLER . '/index';
        }

        self::$compiled['routes'][$path] = $target;
    }

    /**
     * Defines a CodeIgniter reserved route or custom Luthier configuration
     *
     * @param  string  $name
     * @param  mixed   $value
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function set($name, $value)
    {
        if(!in_array($name, ['404_override','default_controller','translate_uri_dashes']))
        {
            throw new \Exception('Unknown reserved route "' . $name . '"');
        }

        if($name == '404_override' && is_callable($value))
        {
            self::$_404 = $value;
            $value = '';
        }

        self::$compiled['reserved'][$name] = $value;
    }


    /**
     * Defines all auth-related routes/middleware
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function auth()
    {
        //
        // Auth routes (Login, logout, etc.)
        //

        self::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
        self::post('logout', 'AuthController@logout')->name('logout');
        self::match(['get', 'post'], 'signup', 'AuthController@signup')->name('signup');
        self::group('password-reset', function(){
            self::match(['get','post'], '/', 'AuthController@passwordReset')->name('password_reset');
            self::get('{token}', 'AuthController@passwordResetForm')->name('password_reset_form');
        });

        //
        // User area
        //

        self::group('dashboard', ['namespace' => 'user_area'], function(){
           self::get('/', 'DashboardController@index')->name('dashboard');
        });
    }


    /**
     * Get route by url
     *
     * (This us used internally by Luthier-CI)
     *
     * @param string $path Current URI url
     *
     * @return Route
     *
     * @throws RouteNotFoundException
     * @access public
     * @static
     */
    public static function getByUrl($url, $requestMethod = null)
    {
        if($requestMethod === null || empty($requestMethod))
        {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : (!is_cli() ? 'GET' : 'CLI');
        }
        else
        {
            $requestMethod = strtoupper($requestMethod);
        }

        // First, look for a direct match:
        $_url = '#^' . str_replace('/', '\\/', $url) . '$#';

        if(isset(self::$compiled['paths'][$_url]))
        {
            foreach(self::$compiled['paths'][$_url] as $route)
            {
                if(in_array($requestMethod, $route->getMethods()))
                {
                    return $route;
                }
            }
        }

        // Then, loop into the array of compiled path
        foreach(self::$compiled['paths'] as $path => $routes)
        {
            if(preg_match($path, $url))
            {
                foreach($routes as $route)
                {
                    if(in_array($requestMethod, $route->getMethods()))
                    {
                        return $route;
                    }
                }
            }
        }

        throw new RouteNotFoundException;
    }


    /**
     * Get route by name
     *
     * @param  string  $name Route name
     *
     * @return Route
     *
     * @throws RouteNotFoundException
     * @access public
     * @static
     */
    public static function getByName($name)
    {
        if(isset(self::$compiled['names'][$name]))
        {
            return self::$compiled['names'][$name];
        }

        throw new RouteNotFoundException;
    }


    /**
     * Get all compiled routes (CodeIgniter format)
     *
     * (This us used internally by Luthier-CI)
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getRoutes()
    {
        return self::$compiled['routes'];
    }


    /**
     * Get the current active route
     *
     * (This us used internally by Luthier-CI)
     *
     * @return Route
     *
     * @access public
     * @static
     */
    public static function getCurrentRoute()
    {
        return self::$current;
    }


    /**
     * Get global middleware
     *
     * (This us used internally by Luthier-CI)
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getGlobalMiddleware()
    {
        return self::$context['middleware']['global'];
    }


    /**
     * Get the current custom 404 route
     *
     * (This us used internally by Luthier-CI)
     *
     * @return string|callback
     *
     * @access public
     * @static
     */
    public static function get404()
    {
        if(self::$_404 !== null)
        {
            return self:: $_404;
        }

        return isset(self::$compiled['reserved']['404_override']) ?
            self::$compiled['reserved']['404_override'] : null;
    }

    public static function getContext($context)
    {
        return self::$context[$context];
    }


    /**
     * Set the current active route
     *
     * (This us used internally by Luthier-CI)
     *
     * @param  Route $route
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function setCurrentRoute(Route $route)
    {
        self::$current = $route;
    }


    /**
     * Set a default global parameter
     *
     * @param  string  $name
     * @param  string   $value
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function setDefaultParam($name, $value)
    {
        self::$context['params'][$name] = $value;
    }


    /**
     * Get default global route params
     *
     * (This is used internally by Luthier)
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getDefaultParams()
    {
        return self::$context['params'];
    }
}
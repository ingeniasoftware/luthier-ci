<?php

/**
 * Route class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Exception\RouteNotFoundException;

class Route
{

    const DEFAULT_CONTROLLER = 'Luthier';

    const HTTP_VERBS = ['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS','TRACE'];


    /**
     * Luthier routes
     *
     * @var static $routes
     *
     * @access private
     */
    private static $routes = [];


    /**
     * Luthier routing context
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
     * @var static $compiled
     *
     * @access private
     */
    private static $compiled = [
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
     * @var static $_404
     *
     * @access private
     */
    private static $_404;


    /**
     * Route path (without prefix)
     *
     * @var $path
     *
     * @access private
     */
    private $path;


    /**
     * Route name
     *
     * @var $name
     *
     * @access private
     */
    private $name;


    /**
     * Route accepted methods (HTTP Verbs)
     *
     * @var $methods
     *
     * @access private
     */
    private $methods = [];


    /**
     * Route action, can be both a 'controller@method' string or a valid callback
     *
     * @var $action
     *
     * @access private
     */
    private $action;


    /**
     * Route middleware
     *
     * @var $middleware
     *
     * @access private
     */
    private $middleware = [];


    /**
     * Route pseudo-namespace  (is actually the directory path to the controller)
     *
     * @var $namespace
     *
     * @access private
     */
    private $namespace = '';


    /**
     * Route prefix
     *
     * @var $prefix
     *
     * @access private
     */
    private $prefix = '';


    /**
     * Array of route parameter objects (Luthier\RouteParam)
     *
     * @var $params
     *
     * @access public
     */
    public $params = [];



    /**
     * Route segment where starts the parameters
     *
     * @var $paramOffset
     *
     * @access public
     */
    public $paramOffset;


    /**
     * Does the route have optional parameters?
     *
     * @var $hasOptionalParams
     *
     * @access private
     */
    private $hasOptionalParams = false;


    /**
     * Is the current route a 404 page?
     *
     * @var $is404
     *
     * @access private
     */
    public $is404 = false;


    /**
     * Is the current route a CLI route
     *
     * @var $isCli
     *
     * @access public
     */
    public $isCli = false;


    /**
     * Current request method
     *
     * @var $method
     *
     * @access public
     */
    public $method;


    /**
     * Class constructor
     *
     * @param  string|array $methods HTTP Verbs
     * @param  array|callable $route Route definition
     *
     * @return void
     * @access public
     */
    public function __construct($methods, $route)
    {
        if($methods == 'any')
        {
            $methods = self::HTTP_VERBS;
        }
        elseif(is_string($methods))
        {

            $methods = [ strtoupper($methods) ];
        }
        else
        {
            array_shift($route);
        }

        foreach($methods as $method)
        {
            $this->methods[] = strtoupper($method);
        }

        // Required route attributes
        list($path, $action) = $route;
        $this->path = trim($path, '/') == '' ? '/' : trim($path, '/');

        if(!is_callable($action) && count(explode('@', $action)) != 2)
        {
            show_error('Route action must be in <strong>controller@method</strong> syntax or be a valid callback');
        }

        $this->action = $action;
        $attributes = isset($route[2]) && is_array($route[2]) ? $route[2] : NULL;

        // Route group inherited attributes
        if(!empty(self::$context['prefix']))
        {
            $prefixes = self::$context['prefix'];
            foreach($prefixes as $prefix)
            {
                $this->prefix .= '/' .trim($prefix, '/');
            }
            $this->prefix = trim($this->prefix,'/');
        }

        if(!empty(self::$context['namespace']))
        {
            $namespaces = self::$context['namespace'];
            foreach($namespaces as $namespace)
            {
                $this->namespace .= '/' .trim($namespace, '/');
            }
            $this->namespace = trim($this->namespace,'/');
        }

        if(!empty(self::$context['middleware']['route']))
        {
            $middlewares = self::$context['middleware']['route'];
            foreach($middlewares as $middleware)
            {
                if(!in_array($middleware, $this->middleware))
                {
                    $this->middleware[] = $middleware;
                }
            }
        }

        // Optional route attributes
        if($attributes !== NULL)
        {
            if(isset($attributes['namespace']))
            {
                $this->namespace = (!empty($this->namespace) ? '/' : '' ) . trim($attributes['namespace'], '/');
            }

            if(isset($attributes['prefix']))
            {
                $this->prefix .= (!empty($this->prefix) ? '/' : '' ) . trim($attributes['prefix'], '/');
            }

            if(isset($attributes['middleware']))
            {
                if(is_string($attributes['middleware']))
                {
                    $attributes['middleware'] = [ $attributes['middleware'] ];
                }

                $this->middleware = array_merge($this->middleware, array_unique($attributes['middleware']));
            }
        }

        // Parsing route parameters
        $_names   = [];
        $fullPath = trim($this->prefix,'/') != '' ? $this->prefix . '/' . $this->path : $this->path;
        $fullPath = trim($fullPath, '/') == '' ? '/' : trim($fullPath, '/');

        foreach(explode('/', $fullPath) as $i => $segment)
        {
            if(preg_match('/^\{(.*)\}$/', $segment))
            {
                if($this->paramOffset === null)
                {
                    $this->paramOffset = $i;
                }

                $param  = new RouteParam($segment);

                if(in_array($param->getName(), $_names))
                {
                    show_error('Duplicate route parameter <strong>' . $param->getName() . '</strong> in route <strong>"' .  $this->path . '</strong>"');
                }

                $_names[] = $param->getName();

                if( $param->isOptional() )
                {
                    $this->hasOptionalParams = true;
                }
                else
                {
                    if( $this->hasOptionalParams )
                    {
                        show_error('Required <strong>' . $param->getName() . '</strong> route parameter is not allowed at this position in <strong>"' . $this->path . '"</strong> route');
                    }
                }

                $this->params[] = $param;
            }
        }

        // Automatically set the default controller if path is "/"
        if($fullPath == '/' && in_array('GET', $this->methods))
        {
            self::$compiled['reserved']['default_controller'] = is_string($action)
                ?
                    ( empty($this->namespace) ? implode('/', explode('@', $action)) : self::DEFAULT_CONTROLLER )
                :
                    self::DEFAULT_CONTROLLER;
        }

        $this->isCli = is_cli();
    }


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
                if(in_array($requestMethod, $route->methods))
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
                    if(in_array($requestMethod, $route->methods))
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
     * Compiles a Luthier-CI route into a CodeIgniter native route
     *
     * (This is used internally by Luthier)
     *
     * @param  string $currentMethod (Optional) Current HTTP Verb
     *
     * @return array
     *
     * @access public
     */
    public function compile($currentMethod = null )
    {
        $routes    = [];

        if($currentMethod === null)
        {
            if(is_array($this->methods) && !empty($this->methods))
            {
                $methods = $this->methods;
            }
            else
            {
                $methods = self::HTTP_VERBS;
            }
        }
        else
        {
            $methods = [ $currentMethod ];
        }

        foreach($methods as $verb)
        {
            $path   = $this->path;
            $target = null;

            if(!empty($this->prefix))
            {
                $path = trim($this->prefix . '/' . $path,'/');
            }

            if(is_callable($this->action))
            {
                $target = self::DEFAULT_CONTROLLER;
            }
            else
            {
                list($controller, $method) = explode('@', $this->action);
                $target = $controller . '/' . $method;

                if(!empty($this->namespace))
                {
                    $target = $this->namespace . '/' . $target;
                }

                foreach($this->params as $c => $param)
                {
                    $target .= '/$'.++$c;
                }
            }

            foreach($this->params as $param)
            {
                $path = str_ireplace($param->getSegment(), $param->getPlaceholder(), $path);
            }

            if($this->hasOptionalParams && $currentMethod === null)
            {
                $route = clone $this;

                do
                {
                    $param = array_pop($route->params);

                    if($param === null || !$param->isOptional())
                    {
                        $route->hasOptionalParams = false;
                        break;
                    }

                    $isOptional = $param->isOptional();
                    $routePath  = $route->getPath();
                    $routePath  = explode('/', $routePath);

                    array_pop($routePath);

                    $route->setPath(implode('/', $routePath));

                    $subRoute = $route->compile($verb);
                    $_path    = key($subRoute[0]);
                    $_target  = $subRoute[0][key($subRoute[0])][$verb];

                    $routes[][$_path][$verb] =  $_target;

                } while( $isOptional );
            }

            $routes[][$path][$verb] = $target;
        }

        $last = array_pop($routes);
        array_unshift($routes, $last);
        $routes = array_reverse($routes);

        return $routes;
    }


    /**
     * Get or set a route parameter
     *
     * @param  string  $name Parameter name
     * @param  string  $value (Optional) Parameter value
     *
     * @return null|string
     *
     * @access public
     */
    public function param($name, $value = null)
    {
        foreach($this->params as &$_param)
        {
            if($name == $_param->getName())
            {
                if($value !== null)
                {
                    $_param->value = $value;
                }
                return $_param->value;
            }
        }
    }


    /**
     * Check if the route has a specific parameter
     *
     * @param  string  $name
     *
     * @return bool
     *
     * @access public
     */
    public function hasParam($name)
    {
        foreach($this->params as &$_param)
        {
            if($name == $_param->getName())
            {
                return true;
            }
        }
        return false;
    }


    /**
     * Build the route url with the provided parameters
     *
     * @param  string|array $params Route parameters
     *
     * @return sting
     *
     * @access public
     */
    public function buildUrl($params)
    {
        $defaultParams = self::getDefaultParams();

        if(is_string($params))
        {
            $params = [ '*' => $params ];
        }
        else
        {
            if(!is_array($params))
            {
                $params = [];
            }
        }

        $path = $this->getPrefix() . '/' . $this->getPath();

        foreach($this->params as &$param)
        {
            $name = $param->getName();

            if(!$param->isOptional())
            {
                if(!isset($defaultParams[$name]) && !isset($params[$param->getName()]))
                {
                    throw new \Exception('Missing "' . $name .'" parameter for "' . $this->getName() . '" route');
                }

                if(isset($defaultParams[$name]))
                {
                    $param->value = $defaultParams[$param->getName()];
                }

                if(isset($params[$param->getName()]))
                {
                    $param->value = $params[$param->getName()];
                }

                $path = str_replace($param->getSegment(), $param->value, $path);
            }
            else
            {
                $_path = explode('/', $path);
                array_pop($_path);
                $path = implode('/', $_path);
                array_pop($this->params);
            }
        }

        return base_url() . trim($path,'/');
    }


    /**
     * Fluent name setter for a route
     *
     * @param  string $name
     *
     * @return Route
     * @access public
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }


    /**
     * Get route name
     *
     * @return string
     *
     * @access public
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set route name
     *
     * @param  string  $name
     *
     * @return void
     *
     * @access public
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * Get route path
     *
     * @return string
     *
     * @access public
     */
    public function getPath()
    {
        return $this->path;
    }


    /**
     * Set route path
     *
     * @param  string  $path
     *
     * @return void
     *
     * @access public
     */
    public function setPath($path)
    {
        $this->path = $path;
    }


    /**
     * Get route prefix
     *
     * @return string
     *
     * @access public
     */
    public function getPrefix()
    {
        return $this->prefix;
    }


    /**
     * Get route action
     *
     * @return string|callback
     *
     * @access public
     */
    public function getAction()
    {
        return $this->action;
    }


    /**
     * Set route action
     *
     * @param  string|callback   $action
     *
     * @return void
     *
     * @access public
     */
    public function setAction($action)
    {
        $this->action = $action;
    }


    /**
     * Get route middleware
     *
     * @return array
     *
     * @access public
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }


    /**
     * Get route namespace
     *
     * @return string
     *
     * @access public
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
}
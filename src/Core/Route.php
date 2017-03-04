<?php

/**
 * Route class
 *
 * @author    Anderson Salas <me@andersonsalas.com.ve>
 * @copyright 2017
 * @license   GNU-3.0
 *
 */

namespace Luthier\Core;

class Route
{

    /**
     * Supported HTTP Verbs for this class
     *
     * @var static array $http_verbs Array of supported HTTP Verbs
     *
     * @access protected
     */
    protected static $http_verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'CONNECT', 'HEAD'];


    /**
     * All improved routes parsed
     *
     * @var static array $routes Array of routes with meta data
     *
     * @access protected
     */
    protected static $routes = array();


    /**
     * CodeIgniter 'default_controller' index of the $route variable in config/routes.php
     *
     * @var static $defaultController
     *
     * @access protected
     */
    protected static $defaultController;


    /**
     * CodeIgniter '404_override' index of the $route variable in config/routes.php
     *
     * @var static $_404page
     *
     * @access protected
     */
    protected static $_404page = NULL;


    /**
     * CodeIgniter 'translate_uri_dashes' index of the $route variable in config/routes.php
     *
     * @var static $translateDashes
     *
     * @access protected
     */
    protected static $translateDashes = FALSE;


    /**
     * Array of hidden routes, it will parsed as an route with a show_404() callback into a clousure
     *
     * @var static $hiddenRoutes
     *
     * @access protected
     */
    protected static $hiddenRoutes = array();


    /**
     * Route group '$hideOriginal' directive
     *
     * @var static $hideOriginals
     *
     * @access protected
     */
    protected static $hideOriginals = [];


    /**
     * Route group prefix
     *
     * @var static $prefix
     *
     * @access protected
     */
    protected static $prefix = [];


    /**
     * Route group namespace
     *
     * @var static $namespace
     *
     * @access protected
     */
    protected static $namespace = [];


    /**
     * Route group middleware
     *
     * @var static $middleware [add description]
     *
     * @access protected
     */
    protected static $middleware = [];


    /**
     * Generic method to add a improved route
     *
     * @param  mixed $verb String or array of string of valid HTTP Verbs that will be accepted in this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function add($verb, $path, $attr, $hideOriginal = TRUE, $return = FALSE)
    {
        if (!is_array($attr))
        {
            show_error('You must specify the route attributes as an array', 500, 'Route error: bad attributes');
        }

        if (!isset($attr['uses']))
        {
            show_error('Route requires a \'controller@method\' to be pointed and it\'s not defined in the route attributes', 500, 'Route error: missing controller');
        }

        if (!preg_match('/^([a-zA-Z1-9-_]+)@([a-zA-Z1-9-_]+)$/', $attr['uses'], $parsedController) !== FALSE)
        {
            show_error('Route controller must be in format controller@method', 500, 'Route error: bad controller format');
        }

        $controller = $parsedController[1];
        $method     = $parsedController[2];

        if (!is_string($path))
            show_error('Route path must be a string ', 500, 'Route error: bad route path');

        if (!is_string($verb))
            show_error('Route HTTP Verb must be a string', 500, 'Route error: bad verb type');

        $verb = strtoupper($verb);

        if (!in_array($verb, self::$http_verbs, TRUE))
        {
            $errorMsg = 'Verb "'.$verb.'" is not a valid HTTP Verb, allowed verbs:<ul>';
            foreach (self::$http_verbs as $validVerb)
            {
                $errorMsg .= '<li>'.$validVerb.'</li>';
            }
            $errorMsg .= '</ul>';
            show_error($errorMsg, 500, 'Route error: unknow method');
        }

        $route['verb'] = $verb;

        $path = trim($path, '/');

        $route['path']       = $path;
        $route['controller'] = $controller;
        $route['method']     = $method;

        if (isset($attr['as']))
        {
            $route['name'] = $attr['as'];
        } else
        {
            $route['name'] = NULL;
        }

        // Setting up the prefix

        $route['prefix'] = NULL;
        $group_prefix = implode('/', self::$prefix);

        if ($group_prefix)
            $route['prefix'] = $group_prefix.'/';

        if (isset($attr['prefix']))
            $route['prefix'] .= $attr['prefix'];

        // Setting up the namespace

        $route['namespace'] = NULL;
        $group_namespace = implode('/', self::$namespace);

        if (!is_null($group_namespace))
            $route['namespace'] = $group_namespace.'/';
        if (isset($attr['namespace']))
            $route['namespace'] .= $attr['namespace'];

        $route['prefix']    = trim($route['prefix'], '/');
        $route['namespace'] = trim($route['namespace'], '/');

        if (empty($route['prefix']))
            $route['prefix'] = NULL;

        if (empty($route['namespace']))
            $route['namespace'] = NULL;

        // Route middleware
        $route['middleware'] = [];
        $route['middleware'] = array_merge($route['middleware'], self::$middleware);

        if (isset($attr['middleware']))
        {
            if (is_array($attr['middleware']))
            {
                foreach ($attr['middleware'] as $middleware)
                    $route['middleware'][] = $middleware; # Group
            }
            elseif (is_string($attr['middleware']))
            {
                $route['middleware'][] = $attr['middleware']; # Group
            }
            else
            {
                show_error('Route middleware must be a string or an array', 500, 'Route error: bad middleware format');
            }
        }

        $compiledRoute = self::compileRoute((object) $route);

        $route['compiled'] =
            [
                $compiledRoute->path => $compiledRoute->route
            ];

        $groupHideOriginals = end(self::$hideOriginals);

        if ($hideOriginal || $groupHideOriginals || ($compiledRoute->path != '' && $compiledRoute->path != '/'))
        {
            $hiddenRoutePath      = $controller.'/'.$method;
            $hiddenRouteNamespace = '';

            if (!is_null($route['namespace']))
            {
                $hiddenRouteNamespace = $route['namespace'].'/';
            }

            $hiddenRoutePath = $hiddenRouteNamespace.$hiddenRoutePath;

            if ($method == 'index')
            {
                self::$hiddenRoutes[] = [$hiddenRouteNamespace.$controller  => function() { self::trigger404(); }];
            }

            self::$hiddenRoutes[] = [$hiddenRoutePath => function() { self::trigger404(); }];
        }

        if (!$return)
        {
            self::$routes[] = (object) $route;
        } else
        {
            return (object) $route;
        }
    }


    /**
     * Adds a GET route, alias of Route::add('GET',$url,$attr,$hideOriginal)
     *
     * @param  string $url String or array of strings that will trigger this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function get($url, $attr, $hideOriginal = TRUE)
    {
        self::add('GET', $url, $attr, $hideOriginal);
    }


    /**
     * Adds a POST route, alias of Route::add('POST',$url,$attr,$hideOriginal)
     *
     * @param  string $url String or array of strings that will trigger this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function post($url, $attr, $hideOriginal = TRUE)
    {
        self::add('POST', $url, $attr, $hideOriginal);
    }


    /**
     * Adds a PUT route, alias of Route::add('PUT',$url,$attr,$hideOriginal)
     *
     * @param  mixed $url String or array of strings that will trigger this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function put($url, $attr, $hideOriginal = TRUE)
    {
        self::add('PUT', $url, $attr, $hideOriginal);
    }


    /**
     * Adds a PATCH route, alias of Route::add('PATCH',$url,$attr,$hideOriginal)
     *
     * @param  mixed $url String or array of strings that will trigger this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function patch($url, $attr, $hideOriginal = TRUE)
    {
        self::add('PATCH', $url, $attr, $hideOriginal);
    }


    /**
     * Adds a DELETE route, alias of Route::add('DELETE',$url,$attr,$hideOriginal)
     *
     * @param  string $url String or array of strings that will trigger this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function delete($url, $attr, $hideOriginal = TRUE)
    {
        self::add('DELETE', $url, $attr, $hideOriginal);
    }


    /**
     * Adds a route with ALL accepted verbs on Route::$http_verbs
     *
     * @param  mixed $url String or array of strings that will trigger this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function any($url, $attr, $hideOriginal = TRUE)
    {
        foreach (self::$http_verbs as $verb)
        {
            $verb = strtolower($verb);
            self::add($verb, $url, $attr, $hideOriginal);
        }
    }


    /**
     * Adds a list of routes with the verbs contained in $verbs, alias of Route::add($verbs,$url,$attr,$hideOriginal)
     *
     * @param  string[] $verbs String or array of string of valid HTTP Verbs that will be accepted in this route
     * @param  string $url String or array of strings that will trigger this route
     * @param  array $attr Associative array of route attributes
     * @param  bool $hideOriginal (Optional) map the original $url as a route with a show_404() callback inside
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function matches($verbs, $url, $attr, $hideOriginal = FALSE)
    {
        if (!is_array($verbs))
            show_error('Route::matches() first argument must be an array of valid HTTP Verbs', 500, 'Route error: bad Route::matches() verb list');

        foreach ($verbs as $verb)
        {
            self::add($verb, $url, $attr, $hideOriginal);
        }
    }


    /**
     * Adds a RESTFul route wich contains methods for create, read, update, view an specific resource
     *
     *
     * @param  string $name
     * @param  string $controller
     * @param  array $attr
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function resource($name, $controller, $attr = NULL)
    {
        $base_attr = [];

        $hideOriginal = FALSE;

        if (isset($attr['namespace']))
            $base_attr['namespace']  = $attr['namespace'];

        if (isset($attr['middleware']))
            $base_attr['middleware'] = $attr['middleware'];

        if (isset($attr['hideOriginal']))
            $hideOriginal = (bool) $attr['hideOriginal'];

        $base_attr['prefix'] = strtolower($name);

        if (isset($attr['prefix']))
            $base_attr['prefix'] = $attr['prefix'];

        $only = [];

        $controller = strtolower($controller);

        if (isset($attr['only']) && (is_array($attr['only']) || is_string($attr['only'])))
        {
            if (is_array($attr['only']))
            {
                $only = $attr['only'];
            }
            else
            {
                $only[] = $attr['only'];
            }
        }

        if (empty($only) || in_array('index', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@index', 'as' => $name.'.index']);
            self::get('/', $route_attr, $hideOriginal);
        }

        if (empty($only) || in_array('create', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@create', 'as' => $name.'.create']);
            self::get('create', $route_attr, $hideOriginal);
        }

        if (empty($only) || in_array('store', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@store', 'as' => $name.'.store']);
            self::post('/', $route_attr, $hideOriginal);
        }

        if (empty($only) || in_array('show', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@show', 'as' => $name.'.show']);
            self::get('{slug}', $route_attr, $hideOriginal);
        }

        if (empty($only) || in_array('edit', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@edit', 'as' => $name.'.edit']);
            self::get('{slug}/edit', $route_attr, $hideOriginal);
        }

        if (empty($only) || in_array('update', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@update', 'as' => $name.'.update']);
            self::matches(['PUT', 'PATCH'], '{slug}/update', $route_attr, $hideOriginal);
        }

        if (empty($only) || in_array('destroy', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@destroy', 'as' => $name.'.destroy']);
            self::delete('{slug}', $route_attr, $hideOriginal);
        }
    }


    /**
     * Compiles an improved route to a valid CodeIgniter route
     *
     * @param  array $route an improved route
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function compileRoute($route)
    {
        $prefix    = NULL;
        $namespace = NULL;

        if (!is_null($route->prefix))
        {
            $prefix = $route->prefix;
        }

        if (!is_null($route->namespace))
        {
            $namespace = $route->namespace;
        }

        $path = $route->path;

        if (!is_null($prefix))
            $path = $prefix.'/'.$path;

        $controller = $route->controller.'/'.$route->method;

        if (!is_null($namespace))
            $controller = $namespace.'/'.$controller;

        $path       = trim($path, '/');
        $controller = trim($controller, '/');
        $baseController = $controller;

        $replaces =
            [
                '{\((.*)\):[a-zA-Z0-9-_]*(\?}|})' => '($1)', # Custom regular expression
                '{num:[a-zA-Z0-9-_]*(\?}|})'      => '(:num)', # (:num) route
                '{any:[a-zA-Z0-9-_]*(\?}|})'      => '(:any)', # (:any) route
                '{[a-zA-Z0-9-_]*(\?}|})'          => '(:any)', # Everything else
            ];

        $foundedArgs = [];
        $basePath    = '';


        foreach (explode('/', $path) as $path_segment)
        {
            if (!preg_match('/^\{(.*)\}$/', $path_segment))
            {
                $basePath .= $path_segment.'/';
            }
        }

        $basePath = trim($basePath, '/');

        $segments = explode('/', $path);

        foreach ($segments as $key => &$segment)
        {
            $customRegex = FALSE;

            foreach ($replaces as $regex => $replace)
            {
                if($customRegex)
                    continue;

                $matches = [];

                if(preg_match('/^\{(.*)\}$/', $segment))
                {
                    $foundedArgs[$key] = $segment;
                }

                $c = 0;
                $segment = preg_replace('/'.$regex.'/', $replace, $segment, 1, $c);

                if( $regex == array_keys($replaces)[0] && $c > 0)
                    $customRegex = TRUE;
            }
        }

        $path = implode('/', $segments);

        $argConstraint = FALSE;

        $args = [];
        $args['required'] = [];
        $args['optional'] = [];

        foreach ($foundedArgs as $arg)
        {
            if (substr($arg, -2) == '?}')
            {
                $args['optional'][] = $arg;
                $argConstraint = TRUE;
            }
            else
            {
                if ($argConstraint)
                    show_error('Optional route path argument not valid at this position', 500, 'Route error');
                $args['required'][] = $arg;
            }
        }

        $c_foundedArgs = count($foundedArgs);

        if ($c_foundedArgs > 0)
        {
            for ($i = 0; $i < $c_foundedArgs; $i++)
            {
                $controller .= '/$'.($i + 1);
            }
        }

        return (object) [
            'path'      => $path,
            'route'     => $controller,
            'args'      => $args,
            'baseRoute' => $baseController,
            'basePath'  => $basePath
        ];
    }


    /**
     * Compile ALL improved routes into a valid CodeIgniter's associative array of routes
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function register()
    {
        $routes = array();

        foreach (self::$routes as $index => $route)
        {
            $compiled = self::compileRoute($route);

            $backtrackingPath  = '';
            $backtrackingRoute = '';

            $c_reqArgs = count($compiled->args['required']);
            $c_optArgs = count($compiled->args['optional']);

            if ($c_optArgs > 0)
            {
                $e_path  = explode('/', $compiled->path);
                $e_route = explode('/', $compiled->route);

                $basePath = $compiled->basePath;
                $baseRoute = $compiled->baseRoute;

                $a = count(explode('/', $basePath));

                for ($r = 0; $r < $c_reqArgs; $r++)
                {
                    $basePath .= '/'.$e_path[$a + $r];
                    $baseRoute .= '/'.'$'.($r + 1);
                }

                $a = count(explode('/', $basePath));
                $b = ($r + 1);

                $backtracking = [];

                for ($o = 0; $o <= $c_optArgs; $o++)
                {
                    $backtrackingPath  = $basePath;
                    $backtrackingRoute = $baseRoute;

                    for ($c = 0; $c < $o; $c++)
                    {
                        $backtrackingPath .= '/'.$e_path[$a + $c - 1];
                        $backtrackingRoute .= '/'.'$'.($b + $c);
                    }

                    $backtracking[$o] = ['path' => $backtrackingPath, 'route' => $backtrackingRoute];
                }

                foreach ($backtracking as $b_route)
                {
                    $b_compiled   = self::compileRoute($route);
                    $b_args       = array_merge($b_compiled->args['required'], $b_compiled->args['optional']);
                    $b_route_path = $b_route['path'];

                    foreach ($b_args as $arg)
                    {
                        $b_route_path = preg_replace('/\((.*?)\)/', $arg, $b_route_path, 1);
                    }

                    self::add($route->verb, $b_route_path, ['uses' => $route->controller.'@'.$route->method]);

                    if (!isset($routes[$b_route['path']]) || $route->verb == 'GET')
                    {
                        $routes[$b_route['path']] = $b_route['route'];
                    }
                }
            }

            if (!isset($routes[$compiled->path]) || $route->verb == 'GET')
            {
                $routes[$compiled->path] = $compiled->route;
            }

            self::$routes[$index] = (object) $route;
        }

        foreach (self::$hiddenRoutes as $route)
        {
            $path = key($route);
            $_404 = $route[$path];

            if (!isset($routes[$path]))
                $routes[$path] = $_404;
        }

        if (is_null(self::$defaultController))
            show_error('You must specify a home route: Route::home() as default controller!', 500, 'Route error: missing default controller');

        $defaultController = self::$defaultController->compiled;
        $defaultController = $defaultController[key($defaultController)];

        $routes['default_controller'] = $defaultController;

        if (is_null(self::$_404page))
        {
            $routes['404_override'] = '';
        } else
        {
            $routes['404_override'] = self::$_404page->controller;
        }

        $routes['translate_uri_dashes'] = self::$translateDashes;

        return $routes;
    }


    /**
     * Creates a group of routes with common attributes
     *
     * @param  array $attr set of global attributes
     * @param  callback $routes wich contains a set of Route methods
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function group($attr, $routes)
    {
        if (!is_array($attr))
            show_error('Group attribute must be a valid array');

        if (!isset($attr['prefix']))
            show_error('You must specify an prefix!');

        self::$prefix[] = $attr['prefix'];

        if (isset($attr['namespace']))
        {
            self::$namespace[] = $attr['namespace'];
        }

        if (isset($attr['hideOriginals']) && $attr['hideOriginals'] === TRUE)
        {
            self::$hideOriginals[] = TRUE;
        } else
        {
            self::$hideOriginals[] = FALSE;
        }

        $mcount = 0;
        if (isset($attr['middleware']))
        {
            if (is_array($attr['middleware']) || is_string($attr['middleware']))
            {
                if (is_array($attr['middleware']) && !empty($attr['middleware']))
                {
                    $mcount = count($attr['middleware']);
                    foreach ($attr['middleware'] as $middleware)
                        self::$middleware[] = $middleware;
                }
                else
                {
                    self::$middleware[] = $attr['middleware'];
                    $mcount = 1;
                }
            }
            else
            {
                show_error('Group middleware must be an array o a string', 500, 'Route error');
            }
        }

        $res = $routes->__invoke();

        array_pop(self::$prefix);
        array_pop(self::$namespace);
        array_pop(self::$hideOriginals);

        // Flushing nested middleware:
        for($i = 0; $i < $mcount; $i++)
            array_pop(self::$middleware);
    }


    /**
     * Creates the 'default_controller' key in CodeIgniter's route array
     *
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function home($controller, $as = 'home', $attr = NULL)
    {
        $routeAttr =
            [
                'uses' => $controller,
                'as'   => $as
            ];

        if (!is_null($attr) && !is_array($attr))
            show_error('Default controller attributes must be an array', 500, 'Route error: bad attribute type');

        if (!is_null($attr))
            $routeAttr = array_merge($routeAttr, $attr);

        if (isset($attr['prefix']))
            show_error('Default controller may not have a prefix!', 500, 'Route error: prefix not allowed');

        self::$defaultController = self::$routes[] = self::add('GET', '/', ['uses' => $controller, 'as' => $as], TRUE, TRUE);
    }


    /**
     * Get all the improved routes defined
     *
     * @return array List of all defined routes
     *
     * @access public
     * @static
     */
    public static function getRoutes($verb = NULL)
    {
        if (is_null($verb))
        {
            return self::$routes;
        }
        else
        {
            $routes = [];
            foreach (self::$routes as $route)
            {
                if ($route->verb == $verb)
                    $routes[] = $route;
            }
            return $routes;
        }
    }


    /**
     * Get all hidden routes
     *
     * @return Route
     *
     * @access public
     * @static
     */
    public static function getHiddenRoutes()
    {
        return self::$hiddenRoutes;
    }


    /**
     * Retrieve a route wich is called $search (if exists)
     *
     * @param  string $search The route name to search
     * @param  $args (Optional) The route arguments that will be parsed
     *
     * @return mixed Founded route in case of success, and error in case of no matches.
     *
     * @access public
     * @static
     */
    public static function getRouteByName($search)
    {
        $founded = NULL;

        $args = func_get_args();
        unset($args[0]);

        foreach (self::$routes as $route)
        {
            if ($route->name == $search)
            {
                $founded = $route;
            }
        }

        if (!is_null($founded))
        {
            $routeArgs        = self::compileRoute($founded)->args;
            $routeArgCount    = count($routeArgs['required']) + count($routeArgs['optional']);
            $routeReqArgCount = count($routeArgs['required']);

            if (count($args) < $routeReqArgCount)
            {
                $missingArgs = $routeReqArgCount - count($args);
                throw new \Exception('Missing '.$missingArgs.' required argument'.($missingArgs != 1 ? 's' : '').' for route "'.$founded->name.'"');
            }
            if (count($args) > $routeArgCount)
            {
                throw new \Exception('The route "'.$founded->name.'" expects maximum '.$routeArgCount.' argument'.($routeArgCount != 1 ? 's' : '').', '.count($args).' provided');
            }

            $path = self::compileRoute($founded)->path;

            foreach ($args as $replacement)
            {
                $path = preg_replace('/\((.*?)\)/', $replacement, $path, 1);
            }

            $argsLeft = $routeArgCount - count($args);

            for ($i = $argsLeft; $i >= 0; $i--)
            {
                $path = preg_replace('/\((.*?)\)/', '', $path, 1);
            }

            return base_url(trim($path, '/'));
        }

        throw new \Exception('The route "'.$search.'" is not defined');
    }


    /**
     *  Heuristic testing of current uri_string in compiled routes
     *
     *  This is the 'reverse' process of the improved routing, it'll take the current
     *  uri string and attempts to find a CodeIgniter route that matches with his pattern
     *
     *
     * @param Middleware $path
     * @param string $requestMethod
     * @return mixed
     */
    public static function getRouteByPath($path, $requestMethod = NULL)
    {
        if (is_null($requestMethod))
            $requestMethod = $_SERVER['REQUEST_METHOD'];

        $routes = self::getRoutes($requestMethod);

        if (empty($routes))
            return FALSE;

        $path = trim($path);

        if ($path == '')
            return self::$defaultController;

        $wildcards =
            [
                '/\(:any\)/',
                '/\(:num\)/',
                '/\((.*?)\)/',
            ];

        $replaces =
            [
                '[^/]+',
                '[0-9]+',
                '(.*)'
            ];

        foreach (['exact', 'regex'] as $mode)
        {
            foreach ([$path, $path.'/index'] as $findPath)
            {
                foreach ($routes as $route)
                {
                    $compiledPath = key($route->compiled);

                    if ($mode == 'exact')
                    {
                        if ($findPath == $compiledPath)
                            return $route;
                    }
                    else
                    {
                        $e_findPath     = explode('/', $findPath);
                        $e_compiledPath = explode('/', $compiledPath);

                        if (count($e_findPath) == count($e_compiledPath))
                        {
                            $valid    = TRUE;
                            $skip_seg = [];

                            for ($i = 0; $i < count($e_findPath); $i++)
                            {
                                $count = 0;
                                $reg   = preg_replace($wildcards, $replaces, $e_compiledPath[$i], -1, $count);
                                $valid = (bool) preg_match('#^'.$reg.'$#', $e_findPath[$i]);

                                if ($valid && $count > 0)
                                    $skip_seg[] = $i;
                            }

                            if ($valid)
                            {
                                for ($i = 0; $i < count($e_findPath); $i++)
                                {
                                    if(in_array($i, $skip_seg))
                                        continue;

                                    if ($valid)
                                        $valid = $e_findPath[$i] == $e_compiledPath[$i];
                                }
                            }

                            if ($valid)
                                return $route;
                        }
                    }
                }
            }
        }

        return FALSE;
    }


    /**
     * Parse improved route arguments by a provided path
     *
     * @param  object  $route
     * @param  string  $path
     *
     * @return bool | object
     *
     * @access public
     * @static
     */
    public static function getRouteArgs($route, $path)
    {
        $compiled = self::compileRoute($route);

        $r_seg = explode('/', $compiled->path);
        $p_seg = explode('/', $path);

        $args   = [];
        $n_args = 1;

        for ($s = 0; $s < count($r_seg); $s++)
        {
            if (!isset($p_seg[$s]))
                continue;

            if ($r_seg[$s] != $p_seg[$s])
            {
                $args['$'.$n_args] = $p_seg[$s];
                $n_args++;
            }
        }

        return $args;
    }


    /**
     * Returns an array with the valid HTTP Verbs used in routes
     *
     * @return Route
     *
     * @access public
     * @static
     */
    public static function getHTTPVerbs()
    {
        return self::$http_verbs;
    }


    /**
     * Set the 404 error controller ($route['404_override'])
     *
     * @param  string  $controller
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function set404($controller, $path = '404')
    {
        self::$_404page = (object)
        [
            'controller' => $controller,
            'path'       => $path
        ];
    }


    /**
     * Get the 404 route
     *
     * @return Route $_404page
     *
     * @return Route | null
     *
     * @access public
     * @static
     */
    public static function get404()
    {
        return self::$_404page;
    }


    /**
     * Set the 'translate_uri_dashes' value ($route['translate_uri_dashes'])
     *
     * @param  $value
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function setTrasnlateUriDashes($value)
    {
        self::$translateDashes = (bool) $value;
    }


    /**
     * Attempts to trigger a nice 404 view (if a custom 404 controller is defined)
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function trigger404()
    {
        if (!is_null(self::$_404page))
        {
            header('Location: '.config_item('base_url').self::$_404page->path);
            die;
        }

        show_404();
    }
}
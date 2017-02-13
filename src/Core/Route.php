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
     * (For route groups only) makes the 'hideOriginal' attribute global for the current group
     *
     * @var static $hideOriginals
     *
     * @access protected
     */
    protected static $hideOriginals = [];

    /**
     * (For route groups only) makes the 'prefix' attribute global for the current group
     *
     * @var static $prefix
     *
     * @access protected
     */
    protected static $prefix = [];

    /**
     * (For route groups only) makes the 'namespace' attribute global for the current group
     *
     * @var static $namespace
     *
     * @access protected
     */
    protected static $namespace = [];

    /**
     * Array with group middleware. It will be used with the Middleware class as a global route filter
     *
     * @var static $groupMiddleware
     *
     * @access protected
     */
    protected static $groupMiddleware = array();

    /**
     * Generic method to add a improved route
     *
     * @param  mixed $verb String or array of string of valid HTTP Verbs that will be accepted in this route
     * @param  mixed $url String or array of strings that will trigger this route
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
        if(!is_array($attr))
        {
            show_error('You must specify the route attributes as an array',500,'Route error: bad attributes');
        }

        if(!isset($attr['uses']))
        {
            show_error('Route requires a \'controller@method\' to be pointed and it\'s not defined in the route attributes', 500, 'Route error: missing controller');
        }

        if(!preg_match('/^([a-zA-Z1-9-_]+)@([a-zA-Z1-9-_]+)$/', $attr['uses'], $parsedController) !== FALSE)
        {
            show_error('Route controller must be in format controller@method', 500, 'Route error: bad controller format');
        }


        $controller = $parsedController[1];
        $method     = $parsedController[2];

        if(!is_string($path))
            show_error('Route path must be a string ', 500, 'Route error: bad route path');

        if(!is_string($verb))
            show_error('Route HTTP Verb must be a string', 500, 'Route error: bad verb type');

        $verb = strtoupper($verb);

        if(!in_array($verb, self::$http_verbs,TRUE))
        {
            $errorMsg = 'Verb "'.$verb.'" is not a valid HTTP Verb, allowed verbs:<ul>';
            foreach( self::$http_verbs as $validVerb )
            {
                $errorMsg .= '<li>'.$validVerb.'</li>';
            }
            $errorMsg .= '</ul>';
            show_error($errorMsg,500,'Route error: unknow method');
        }

        $route['verb'] = $verb;

        $path = trim($path,'/');

        $route['path']       = $path;
        $route['controller'] = $controller;
        $route['method']     = $method;

        if(isset($attr['as']))
        {
            $route['name'] = $attr['as'];
        }
        else
        {
            $route['name'] = NULL;
        }

        // Setting up the prefix

        $route['prefix'] = NULL;
        $group_prefix = end(self::$prefix);

        if($group_prefix)
            $route['prefix'] = $group_prefix.'/';

        if(isset($attr['prefix']))
            $route['prefix'] .= $attr['prefix'];

        // Setting up the namespace

        $route['namespace'] = NULL;
        $group_namespace = end(self::$namespace);

        if(!is_null($group_namespace))
            $route['namespace'] = $group_namespace.'/';
        if(isset($attr['namespace']))
            $route['namespace'] .= $attr['namespace'];

        $route['prefix']    = trim($route['prefix'], '/');
        $route['namespace'] = trim($route['namespace'],'/');

        if(empty($route['prefix']))
            $route['prefix'] = NULL;

        if(empty($route['namespace']))
            $route['namespace'] = NULL;

        $route['middleware'] = array();

        if(isset($attr['middleware']))
        {
            if(is_array($attr['middleware']))
            {
                foreach($attr['middleware'] as $middleware)
                    $route['middleware'][] = $middleware; # Group
            }
            elseif( is_string($attr['middleware']))
            {
                $route['middleware'][] = $attr['middleware']; # Group
            }
            else
            {
                show_error('Route middleware must be a string or an array',500,'Route error: bad middleware format');
            }
        }

        $compiledRoute = self::compileRoute((object) $route);

        $route['compiled'] =
            [
                $compiledRoute->path => $compiledRoute->route
            ];

        $groupHideOriginals = end(self::$hideOriginals);

        if($hideOriginal || $groupHideOriginals || ($compiledRoute->path != '' && $compiledRoute->path != '/' ) )
        {
            $hiddenRoutePath      = $controller.'/'.$method;
            $hiddenRouteNamespace = '';

            if(!is_null($route['namespace']))
            {
                $hiddenRouteNamespace = $route['namespace'].'/';
            }

            $hiddenRoutePath = $hiddenRouteNamespace.$hiddenRoutePath;

            if($method == 'index')
            {
                self::$hiddenRoutes[] = [ $hiddenRouteNamespace.$controller  => function(){ show_404(); }];
            }

            self::$hiddenRoutes[] = [$hiddenRoutePath => function(){ show_404(); }];
        }

        if(!$return)
        {
            self::$routes[] = (object) $route;
        }
        else
        {
            return (object) $route;
        }
    }

    /**
     * Adds a GET route, alias of Route::add('GET',$url,$attr,$hideOriginal)
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
    public static function get($url, $attr, $hideOriginal = TRUE)
    {
        self::add('GET', $url,$attr, $hideOriginal);
    }

    /**
     * Adds a POST route, alias of Route::add('POST',$url,$attr,$hideOriginal)
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
    public static function post($url, $attr, $hideOriginal = TRUE)
    {
        self::add('POST', $url,$attr, $hideOriginal);
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
        self::add('PUT', $url,$attr, $hideOriginal);
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
        self::add('PATCH', $url,$attr, $hideOriginal);
    }

    /**
     * Adds a DELETE route, alias of Route::add('DELETE',$url,$attr,$hideOriginal)
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
    public static function delete($url, $attr, $hideOriginal = TRUE)
    {
        self::add('DELETE', $url,$attr, $hideOriginal);
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
        foreach(self::$http_verbs as $verb)
        {
            $verb = strtolower($verb);
            self::add($verb, $url, $attr, $hideOriginal);
        }
    }

    /**
     * Adds a list of routes with the verbs contained in $verbs, alias of Route::add($verbs,$url,$attr,$hideOriginal)
     *
     * @param  mixed $verb String or array of string of valid HTTP Verbs that will be accepted in this route
     * @param  mixed $url String or array of strings that will trigger this route
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
        if(!is_array($verbs))
            show_error('Route::matches() first argument must be an array of valid HTTP Verbs', 500, 'Route error: bad Route::matches() verb list');

        foreach($verbs as $verb)
        {
            self::add($verb, $url, $attr, $hideOriginal);
        }
    }

    /**
     * Adds a RESTFul route wich contains methods for create, read, update, view an specific resource
     *
     * This is a shorthand of creating
     *      Route::get('{{url}}',['uses' => '{controller}@index', 'as' => '{controller}.index']);
     *      Route::get('{{url}}/create',['uses' => '{controller}@create', 'as' => '{controller}.create']);
     *      Route::post('{{url}}',['uses' => '{controller}@store', 'as' => '{controller}.store']);
     *      Route::get('{{url}}/{slug}',['uses' => '{controller}@show', 'as' => '{controller}.show']);
     *      Route::get('{{url}}/edit',['uses' => '{controller}@edit', 'as' => '{controller}.edit']);
     *      Route::matches(['PUT','PATCH'],'{{url}}/{slug}',['uses' => '{controller}@update', 'as' => '{controller}.update']);
     *      Route::delete('{{url}}/{slug}',['uses' => '{controller}@delete', 'as' => '{controller}.delete']);
     *
     * PLEASE NOTE: This is NOT a crud generator, just a bundle of predefined routes.
     *
     * @param  string $url String or array of strings that will trigger this route
     * @param  string $controller Controller name (only controller name)
     * @param  array $attr Associative array of route attributes
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function resource($name, $controller, $attr = NULL)
    {
        $base_attr = array();

        $hideOriginal = FALSE;

        if(isset($attr['namespace']))
            $base_attr['namespace']  = $attr['namespace'];

        if(isset($attr['middleware']))
            $base_attr['middleware'] = $attr['middleware'];

        if(isset($attr['hideOriginal']))
            $hideOriginal = (bool) $attr['hideOriginal'];

        $base_attr['prefix'] = strtolower($name);

        if(isset($attr['prefix']))
            $base_attr['prefix']  = $attr['prefix'];

        $only = array();

        $controller = strtolower($controller);

        if(isset($attr['only']) && (is_array($attr['only']) || is_string($attr['only'])))
        {
            if(is_array($attr['only']))
            {
                $only  = $attr['only'];
            }
            else
            {
                $only[] = $attr['only'];
            }
        }

        if(empty($only) || in_array('index', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@index',   'as' => $name.'.index']);
            self::get('/', $route_attr, $hideOriginal);
        }

        if(empty($only) || in_array('create', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@create', 'as' => $name.'.create']);
            self::get('create', $route_attr, $hideOriginal);
        }

        if(empty($only) || in_array('store', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@store', 'as' => $name.'.store']);
            self::post('/', $route_attr, $hideOriginal);
        }

        if(empty($only) || in_array('show', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@show', 'as' => $name.'.show']);
            self::get('{slug}', $route_attr, $hideOriginal);
        }

        if(empty($only) || in_array('edit', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@edit', 'as' => $name.'.edit']);
            self::get('{slug}/edit', $route_attr, $hideOriginal);
        }

        if(empty($only) || in_array('update', $only))
        {
            $route_attr = array_merge($base_attr, ['uses' => $controller.'@update', 'as' => $name.'.update']);
            self::matches(['PUT', 'PATCH'], '{slug}/update', $route_attr, $hideOriginal);
        }

        if(empty($only) || in_array('destroy', $only))
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

        if(!is_null($route->prefix))
        {
            $prefix = $route->prefix;
        }

        if(!is_null($route->namespace))
        {
            $namespace = $route->namespace;
        }

        $path = $route->path;

        if(!is_null($prefix))
            $path = $prefix.'/'.$path;

        /*
        if(substr($path, 0, 1) == "/" && strlen($path) > 1)
            $path = substr($path,1);
        */

        $controller = $route->controller.'/'.$route->method;

        if(!is_null($namespace))
            $controller = $namespace.'/'.$controller;

        $path       = trim($path,'/');
        $controller = trim($controller,'/');

        $replaces =
            [
                '{\((.*)\):[a-zA-Z0-9-_]*(\?}|})' => '($1)',   # Custom regular expression
                '{num:[a-zA-Z0-9-_]*(\?}|})'      => '(:num)', # (:num) route
                '{any:[a-zA-Z0-9-_]*(\?}|})'      => '(:any)', # (:any) route
                '{[a-zA-Z0-9-_]*(\?}|})'          => '(:any)', # Everything else
            ];

        $foundedArgs = [];

        foreach($replaces as $regex => $replace)
        {
            $matches = [];
            if(preg_match_all('/'.$regex.'/', $path, $matches ))
            {
                $foundedArgs = $matches[0];
            }

            $path = preg_replace('/'.$regex.'/', $replace, $path);
        }

        $argConstraint = FALSE;

        $args = [];
        $args['required'] = [];
        $args['optional'] = [];

        foreach($foundedArgs as $arg)
        {
            if(substr($arg,-2) == '?}')
            {
                $args['optional'][] = $arg;
                $argConstraint = TRUE;
            }
            else
            {
                if($argConstraint)
                    show_error('Optional route path argument not valid at this position', 500, 'Route error');
                $args['required'][] = $arg;
            }
        }

        if(count($foundedArgs) > 0)
        {
            for($i = 0; $i < count($foundedArgs); $i++)
            {
                $controller .= '/$'.($i + 1);
            }
        }

        return (object) [
            'path'  => $path,
            'route' => $controller,
            'args'  => $args,
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

        foreach(self::$routes as $index => $route)
        {
            $compiled = self::compileRoute($route);

            if( !isset($routes[$compiled->path]) || $route->verb == 'GET' )
            {
                $routes[$compiled->path] = $compiled->route;
            }

            self::$routes[$index] = (object) $route;
        }

        foreach(self::$hiddenRoutes as $route)
        {
            $path = key($route);
            $_404 = $route[$path];

            if(!isset($routes[$path]))
                $routes[$path] = $_404;
        }

        if(is_null(self::$defaultController))
            show_error('You must specify a home route: Route::home() as default controller!', 500, 'Route error: missing default controller');

        $defaultController = self::$defaultController->compiled;
        $defaultController = $defaultController[key($defaultController)];

        $routes['default_controller'] = $defaultController;

        if(is_null(self::$_404page))
        {
            $routes['404_override'] = '';
        }
        else
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
        if(!is_array($attr))
            show_error('Group attribute must be a valid array');

        if(!isset($attr['prefix']))
            show_error('You must specify an prefix!');

        self::$prefix[] = $attr['prefix'];

        if(isset($attr['namespace']))
        {
            self::$namespace[] = $attr['namespace'];
        }

        if(isset($attr['hideOriginals']) && $attr['hideOriginals'] === TRUE)
        {
            self::$hideOriginals[] = TRUE;
        }
        else
        {
            self::$hideOriginals[] = FALSE;
        }

        if(isset($attr['middleware']))
        {
            if(is_array($attr['middleware']) || is_string($attr['middleware']))
            {
                if(is_array($attr['middleware']) && !empty($attr['middleware']))
                {
                    foreach($attr['middleware'] as $middleware)
                        self::$groupMiddleware[] = [ $attr['prefix'] => $middleware ];
                }
                else
                {
                    self::$groupMiddleware[] = [ $attr['prefix'] => $attr['middleware'] ];
                }
            }
            else
            {
                show_error('Group middleware not valid');
            }
        }

        $res = $routes->__invoke();

        array_pop(self::$prefix);
        array_pop(self::$namespace);
        array_pop(self::$hideOriginals);
    }

    /**
     * Creates the 'default_controller' key in CodeIgniter's route array
     *
     * @param  string $route controller/method name
     * @param  string $alias (Optional) alias of the default controller
     *
     * Due a CodeIgniter limitations, this route MAY NOT be a directory.
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

        if(!is_null($attr) && !is_array($attr))
            show_error('Default controller attributes must be an array',500,'Route error: bad attribute type');

        if(!is_null($attr))
            $routeAttr = array_merge($routeAttr,$attr);

        if(isset($attr['prefix']))
            show_error('Default controller may not have a prefix!',500,'Route error: prefix not allowed');

        self::$defaultController = self::$routes[] = self::add('GET', '/', ['uses' => $controller, 'as' => $as],TRUE, TRUE);
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
        if(is_null($verb))
        {
            return self::$routes;
        }
        else
        {
            $routes = [];
            foreach(self::$routes as $route)
            {
                if($route->verb == $verb)
                    $routes[] = $route;
            }
            return $routes;
        }
    }

    /**
     * Get all hidden routes
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getHiddenRoutes()
    {
        return self::$hiddenRoutes;
    }

    /**
     * Get all middleware defined by route groups.
     *
     * This middleware actually works as uri filter since they will not check the route,
     * just check if the current uri string matches the prefix of the route group.
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getGroupMiddleware()
    {
        return self::$groupMiddleware;
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

        foreach(self::$routes as $route)
        {
            if($route->name == $search)
            {
                $founded = $route;
            }
        }

        if(!is_null($founded))
        {
            $routeArgs        = self::compileRoute($founded)->args;
            $routeArgCount    = count($routeArgs['required']) + count($routeArgs['optional']);
            $routeReqArgCount = count($routeArgs['required']);

            if(count($args) < $routeReqArgCount)
            {
                $missingArgs = $routeReqArgCount - count($args);
                throw new \Exception('Missing '.$missingArgs.' required argument'.($missingArgs != 1 ? 's' : '').' for route "'.$founded->name.'"');
            }
            if(count($args) > $routeArgCount)
            {
                throw new \Exception('The route "'.$founded->name.'" expects maximum '.$routeArgCount.' argument'.($routeArgCount != 1 ? 's' : '').', '.count($args).' provided');
            }

            $path = self::compileRoute($founded)->path;

            foreach($args as $replacement)
            {
                $path = preg_replace('/\((.*?)\)/', $replacement, $path, 1);
            }

            $argsLeft =  $routeArgCount - count($args);

            for($i = $argsLeft; $i >= 0; $i--)
            {
                $path = preg_replace('/\((.*?)\)/', '', $path, 1);
            }

            return base_url(trim($path,'/'));
        }

        throw new \Exception('The route "'.$search.'" is not defined');
    }

    /**
     *  Heuristic testing of current uri_string in compiled routes
     *
     *  This is the 'reverse' process of the improved routing, it'll take the current
     *  uri string and attempts to find a CodeIgniter route that matches with his pattern
     *
     * @param  string $search
     *
     * @return mixed
     */
    public static function getRouteByPath($path, $requestMethod = NULL)
    {
        if(is_null($requestMethod))
            $requestMethod = $_SERVER['REQUEST_METHOD'];

        $routes = self::getRoutes($requestMethod);

        if(empty($routes))
            return FALSE;

        $founded = FALSE;
        $matches = array();

        $path = trim($path);

        if($path == '')
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


        foreach( ['exact' , 'regex'] as $mode)
        {
            foreach( [ $path, $path.'/index' ] as $findPath )
            {
                foreach($routes as $route)
                {
                    $compiledPath = key($route->compiled);

                    if($mode == 'exact')
                    {
                        if($findPath == $compiledPath)
                            return $route;
                    }
                    else
                    {
                        $e_findPath     = explode('/', $findPath);
                        $e_compiledPath = explode('/', $compiledPath);

                        if( count($e_findPath) == count($e_compiledPath))
                        {
                            $valid = TRUE;
                            for($i = 0; $i < count($e_findPath); $i++)
                            {
                                $reg = preg_replace($wildcards, $replaces, $e_compiledPath[$i]);

                                $valid = (bool) preg_match('#^'.$reg.'$#', $e_findPath[$i]);
                            }
                            if($valid)
                                return $route;
                        }
                    }
                }
            }
        }

        return FALSE;
    }

    /**
     * Returns an array with the valid HTTP Verbs used in routes
     *
     * @return array
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
     * @param  string  $namespace (Optional)
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
     * @return array $_404page
     *
     * @return object | null
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
}
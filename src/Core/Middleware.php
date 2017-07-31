<?php

/**
 * Middleware class
 *
 * @author    Anderson Salas <me@andersonsalas.com.ve>
 * @copyright 2017
 * @license   GNU-3.0
 *
 */

namespace Luthier\Core;

class Middleware
{
    protected static $overrideRequest = NULL;

    /**
     * CodeIgniter instance (in dynamic context)
     *
     * @var $CI
     *
     * @access protected
     */
    protected $CI;

    /**
     * CodeIgniter instance (in static context)
     *
     * @var static $instance
     *
     * @access protected
     */
    protected static $instance;

    /**
     * Current URI string
     *
     * @var static $uri_string
     *
     * @access protected
     */
    protected static $uri_string;

    /**
     * Class constructor
     *
     * @return void
     *
     * @access public
     */
    public function __construct()
    {
        $this->CI = & self::$instance;
    }

    public static function overrideRequestWith($request)
    {
        self::$overrideRequest = $request;
    }

    /**
     * Class initialization (in static context)
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function init()
    {
        self::$instance = & get_instance();
        self::$uri_string = self::$instance->router->uri->uri_string();

        // Execute user defined middleware:
        self::routeMiddleware();

        // Execute Luthier's internal middleware
        $internalMiddleware = dirname(__DIR__).DIRECTORY_SEPARATOR.'Middleware'.DIRECTORY_SEPARATOR;
        require $internalMiddleware.'Request.php';

        $request = new \Luthier\Middleware\Request();
        $request->run();
    }

    /**
     * Executes the current route middleware (if any)
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function routeMiddleware()
    {
        $currentRoute = Route::getRouteByPath(self::$uri_string);

        $_run = array();

        // Current route middleware
        if ($currentRoute !== FALSE)
        {
            foreach ($currentRoute->middleware as $middleware)
            {
                if (!in_array($middleware, $_run))
                    $_run[] = $middleware;
            }
        }

        // Call the current controller __beforeMiddleware() method, if exists:
        if (method_exists(self::$instance, '_beforeMiddleware'))
            self::$instance->_beforeMiddleware();

        foreach ($_run as $middleware)
            self::start($middleware);

        // Call the current controller _afterMiddleware() method, if exists:
        if (method_exists(self::$instance, '_afterMiddleware'))
            self::$instance->_afterMiddleware();
    }

    /**
     * Run middleware
     *
     * @param  string $middlewareName
     *
     * @return void
     *
     * @access private
     * @static
     */
    public static function start($name, $dir = NULL)
    {
        if($dir === NULL)
        {
            $dir  = APPPATH.'middleware'.DIRECTORY_SEPARATOR;
            $name = ucfirst($name).'_middleware';
        }

        if (!file_exists($dir))
            show_error('Unable to find (or read) the middleware folder: "'.$dir.'"');

        $target = $dir.$name.'.php';

        if (!file_exists($target))
            show_error('Unable to find (or read) the middleware "'.$target.'"');

        require $target;

        if (!class_exists($name))
            show_error('Class "'.$name.'" not found');

        $middleware = new $name();
        $middleware->run();
    }
}

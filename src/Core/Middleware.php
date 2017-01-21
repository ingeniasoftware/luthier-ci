<?php

/**
 * Middleware class
 *
 * @author    Anderson Salas <me@andersonsalas.com.ve>
 * @copyright 2017
 * @license   GNU-3.0
 * @version   1.0.2-alpha
 *
 */

namespace Luthier\Core;

class Middleware
{

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
        $this->CI =& self::$instance;
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
        self::$instance =& get_instance();
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
        $currentRoute    = Route::getRouteByPath(self::$uri_string);

        $groupMiddleware = Route::getGroupMiddleware();
        $_run = array();

        // Current route middleware
        if($currentRoute !== FALSE)
        {
            foreach($currentRoute->middleware as $middleware)
            {
                if(!in_array($middleware,$_run))
                    $_run[] = $middleware;
            }
        }

        // Group middleware:
        foreach($groupMiddleware as $middlewares)
        {
            foreach($middlewares as $path => $middleware)
            {
                $_lenght = strlen($path);

                $search = self::$uri_string;
                $search = substr($search,0,$_lenght);

                if( $search === $path )
                {
                    if(!in_array($middleware,$_run))
                         $_run[] = $middleware;
                }
            }
        }

        foreach($_run as $middleware)
        {
            self::runMiddleware($middleware);
        }
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
    private static function runMiddleware($middlewareName)
    {
        $middlewareDir = APPPATH.'middleware'.DIRECTORY_SEPARATOR;

        $middlewareOriginalName = $middlewareName;

        $middlewareName = ucfirst($middlewareName).'_middleware';

        if(!file_exists($middlewareDir))
            show_error('Unable to find (or read) the middleware folder: "'.$middlewareDir.'"');

        $runMiddleware =  $middlewareDir.$middlewareName.'.php';

        if(!file_exists($runMiddleware))
            show_error('Unable to find (or read) the middleware "'.$runMiddleware.'"');

        require $runMiddleware;

        if(!class_exists($middlewareName))
            show_error('Class "'.$middlewareName.'" not found');

        $middleware = new $middlewareName();

        // Call the current controller __beforeMiddleware() method, if exists:
        if(method_exists(self::$instance, '_beforeMiddleware'))
            self::$instance->_beforeMiddleware();

        // Run the middleware
        $middleware->run();

        // Call the current controller _afterMiddleware() method, if exists:
        if(method_exists(self::$instance, '_afterMiddleware'))
            self::$instance->_afterMiddleware();

    }
}

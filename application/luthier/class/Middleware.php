<?php
/**
 * Middleware class
 *
 * Provides a easy way to implement Middleware in CodeIgniter
 *
 * @package   Luthier Framework Core
 * @author    Anderson Salas <me@andersonsalas.com.ve>
 * @copyright 2016
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version   1.0
 *
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *     28 class Middleware
 *     37   function __construct()
 *     50   function init()
 *     86   function routeMiddleware()
 *    136   function runMiddleware($middlewareName, $middlewareDir = NULL)
 *
 * TOTAL FUNCTIONS: 4
 *
 */

class Middleware
{
    const VERSION = 1.0;

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
        
        $internalMiddlewareDir = APPPATH.'luthier'.DS.'middleware'.DS;

        self::routeMiddleware();

        self::runMiddleware('LuthierRequest', $internalMiddlewareDir);
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
    private static function runMiddleware($middlewareName, $middlewareDir = NULL)
    {
        if(is_null($middlewareDir))
            $middlewareDir = APPPATH.'middleware'.DS;

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

        if(method_exists(self::$instance, '_beforeMiddleware'))
            self::$instance->_beforeMiddleware();

        $middleware->run();

        if(method_exists(self::$instance, '_afterMiddleware'))
            self::$instance->_afterMiddleware();

    }
}

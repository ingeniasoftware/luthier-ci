<?php

/**
 * Luthier Request middleware (internal)
 *
 * @author    Anderson Salas <me@andersonsalas.com.ve>
 * @copyright 2017
 * @license   GNU-3.0
 *
 */

namespace Luthier\Middleware;

use Luthier\Core\Route as Route;

class Request extends \Luthier\Core\Middleware
{

    /**
     * Current (improved) route
     *
     * @var $route
     *
     * @access protected
     */
    protected $route;

    /**
     * Infered request method
     *
     * @var $requestMethod
     *
     * @access protected
     */
    protected $requestMethod;

    /**
     * Class constructor
     *
     * @return void
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
        $this->deterimeRequestMethod();
        $this->route = Route::getRouteByPath(self::$uri_string, $this->requestMethod);
    }

    /**
     * Determines the actual request method
     *
     * @return void
     *
     * @access private
     */
    private function deterimeRequestMethod()
    {

        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $formMethod    = NULL;
        $validMethods  = Route::getHTTPVerbs();

        // FIXME: Solve ambiguity here! POST with _method="GET" makes no sense

        if (isset($_POST['_method']) && in_array(strtoupper($_POST['_method']), $validMethods, TRUE))
            $formMethod = strtoupper($_POST['_method']);

        if (is_null($formMethod))
        {
            $this->requestMethod = $requestMethod;
        }
        else
        {
            if ($requestMethod == 'POST')
                $this->requestMethod = $formMethod;

            if (!$this->CI->input->is_ajax_request() && $this->requestMethod == 'HEAD')
                $this->requestMethod = 'POST';
        }
    }

    /**
     * Entry point of the middleware
     *
     * @return void
     *
     * @access public
     */
    public function run()
    {
        if (!$this->route)
        {
            //if(is_null(Route::get404()))
            //    show_404();

            if (Route::get404()->controller != get_class($this->CI))
            {
                if (ENVIRONMENT != 'production')
                {
                    show_error('The request method '.$this->requestMethod.' is not allowed to view the resource', 403, 'Forbidden method');
                } else
                {
                    //redirect(Route::get404()->path);
                    Route::trigger404();
                }
            }
        } else
        {
            if (method_exists($this->CI, $this->route->method))
            {
                $path_args  = Route::getRouteArgs($this->route, self::$uri_string);
                $route_args = Route::compileRoute($this->route)->args;



                // Redirect to 404 if not enough parameters provided

                if(count($path_args) < count($route_args['required']))
                    redirect(Route::get404()->path);

                if(count($path_args) == 0)
                {
                    $this->CI->{$this->route->method}();
                }
                else
                {
                    call_user_func_array( [$this->CI, $this->route->method], array_values($path_args) );
                }


                // TODO: Add support to hooks in this execution thread

                $this->CI->output->_display();
                exit(0);
            }
            else
            {
                if (ENVIRONMENT != 'production')
                {
                    show_error('The method '.$this->route->controller.'::'.$this->route->method.'() does not exists', 500, 'Method not found');
                }
                else
                {
                    //redirect(Route::get404()->path);
                    Route::trigger404();
                }
            }
        }
    }
}
<?php
/**
 * Luthier Request middleware (internal)
 *
 * This is the soul of Luthier: takes the current uri string, transforms it into a
 * improved route, get's their meta-data and serves his specific response.
 *
 * Provides an actual RESTFul API to CodeIgniter
 *
 * @package   Luthier Framework Core
 * @author    Anderson Salas <me@andersonsalas.com.ve>
 * @copyright 2016
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version   1.0
 */

class LuthierRequest_middleware extends Middleware
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

        if(isset($_POST['_method']) && in_array(strtoupper($_POST['_method']), $validMethods , TRUE))
            $formMethod = strtoupper($_POST['_method']);

        if(is_null($formMethod))
        {
            $this->requestMethod = $requestMethod;
        }
        else
        {
            if($requestMethod == 'POST')
                $this->requestMethod = $formMethod;

            if(!$this->CI->input->is_ajax_request() && $this->requestMethod == 'HEAD')
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
        if(!$this->route)
        {
            if(ENVIRONMENT != 'production')
            {
                show_error('The request method '.$this->requestMethod.' is not allowed to view the resource', 403, 'Forbidden method');
            }
            else
            {
                show_404();
            }
        }

        if(method_exists($this->CI,$this->route->method))
        {
            $this->CI->{$this->route->method}();
            exit(0); // Exit the "parent" controller callback
        }
        else
        {
            if(ENVIRONMENT != 'production')
            {
                show_error('The method '.$this->route->controller.'::'.$this->route->method.'() does not exists', 500, 'Method not found');
            }
            else
            {
                show_404();
            }
        }
    }
}
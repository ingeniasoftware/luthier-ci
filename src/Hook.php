<?php

/**
 * Loader class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Exception\RouteNotFoundException;
use Luthier\RouteBuilder as Route;
use Luthier\RouteAjaxMiddleware;
use Luthier\Debug;
use Luthier\Debug\DatabaseDataCollector;
use Luthier\Debug\RouteDataCollector;
use Luthier\Debug\AuthDataCollector;
use Luthier\Auth\Dispatcher as AuthDispatcher;
use Luthier\Utils;

final class Hook
{

    /**
     * Get all Luthier-CI hooks
     *
     * @param  mixed   $config (Optional)
     * @param  mixed   $hooks (Optional)  (Passed by reference)
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function getHooks($config = null, &$hooks = [])
    {
        if(empty($config))
        {
            $config = [
                'modules' => [],
            ];
        }

        $hooks['pre_system'][] = function() use($config)
        {
            self::preSystemHook($config);
        };

        $hooks['pre_controller'][] = function()
        {
            global $params, $URI, $class, $method;
            self::preControllerHook($params, $URI, $class, $method);
        };

        $hooks['post_controller_constructor'][] = function() use($config)
        {
            global $params;
            self::postControllerConstructorHook($config, $params);
        };

        $hooks['post_controller'][] = function() use($config)
        {
            self::postControllerHook($config);
        };

        $hooks['display_override'][] = function()
        {
            self::displayOverrideHook();
        };

        return $hooks;
    }


    /**
     * "pre_system" hook
     *
     * @param  array  $config Current Luthier-CI configuration
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function preSystemHook($config)
    {
        //
        // Constants and required files
        //

        define('LUTHIER_CI_VERSION', '0.3.0');
        define('LUTHIER_CI_DIR', __DIR__);

        $isAjax =  isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        $isCli  =  is_cli();
        $isWeb  = !is_cli();

        require_once __DIR__ . '/Facades/Route.php' ;
        require_once __DIR__ . '/Facades/Auth.php' ;

        if( !file_exists(APPPATH . '/routes') )
        {
            mkdir( APPPATH . '/routes' );
        }

        if( !file_exists(APPPATH . '/middleware') )
        {
            mkdir( APPPATH . '/middleware' );
        }

        if( !file_exists(APPPATH . '/routes/web.php') )
        {
            copy(__DIR__ . '/Resources/DefaultWebRoutes.php', APPPATH.'/routes/web.php' );
        }

        if($isWeb)
        {
            require_once(APPPATH . '/routes/web.php');
        }

        if( !file_exists(APPPATH . '/routes/api.php') )
        {
            copy(__DIR__ . '/Resources/DefaultApiRoutes.php', APPPATH.'/routes/api.php' );
        }


        if($isAjax || $isWeb)
        {
            Route::group('/', ['middleware' => [ new RouteAjaxMiddleware() ]],
                function()
                {
                    require_once(APPPATH . '/routes/api.php');
                }
            );
        }


        if( !file_exists(APPPATH . '/routes/cli.php') )
        {
            copy(__DIR__ . '/Resources/DefaultCliRoutes.php', APPPATH.'/routes/cli.php' );
        }


        if($isCli)
        {
            require_once(APPPATH . '/routes/cli.php');
            Route::set('default_controller', Route::DEFAULT_CONTROLLER);
        }

        //
        //  [>= v0.3.0] Deleting old Luthier default controller (if exists)
        //
        if( file_exists(APPPATH . '/controllers/Luthier.php'))
        {
            unlink(APPPATH . '/controllers/Luthier.php');
        }

        if( !file_exists(APPPATH . '/controllers/' .  Route::DEFAULT_CONTROLLER . '.php'))
        {
            copy(__DIR__ . '/Resources/DefaultController.php', APPPATH.'/controllers/'.  Route::DEFAULT_CONTROLLER .'.php' );
        }

        require_once( __DIR__ . '/Functions.php');

        //
        // Auth module
        //

        if(in_array('auth', $config['modules']))
        {
            Route::middleware(new AuthDispatcher());
        }

        //
        // Debug module
        //

        if( ENVIRONMENT != 'production' && !$isCli && !$isAjax && in_array('debug', $config['modules']))
        {
            Debug::init();
            Debug::addCollector(new AuthDataCollector());
            Debug::addCollector(new RouteDataCollector());
            Debug::log('Welcome to Luthier-CI ' . LUTHIER_CI_VERSION . '!');
        }

        //
        // Compile all routes
        //

        Route::compileAll();

        //
        // HTTP verb tweak
        //
        // This allows us to use any HTTP Verb if the form contains a hidden field
        // named "_method"
        //

        if(isset($_SERVER['REQUEST_METHOD']))
        {
            if(strtolower($_SERVER['REQUEST_METHOD']) == 'post' && isset($_POST['_method']))
            {
                $_SERVER['RQUEST_METHOD'] = $_POST['_method'];
            }

            $requestMethod = $_SERVER['REQUEST_METHOD'];
        }
        else
        {
            $requestMethod = 'CLI';
        }

        //
        // Getting the current url
        //

        $url = Utils::currentUrl();

        //
        // Guessing the current Luthier-CI route
        //

        try
        {
            $currentRoute = Route::getByUrl($url);
        }
        catch(RouteNotFoundException $e)
        {
            Route::$compiled['routes'][$url] = Route::DEFAULT_CONTROLLER . '/index';
            $currentRoute =  Route::{ !is_cli() ? 'any' : 'cli' }($url, function(){
                if(!is_cli() && is_callable(Route::get404()))
                {
                    $_404 = Route::get404();
                    call_user_func($_404);
                }
                else
                {
                    show_404();
                }
            });
            $currentRoute->is404 = true;
            $currentRoute->isCli = is_cli();
        };

        Debug::log('>>> CURRENT ROUTE:', 'info', 'routing');
        Debug::log($currentRoute, 'info', 'routing');
        Debug::log('>>> RAW ROUTING:', 'info', 'routing');
        Debug::log(Route::$compiled['routes'], 'info', 'routing');

        $currentRoute->requestMethod = $requestMethod;

        Route::setCurrentRoute($currentRoute);
    }


    /**
     * "pre_controller" hook
     *
     * @param  array    $params (Passed by reference) Array of CodeIgniter router parsed parameters
     * @param  string   $URI (Passed by reference) Current URL
     * @param  string   $class (Passed by reference) Current Class
     * @param  string   $method (Passed by reference) Current Method
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function preControllerHook(&$params, &$URI, &$class, &$method)
    {
        $route  = Route::getCurrentRoute();

        // Is a 404 route? exit
        if($route->is404)
        {
            return;
        }

        $path   = (!empty($route->getPrefix()) ? '/' : '') . $route->getPath();
        $pcount = 0;

        //
        // Removing controller's subdirectories limitation over "/" path
        //

        if($path == '/')
        {
            if(!empty($route->getNamespace()) || is_string($route->getAction()))
            {
                $dir = $route->getNamespace();
                list($_class, $_method) = explode('@', $route->getAction());

                $_controller = APPPATH . 'controllers/' . (!empty($dir) ? $dir . '/' : '') . $_class .'.php';

                if(file_exists($_controller))
                {
                    require_once $_controller;
                    list($class, $method) = explode('@', $route->getAction());
                }
                else
                {
                    $route->setAction( function(){
                        if(!is_cli() && is_callable(Route::get404()))
                        {
                            $_404 = Route::get404();
                            call_user_func($_404);
                        }
                        else
                        {
                            show_404();
                        }
                    });
                }
            }
        }

        if(!$route->isCli)
        {
            // Full path:
            $_path = implode('/', [$route->getPrefix(), $path]);

            foreach(explode('/', $_path) as $i => $segment)
            {
                if(preg_match('/^\{(.*)\}$/', $segment))
                {
                    $route->params[$pcount]->value =  $URI->segment($i+1);

                    // Removing "sticky" route parameters
                    if(substr($route->params[$pcount]->getName(), 0, 1) == '_')
                    {
                        unset($params[$pcount]);
                    }

                    $pcount++;
                }
            }
        }
        else
        {
            if(!empty($route->params))
            {
                $_path = array_slice($_SERVER['argv'], 1);

                if($_path)
                {
                    $params = array_slice($_path, $route->paramOffset);
                }

                foreach($route->params as $i => &$_param)
                {
                    $_param->value = isset($params[$i]) ? $params[$i] : NULL;
                }
            }
        }

        Route::setCurrentRoute($route);
    }


    /**
     * "post_contoller_constructor" hook
     *
     * @param  array    $config Current Luthier-CI configuration
     * @param  array    $params (Passed by reference) Array of CodeIgniter router parsed parameters
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function postControllerConstructorHook($config, &$params)
    {
        if(!is_cli())
        {
            //
            // Auth bootstrap
            //

            if(in_array('auth', $config['modules']) || in_array('debug', $config['modules']))
            {
                ci()->load->library('session');
            }

            if(in_array('auth', $config['modules']))
            {
                ci()->load->config('auth');
                Auth::init();
                Auth::session('validated', false);
            }

            //
            // Restoring flash debug messages
            //

            if(ENVIRONMENT != 'production' && in_array('debug', $config['modules']))
            {
                $debugBarFlashMessages = ci()->session->flashdata('_debug_bar_flash');

                if(!empty($debugBarFlashMessages) && is_array($debugBarFlashMessages))
                {
                    foreach($debugBarFlashMessages as $message)
                    {
                        list($message, $type, $collector) = $message;
                        Debug::log($message, $type, $collector);
                    }
                }
            }

            if(in_array('auth', $config['modules']))
            {
                Debug::log('>>> CURRENT AUTH SESSION:','info','auth');
                Debug::log(Auth::session(), 'info', 'auth');
                Debug::log('>>> CURRENT USER:','info','auth');
                Debug::log(Auth::user(), 'info', 'auth');
            }
        }


        //
        // Routing
        //

        ci()->route = Route::getCurrentRoute();

        if(!ci()->route->is404)
        {
            ci()->load->helper('url');
            ci()->middleware = new Middleware();

            if(method_exists(ci(), 'preMiddleware'))
            {
                call_user_func([ci(), 'preMiddleware']);
            }

            foreach(Route::getGlobalMiddleware()['pre_controller'] as $middleware)
            {
                ci()->middleware->run($middleware);
            }

            // Setting "sticky" route parameters values as default for current route
            foreach(ci()->route->params as &$param)
            {
                if(substr($param->getName(),0,1) == '_')
                {
                    Route::setDefaultParam($param->getName(), ci()->route->param($param->getName()));
                }
            }

            foreach(ci()->route->getMiddleware() as $middleware)
            {
                if(is_string($middleware))
                {
                    $middleware = [ $middleware ];
                }

                foreach($middleware as $_middleware)
                {
                    ci()->middleware->run($_middleware);
                }
            }
        }

        if(is_callable(ci()->route->getAction()))
        {
            call_user_func_array(ci()->route->getAction(), $params);
        }
    }


    /**
     * "post_controller_hook" hook
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function postControllerHook($config)
    {
        if(ci()->route->is404)
        {
            return;
        }

        foreach(Route::getGlobalMiddleware()['post_controller'] as $middleware)
        {
            ci()->middleware->run($middleware);
        }

        if(!is_cli() && in_array('auth', $config['modules']))
        {
            Auth::session('validated', false);
        }
    }


    /**
     * "display_override_hook" hook
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function displayOverrideHook()
    {
        $output = ci()->output->get_output();

        if(isset(ci()->db))
        {
            $queries = ci()->db->queries;
            if(!empty($queries))
            {
                Debug::addCollector(new DatabaseDataCollector());
                foreach($queries as $query)
                {
                    Debug::log($query, 'info', 'database');
                }
            }
        }

        Debug::prepareOutput($output);
        ci()->output->_display($output);
    }
}
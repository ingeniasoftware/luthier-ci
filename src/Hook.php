<?php

/**
 * Loader class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Exception\RouteNotFoundException;

final class Hook
{
    /**
     * Defines all required hooks in order to boot Luthier-CI
     *
     * @param  array $hooks Existing hooks

     * @return array
     *
     * @access public
     * @static
     */
    public static function getHooks($hooks = [])
    {
        if(isset($hooks['pre_system']) && !is_array($hooks['pre_system']))
        {
            $_hook = $hooks['pre_system'];
            $hooks['pre_system']   = [];
            $hooks['pre_system'][] = $_hook;
        }

        if(isset($hooks['pre_controller']) && !is_array($hooks['pre_controller']))
        {
            $_hook = $hooks['pre_controller'];
            $hooks['pre_controller']   = [];
            $hooks['pre_controller'][] = $_hook;
        }

        if(isset($hooks['post_controller_constructor']) && !is_array($hooks['post_controller_constructor']))
        {
            $_hook = $hooks['post_controller_constructor'];
            $hooks['post_controller_constructor']   = [];
            $hooks['post_controller_constructor'][] = $_hook;
        }

        if(isset($hooks['post_controller']) && !is_array($hooks['post_controller']))
        {
            $_hook = $hooks['post_controller'];
            $hooks['post_controller']   = [];
            $hooks['post_controller'][] = $_hook;
        }

        //
        // Pre system  hook
        //

        $hooks['pre_system'][] = function()
        {
            // Defining some constants
            define('LUTHIER_CI_VERSION', 1.0);

            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $isCli  = is_cli();
            $isWeb  = !$isCli;

            // Including facades
            require_once __DIR__ . '/Facades/Route.php' ;

            // Route files
            if( !file_exists(APPPATH . '/routes') )
            {
                mkdir( APPPATH . '/routes' );
            }

            // Middleware folder
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

            if($isAjax)
            {
                require_once(APPPATH . '/routes/api.php');
            }

            // CLI routes file
            // (NOT WORKING YET!!!)
            if( !file_exists(APPPATH . '/routes/cli.php') )
            {
                copy(__DIR__ . '/Resources/DefaultCliRoutes.php', APPPATH.'/routes/cli.php' );
            }

            if($isCli)
            {
                require_once(APPPATH . '/routes/cli.php');
                Route::get('/', Route::DEFAULT_CONTROLLER . '@index' );
            }

            // Default (fake) controller
            if( !file_exists(APPPATH . '/controllers/' .  Route::DEFAULT_CONTROLLER . '.php'))
            {
                copy(__DIR__ . '/Resources/DefaultController.php', APPPATH.'/controllers/'.  Route::DEFAULT_CONTROLLER .'.php' );
            }

            // Global functions
            require_once( __DIR__ . '/Functions.php');

            // Compile all routes
            Route::compileAll();

            // This allows us to use any HTTP Verb through a form with a hidden field
            // named "_method"
            if(isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post')
            {
                if(isset($_POST['_method']))
                {
                    $_SERVER['RQUEST_METHOD'] = $_POST['_method'];
                }
            }

            // Parsing uri and setting the current Luthier route (if exists)
            // FIXME: Maybe this isn't sufficient
            $uri = '/';

            if(isset($_SERVER['PATH_INFO']))
            {
                $uri = trim($_SERVER['PATH_INFO'],'/');
            }

            try
            {
                $currentRoute = Route::getByUrl($uri);
            }
            catch(RouteNotFoundException $e)
            {
                Route::addCompiledRoute($uri);
                $currentRoute =  Route::any($uri, function(){
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
            };

            Route::setCurrentRoute($currentRoute);
        };

        //
        //  Pre controller hook
        //

        $hooks['pre_controller'][] = function()
        {
            global $params, $URI, $class, $method;

            $route  = Route::getCurrentRoute();
            $path   = (!empty($route->getPrefix()) ? '/' : '') . $route->getPath();
            $pcount = 0;

            //
            // Removing controller's subdirectories limitation over "/" path
            //

            if($path == '/' && !$route->is404)
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

            foreach(explode('/', implode('/', [$route->getPrefix(), $path])) as $i => $segment)
            {
                if(preg_match('/^\{(.*)\}$/', $segment))
                {
                    $route->params[$pcount]->value =  $URI->segment($i+1);

                    // Removing "sticky" route parameters from CI callback parameters
                    if(substr($route->params[$pcount]->getName(), 0, 1) == '_')
                    {
                        unset($params[$pcount]);
                    }

                    $pcount++;
                }
            }

            Route::setCurrentRoute($route);
        };

        //
        //  Post controller constructor hook
        //

        $hooks['post_controller_constructor'][] = function()
        {
            global $params;

            // Loading required URL helper
            ci()->load->helper('url');

            // Injecting current route
            ci()->route = Route::getCurrentRoute();

            // Injecting middleware class
            ci()->middleware = new Middleware();

            if(method_exists(ci(), 'preMiddleware'))
            {
                call_user_func([ci(), 'preMiddleware']);
            }

            foreach(Route::getGlobalMiddleware()['pre_controller'] as $middleware)
            {
                ci()->middleware->run($middleware);
            }

            // Seting "sticky" route params values as default for current route
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

            if(is_callable(ci()->route->getAction()))
            {
                call_user_func_array(ci()->route->getAction(), $params);
            }
        };

        //
        //  Post controller hook
        //

        $hooks['post_controller'][] = function()
        {
            foreach(Route::getGlobalMiddleware()['post_controller'] as $middleware)
            {
                ci()->middleware->run($middleware);
            }
        };

        return $hooks;
    }
}
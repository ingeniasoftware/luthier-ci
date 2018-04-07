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
            }

            Route::setCurrentRoute($currentRoute);
        };

        //
        //  Controller constructor hook
        //

        $hooks['post_controller_constructor'][] = function()
        {
            // Load required URI helper
            ci()->load->helper('url');

            // Inject current route to singleton
            $route = Route::getCurrentRoute();
            ci()->route = &$route;

            $_path  = (!empty($route->getPrefix()) ? '/' : '') . $route->getPath();
            $params = [];
            $pcount = 0;

            foreach(explode('/', $_path) as $i => $segment)
            {
                if(preg_match('/^\{(.*)\}$/', $segment))
                {
                    $params[] = $route->params[$pcount]->value = ci()->uri->segment($i+1);
                    $pcount++;
                }
            }

            Route::setCurrentRoute($route);

            // Injecting middleware class
            ci()->middleware = new Middleware();

            // Running custom user pre-middleware action
            if(method_exists(ci(), 'preMiddleware'))
            {
                call_user_func([ci(), 'preMiddleware']);
            }

            // Running global middleware
            foreach(Route::getGlobalMiddleware()['pre_controller'] as $middleware)
            {
                ci()->middleware->run($middleware);
            }

            // Make 'Sticky' _locale special route param if the current route defines it
            if($route->hasParam('_locale'))
            {
                Route::setParam('_locale', $route->param('_locale'));
            }

            // Running route middleware (if any)
            foreach($route->getMiddleware() as $middleware)
            {
                ci()->middleware->run($middleware);
            }

            if(is_callable($route->getAction()))
            {
                var_dump($params);
                call_user_func_array($route->getAction(), $params);
            }
        };

        //
        //  Post controller hook
        //

        $hooks['post_controller'][] = function()
        {
            $route = Route::getCurrentRoute();

            foreach(Route::getGlobalMiddleware()['post_controller'] as $middleware)
            {
                ci()->middleware->run($middleware);
            }
        };

        return $hooks;
    }
}
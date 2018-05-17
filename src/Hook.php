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
use Luthier\Debug;
use Luthier\Auth\AuthDispatcher;

final class Hook
{
    private static $contentType;

    /**
     * Defines all required hooks to boot Luthier-CI
     *
     * @param  array $hooks Existing hooks
     *
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
            $hooks['pre_system'] = [ $_hook ];
        }

        if(isset($hooks['pre_controller']) && !is_array($hooks['pre_controller']))
        {
            $_hook = $hooks['pre_controller'];
            $hooks['pre_controller'] = [ $_hook ];
        }

        if(isset($hooks['post_controller_constructor']) && !is_array($hooks['post_controller_constructor']))
        {
            $_hook = $hooks['post_controller_constructor'];
            $hooks['post_controller_constructor'] = [ $_hook ];
        }

        if(isset($hooks['post_controller']) && !is_array($hooks['post_controller']))
        {
            $_hook = $hooks['post_controller'];
            $hooks['post_controller'] = [ $_hook ];
        }

        $hooks['pre_system'][] = function()
        {
            //
            // Luthier-CI Initialization
            //
            // Defining some constants, creating and loading required files, etc.
            //

            define('LUTHIER_CI_VERSION', '0.3.0');
            define('LUTHIER_CI_DIR', __DIR__);

            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $isCli  = is_cli();
            $isWeb  = !$isCli;

            require_once __DIR__ . '/Facades/Route.php' ;

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

            if($isAjax)
            {
                require_once(APPPATH . '/routes/api.php');
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

            if( !file_exists(APPPATH . '/controllers/' .  Route::DEFAULT_CONTROLLER . '.php'))
            {
                copy(__DIR__ . '/Resources/DefaultController.php', APPPATH.'/controllers/'.  Route::DEFAULT_CONTROLLER .'.php' );
            }

            require_once( __DIR__ . '/Functions.php');


            //
            // Special debug bar routes (assets)
            //

            if( ENVIRONMENT != 'production' && !$isCli && !$isAjax )
            {
                Debug::getDebugBarRoutes();
            }

            //
            // Instance of global Authentication dispatcher (listener)
            //

            Route::middleware(new AuthDispatcher());


            //
            // Compile all routes
            //

            Route::compileAll();

            //
            // HTTP verbs in forms fix
            //
            // Since we only can perform GET and POST request via traditional html forms,
            // this allows us to use any HTTP Verb if the form contains a hidden field
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

            $url = self::getCurrentUrl();

            //
            // Setting the current Luthier-CI route
            //
            // With the url correctly parsed, now it's time to find what
            // route matches that url
            //

            try
            {
                $currentRoute = Route::getByUrl($url);
            }
            catch(RouteNotFoundException $e)
            {
                Route::addCompiledRoute($url);
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


            $currentRoute->method = $requestMethod;

            Route::setCurrentRoute($currentRoute);
        };

        $hooks['pre_controller'][] = function()
        {
            global $params, $URI, $class, $method;

            $route  = Route::getCurrentRoute();

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
        };

        $hooks['post_controller_constructor'][] = function()
        {
            global $params;

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
        };


        $hooks['post_controller'][] = function()
        {
            if(ci()->route->is404)
            {
                return;
            }

            foreach(Route::getGlobalMiddleware()['post_controller'] as $middleware)
            {
                ci()->middleware->run($middleware);
            }

        };


        $hooks['display_override'][] = function()
        {
            $output = ci()->output->get_output();

            //
            // Injecting DebugBar
            //
            if(ENVIRONMENT != 'production' && !ci()->input->is_ajax_request() && !is_cli())
            {
                $output = str_ireplace('</head>', '<link rel="stylesheet" href="'. route('debug_bar.css_assets') .'" /></head>', $output);
                $output = str_ireplace('</body>', '<script src="'. route('debug_bar.js_assets') .'"></script>' . Debug::getDebugBar()->getJavascriptRenderer()->render() .'</body>', $output);
            }

            ci()->output->_display($output);
        };

        return $hooks;
    }


    /**
     * Get the current url
     *
     * This is the same code of the CI_URI class, but since we can't load it here
     * (because 'undefined constant' errors) we have not choice that copy the
     * needed code:
     *
     * @return string
     * @static
     */
    final private static function getCurrentUrl()
    {
        if(is_cli())
        {
            $args = array_slice($_SERVER['argv'], 1);
            return $args ? implode('/', $args) : '/';
        }

        $uriProtocol = config_item('uri_protocol');

        $removeRelativeDirectory = function($uri)
        {
            $uris = array();
            $tok = strtok($uri, '/');
            while ($tok !== FALSE)
            {
                if (( ! empty($tok) OR $tok === '0') && $tok !== '..')
                {
                    $uris[] = $tok;
                }
                $tok = strtok('/');
            }

            return implode('/', $uris);
        };

        $parseRequestUri = function() use($removeRelativeDirectory)
        {
            $uri   = parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
            $query = isset($uri['query']) ? $uri['query'] : '';
            $uri   = isset($uri['path']) ? $uri['path'] : '';

            if (isset($_SERVER['SCRIPT_NAME'][0]))
            {
                if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
                {
                    $uri = (string) substr($uri, strlen($_SERVER['SCRIPT_NAME']));
                }
                elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
                {
                    $uri = (string) substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
                }
            }

            if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0)
            {
                $query = explode('?', $query, 2);
                $uri   = $query[0];
                $_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
            }
            else
            {
                $_SERVER['QUERY_STRING'] = $query;
            }

            parse_str($_SERVER['QUERY_STRING'], $_GET);

            if ($uri === '/' OR $uri === '')
            {
                $uri = '/';
            }

            $uri = $removeRelativeDirectory($uri);

            return $uri;
        };

        if($uriProtocol == 'REQUEST_URI')
        {
            $url = $parseRequestUri();
        }
        elseif($uriProtocol == 'QUERY_STRING')
        {
            $uri = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');

            if (trim($uri, '/') === '')
            {
                $uri = '';
            }
            elseif (strncmp($uri, '/', 1) === 0)
            {
                $uri = explode('?', $uri, 2);
                $_SERVER['QUERY_STRING'] = isset($uri[1]) ? $uri[1] : '';
                $uri = $uri[0];
            }

            parse_str($_SERVER['QUERY_STRING'], $_GET);

            $url = $removeRelativeDirectory($uri);
        }
        elseif($uriProtocol == 'PATH_INFO')
        {
            $url = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO']) : $parseRequestUri();
        }
        else
        {
            show_error('Unsupported uri protocol', 500, 'Luthier-CI boot error');
        }

        if(empty($url))
        {
            $url = '/';
        }

        return $url;
    }
}
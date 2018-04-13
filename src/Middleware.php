<?php

/**
 * Luthier-CI Middleware class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

class Middleware
{
    /**
     * Manually run a middleware
     *
     * @param  string|callable $middleware
     * @param  mixed ...$args
     * @return void
     *
     * @access public
     */
    final public function run($middleware, ...$args)
    {
        $CI =& get_instance();

        if(is_callable($middleware))
        {

            if(empty($args))
            {
                $args[] =& $CI;
            }
            else
            {
                $args = array_unshift($args, $CI);
            }

            call_user_func_array($middleware, $args);
        }
        else
        {
            if( file_exists(APPPATH . '/middleware/' . $middleware . '.php' ))
            {
                require_once(APPPATH . '/middleware/' . $middleware . '.php');

                $middlewareInstance = new $middleware();

                if(!method_exists($middlewareInstance,'run'))
                {
                    show_error('Your middleware doesn\'t have a run() method');
                }

                $middlewareInstance->CI = $CI;

                call_user_func([$middlewareInstance, 'run'], $args);
            }
            else
            {
                show_error('Unable to find <strong>' . $middleware .'.php</strong> in your application/middleware folder');
            }
        }
    }

    /**
     * Defines or add a new hook at $hook point
     *
     * @param  string $hook
     * @param  middleware $middleware
     * @param  mixed ...$args
     *
     * @return void
     *
     * @access public
     */
    final public function addHook($hook, $middleware, ...$args)
    {
        $CI =& get_instance();

        if(is_callable($middleware))
        {
            if(empty($args))
            {
                $args[] =& get_instance();
            }

            if(isset($CI->hooks->hooks[$hook]) && !is_array($CI->hooks->hooks[$hook]))
            {
                $_hook = $CI->hooks->hooks[$hook];
                $CI->hooks->hooks[$hook] = [
                    $_hook,
                ];
            }

            $CI->hooks->hooks[$hook][] = call_user_func_array($middleware, $args);
        }
        else
        {
            $CI->hooks->hooks[$hook][] = call_user_function_array([$this,'run'], [ $middleware, $args] );
        }
    }
}

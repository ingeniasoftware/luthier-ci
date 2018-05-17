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
     *
     * @return void
     *
     * @access public
     */
    final public function run($middleware, ...$args)
    {
        if(is_callable($middleware))
        {
            call_user_func_array($middleware, $args);
        }
        else if(is_object($middleware) && method_exists($middleware,'run'))
        {
            $middleware->run($args);
        }
        else if(is_array($middleware))
        {
            foreach($middleware as $run)
            {
                $this->run($run, $args);
            }
            return;
        }
        else
        {
            if( file_exists(APPPATH . '/middleware/' . $middleware . '.php' ))
            {
                require_once(APPPATH . '/middleware/' . $middleware . '.php');

                $instance = new $middleware();

                if(!method_exists($instance,'run'))
                {
                    show_error('Your middleware doesn\'t have a run() method');
                }

                // Injecting CodeIgniter instance in the middleware
                $instance->CI = ci();

                call_user_func([$instance, 'run'], $args);
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
        if(is_callable($middleware))
        {
            if(empty($args))
            {
                $args[] =& get_instance();
            }

            if(isset(ci()->hooks->hooks[$hook]) && !is_array(ci()->hooks->hooks[$hook]))
            {
                $_hook = ci()->hooks->hooks[$hook];
                ci()->hooks->hooks[$hook] = [ $_hook ];
            }

            ci()->hooks->hooks[$hook][] = call_user_func_array($middleware, $args);
        }
        else
        {
            ci()->hooks->hooks[$hook][] = call_user_function_array([$this,'run'], [ $middleware, $args] );
        }
    }
}

<?php

/**
 * Luthier loader
 *
 * @author    Anderson Salas <me@andersonsalas.com.ve>
 * @copyright 2017
 * @license   GNU-3.0
 * @version   1.0.2-alpha
 *
 */

namespace Luthier\Core;

class Loader
{

    /**
     * Returns the Luthier required hooks
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function init()
    {
        return [
            'pre_system' =>
                [
                    function()
                    {
                        function route($name, $args = NULL)
                        {
                            return Route::getRouteByName($name, $args);
                        }
                    }
                ],
            'post_controller_constructor' =>
                [
                    function()
                    {
                        Middleware::init();
                    }
                ],

        ];
    }
}
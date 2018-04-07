<?php

/**
 * Get route by name
 *
 * @return \Luthier\Core\Route
 */
function route($name = null, $params = [])
{
    if($name === null)
    {
        return Route::getCurrentRoute();
    }

    $route = Route::getByName($name);

    return $route->parseUrl($params);
}


/**
 * Alias of get_instance() function
 */
 function ci()
 {
     $CI = &get_instance();
     return $CI;
 }

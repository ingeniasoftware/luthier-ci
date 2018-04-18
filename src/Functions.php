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
        $route = Route::getCurrentRoute();
    }
    else
    {
        $route = Route::getByName($name);
    }

    return $route->buildUrl($params);
}


/**
 * Alias of get_instance() function
 */
 function ci()
 {
     $CI = &get_instance();
     return $CI;
 }

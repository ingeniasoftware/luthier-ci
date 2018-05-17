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
 * Check if route exists
 *
 * @param  string $name Route name
 *
 * @return mixed
 */
function route_exists($name)
{
    return isset(Route::$compiled['names'][$name]);
}


/**
 * Alias of get_instance() function
 */
function ci()
{
    $CI = &get_instance();
    return $CI;
}


 /**
  * Returns a screen with information about Luthier
  *
  * @return mixed
  */
function luthier_info()
{
    ob_start();
    require LUTHIER_CI_DIR . '/Resources/Global/views/welcome.php';
    $luthierInfo = ob_get_clean();
    ci()->output->set_output($luthierInfo);
}
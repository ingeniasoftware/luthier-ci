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

    require LUTHIER_CI_DIR . '/Resources/About.php';
    $luthierInfo = ob_get_clean();

    ci()->output->set_output($luthierInfo);
}



/**
 * Trigger the custom Luthier-CI 404 page, with fallback to
 * show_404() function.
 *
 * @return mixed
 */
function trigger_404()
{
    $_404 = Route::get404();

    if(is_null($_404) || !is_callable($_404))
    {
        show_404();
    }

    call_user_func($_404);
    exit;
}


/**
 * Redirect to named route
 *
 * @param  string   $routeName
 * @param  array    $params (Optional)
 * @param  array    $messages (Optional) Session flash messages to be stored
 *
 * @return mixed
 */
function route_redirect($routeName, $params = [], $messages = [])
{
    if(!empty($messages) && is_array($messages))
    {
        ci()->load->library('session');

        foreach($messages as $_name => $_value)
        {
            ci()->session->set_flashdata($_name, $_value);
        }
    }

    redirect(route($routeName, $params));
}
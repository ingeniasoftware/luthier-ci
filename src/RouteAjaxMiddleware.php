<?php

/**
 * RouteAjaxMiddleware class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Route;
use Luthier\MiddlewareInterface;

class RouteAjaxMiddleware implements MiddlewareInterface
{
    public function run($args = [])
    {
        if(!ci()->input->is_ajax_request())
        {
            trigger_404();
        }
    }
}
<?php

/*
 * Luthier CI
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of Luthier CI, a plugin for CodeIgniter 3. See the LICENSE
 * file for copyright information and license details
 */

namespace Luthier;

/**
 * This middleware is used in routes that must be restricted to AJAX requests
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RouteAjaxMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\MiddlewareInterface::run() 
     */
    public function run($args = [])
    {
        if(!ci()->input->is_ajax_request())
        {
            trigger_404();
        }
    }
}
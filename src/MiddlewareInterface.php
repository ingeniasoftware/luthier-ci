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
 * All Luthier CI middleware used in the application SHOULD implement this interface, 
 * in order to be properly detected by the router
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
interface MiddlewareInterface
{
    /**
     * Middleware entry point
     * 
     * @param mixed $args Middleware arguments
     * 
     * @return mixed
     */
    public function run($args);
}
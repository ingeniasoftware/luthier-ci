<?php

/**
 * Middleware Interface
 *
 * ALL middleware used within the application MUST implement this interface, or will not
 * be detected by the Luthier-CI Middleware class.
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

interface MiddlewareInterface
{
    /**
     * Middleware entry point
     *
     * @param  mixed $args
     *
     * @return mixed
     *
     * @access public
     */
    public function run($args);
}
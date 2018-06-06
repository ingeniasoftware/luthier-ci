<?php

/**
 * User Interface
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */
 
namespace Luthier\Auth;

interface UserInterface
{
    public function __construct($instance, $roles, $permissions);

    public function getInstance();

    public function getUsername();

    public function getRoles();

    public function getPermissions();
}


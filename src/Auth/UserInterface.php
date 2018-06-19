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
    public function __construct($entity, $roles, $permissions);

    public function getEntity();

    public function getUsername();

    public function getRoles();

    public function getPermissions();
}


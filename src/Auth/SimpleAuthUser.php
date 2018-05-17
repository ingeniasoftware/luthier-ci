<?php

namespace Luthier\Auth;

use Luthier\Auth\AuthUserInterface;

abstract class SimpleAuthUser implements AuthUserInterface
{
    private $username;

    private $password;

    private $roles;

    public function __construct($username, $password, $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->roles    = $roles;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return $this->roles;
    }
}
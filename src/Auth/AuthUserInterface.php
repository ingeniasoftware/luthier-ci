<?php

namespace Luthier\Auth;

interface AuthUserInterface
{
    public function __construct($username, $password, $roles);

    public function getRoles();

    public function getUsername();

    public function getPassword();
}


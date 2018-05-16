<?php

namespace Luthier\Auth;

use Luthier\Auth\Interfaces\UserInterface;

abstract class User implements UserInterface
{
    private $table = 'users';

    private $fields = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'active',
        'last_login',
        'last_ip',
        'created_at',
        'updated_at',
    ];

    private $roles;
}
<?php

/**
 * SimpleAuth User class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth;

use Luthier\Auth\UserInterface;

class User implements UserInterface
{
    private $user;

    private $roles;

    private $permissions;

    public function __construct($entity, $roles, $permissions)
    {
        $this->user        = $entity;
        $this->roles       = $roles;
        $this->permissions = $permissions;
    }


    /**
     * This allow us to fetch user object properties directly because, well, because it's
     * a cool way.
     *
     * @param  mixed  $name
     *
     * @return mixed
     *
     * @access public
     */
    public function __get($name)
    {
        if(isset($this->getEntity()->{$name}))
        {
            return $this->getEntity()->{$name};
        }
    }

    public function getEntity()
    {
        return $this->user;
    }

    public function getUsername()
    {
        return $this->user->email;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }
}
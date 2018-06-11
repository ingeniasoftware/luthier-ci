<?php

/**
 * SimpleAuth User Provider class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth;

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\SimpleAuth\Library;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;

class UserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'User';
    }

    final public function loadUserByUsername($username, $password = null)
    {
        ci()->load->database();

        $user = ci()->db->get_where(
              config_item('simpleauth_users_table'),
            [ config_item('simpleauth_username_col') => $username ]
        )->result();

        if(empty($user) || ($password !== null && !$this->verifyPassword($password, $user[0]->{config_item('simpleauth_password_col')})))
        {
            throw new UserNotFoundException();
        }

        $userClass = $this->getUserClass();

        $roles = [ $user[0]->{config_item('simpleauth_role_col')} ];

        $permissions = [];

        if(config_item('simpleauth_enable_acl') === true)
        {
            $databaseUserPermissions = ci()->db->get_where(
                  config_item('simpleauth_users_acl_table'),
                [ 'user_id' => $user[0]->id ]
            )->result();

            if(!empty($databaseUserPermissions))
            {
                foreach($databaseUserPermissions as $permission)
                {
                    $permissionName = '';
                    Library::walkUpPermission($permission->category_id, $permissionName);
                    $permissions[$permission->category_id] = $permissionName;
                }
            }
        }

        return new $userClass($user[0], $roles, $permissions);
    }

    final public function checkUserIsActive(UserInterface $user)
    {
        if($user->getInstance()->{config_item('simpleauth_active_col')} == 0)
        {
            throw new InactiveUserException();
        }
    }


    final public function checkUserIsVerified(UserInterface $user)
    {
        if($user->getInstance()->{config_item('simpleauth_verified_col')} == 0)
        {
            throw new UnverifiedUserException();
        }
    }


    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }


    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }


}
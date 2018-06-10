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
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;

class UserProvider implements UserProviderInterface
{
    private static $fetchedPermissions = [];

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
                    $this->walkUpPermission($permission->category_id, $permissionName);
                    $permissions[$permission->category_id] = implode('.', array_reverse( explode( '.', $permissionName ) ) );
                }
            }
        }

        return new $userClass($user[0], $roles, $permissions);
    }

    final private function walkUpPermission($id, &$permissionName)
    {
        if(!isset(self::$fetchedPermissions[$id]))
        {
            $permission = ci()->db->get_where(
                config_item('simpleauth_users_acl_categories_table'),
                [
                    'id' => $id,
                ]
            )->result();

            if(empty($permission))
            {
                return;
            }

            $permission =  $permission[0];
            self::$fetchedPermissions[$permission->id] = $permission;
        }
        else
        {
            $permission = self::$fetchedPermissions[$id];
        }

        $permissionName .= (!empty($permissionName) ? '.' : '') . $permission->name;

        if($permission->parent_id !== null)
        {
            $this->walkUpPermission( $permission->parent_id , $permissionName);
        }
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
<?php

/*
 * Luthier CI
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of Luthier CI, a plugin for CodeIgniter 3. See the LICENSE
 * file for copyright information and license details
 */

namespace Luthier\Auth\SimpleAuth;

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;

/**
 * SimpleAuth user provider
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class UserProvider implements UserProviderInterface
{
    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Auth\UserProviderInterface::getUserClass()
     */
    public function getUserClass()
    {
        return 'User';
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Auth\UserProviderInterface::loadUserByUsername()
     */
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

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Auth\UserProviderInterface::checkUserIsActive()
     */
    final public function checkUserIsActive(UserInterface $user)
    {
        if($user->getEntity()->{config_item('simpleauth_active_col')} == 0)
        {
            throw new InactiveUserException();
        }
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Auth\UserProviderInterface::checkUserIsVerified()
     */
    final public function checkUserIsVerified(UserInterface $user)
    {
        $enableCheck = config_item('simpleauth_enable_email_verification')  === TRUE &&
                       config_item('simpleauth_enforce_email_verification') === TRUE;

        if(!$enableCheck)
        {
            return;
        }

        if($user->getEntity()->{config_item('simpleauth_verified_col')} == 0)
        {
            throw new UnverifiedUserException();
        }
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Auth\UserProviderInterface::hashPassword()
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Auth\UserProviderInterface::verifyPassword()
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
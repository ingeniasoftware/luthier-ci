<?php

/**
 * SimpleAuth Controller class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth;

use Luthier\Auth;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\PermissionNotFoundException;

class Library
{
    private static $fetchedPermissions = [];

    private static $foundedPermissions = [];

    final public static function walkUpPermission($id, &$permissionName = '')
    {
        //
        // FASTEST: Defined permission ID in ACL Map
        //
        $aclMap = config_item('simpleauth_acl_map');

        if(is_array($aclMap) && !empty($aclMap))
        {
            $foundedPermission = array_search($id,$aclMap);
        }
        else
        {
            $foundedPermission = FALSE;
        }

        if(is_array($aclMap) && !empty($aclMap) && $foundedPermission !== FALSE)
        {
            $permissionName = $foundedPermission;
            return;
        }
        //
        // FAST: Cached permission
        //
        else if(isset(self::$fetchedPermissions[$id]))
        {
            $permission = self::$fetchedPermissions[$id];
        }
        //
        // SLOW: Application guessing of permission name iterating over the ACL categories
        //       table
        //
        else
        {
            $permission = ci()->db->get_where(
                config_item('simpleauth_users_acl_categories_table'),
                [
                    'id' => $id,
                ]
            )->result();

            if(empty($permission))
            {
                throw new PermissionNotFoundException($permissionName);
            }

            $permission =  $permission[0];
            self::$fetchedPermissions[$permission->id] = $permission;
        }

        if(!empty($permissionName))
        {
            $permissionName = explode('.', $permissionName);
            array_unshift($permissionName, $permission->name);
            $permissionName = implode('.', $permissionName);
        }
        else
        {
            $permissionName = $permission->name;
        }

        if($permission->parent_id !== null)
        {
            self::walkUpPermission( $permission->parent_id , $permissionName);
        }
    }


    final public static function walkDownPermission($permission , $parentID = null)
    {
        $aclMap = config_item('simpleauth_acl_map');

        //
        // FASTEST: Defined permission ID in ACL Map
        //
        if(is_array($aclMap) && !empty($aclMap) && isset($aclMap[$permission]))
        {
            return $aclMap[$permission];
        }

        //
        // SLOW: Application guessing of permission name iterating over the ACL categories
        //       table
        //

        $_permission    = explode('.', $permission);
        $permissionName = array_shift($_permission);

        $getCategory = function($permissionName, $parentID)
        {
            $category = ci()->db->get_where(
                config_item('simpleauth_users_acl_categories_table'),
                [
                    'name'      => $permissionName,
                    'parent_id' => $parentID,
                ]
            )->result();

            if(empty($category))
            {
                throw new PermissionNotFoundException($permissionName);
            }

            $category = $category[0];

            self::$fetchedPermissions[$category->id] = $category;

            return $category;
        };

        if($parentID === null)
        {
            $cachedPermission = array_search($permission, self::$foundedPermissions);

            if($cachedPermission !== FALSE)
            {
                return $cachedPermission;
            }
            else
            {
                $category = $getCategory($permissionName, $parentID);
            }
        }
        else
        {
            $category = $getCategory($permissionName, $parentID);
        }

        if(count($_permission) > 0)
        {
            return self::walkDownPermission( implode('.' , $_permission), $category->id);
        }

        return $category->id;
    }


    final public function __call($name, $args)
    {
        if(method_exists(Auth::class, $name))
        {
            return call_user_func_array([Auth::class, $name], $args);
        }

        show_error('Unknown "' . $name . '" method', 500, 'SimpleAuth error');
    }


    public function isFullyAuthenticated()
    {
        if(Auth::isGuest())
        {
            return false;
        }

        return Auth::session('fully_authenticated') === TRUE;
    }


    public function promptPassword($route = 'confirm_password')
    {
        if( Auth::isGuest() || !route_exists($route) )
        {
            return;
        }

        if( !$this->isFullyAuthenticated() )
        {
            $currentUrl = route();

            redirect( route('confirm_password') . '?redirect_to=' . $currentUrl );
            exit;
        }
    }
}

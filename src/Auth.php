<?php

/**
 * Auth class
 *
 * The Luthier-CI Standard Authentication class.
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Debug;

class Auth
{
    private static $providers = [];

    /**
     * Loads an User class and his attached User Provider class used for authentication process.
     *
     * The User Provider class, as its name suggests, is responsible for retrieving the user
     * from somewhere (a database, an API or even a static file) and will return
     * an object of the User class, in case of a successful authentication, of course.
     *
     * The User class is a abstraction layer which returns some essential data of the
     * authenticated user, such his username, roles and permissions.
     *
     * There are a set of requirements:
     *
     * 1) The User Provider class MUST have the "Provider" suffix and MUST implement the
     *    Luthier\Auth\UserProviderInterface inteface.
     * 2) The User Provider class MUST be located in the application/security/providers folder
     * 3) The attached User Instance MUST be located in the application/security/providers folder
     *    and MUST implement the Luthier\Auth\UserInterface interface
     *
     * @param  string  $userClass  User class name (without the -Provider prefix)
     *
     * @return UserProviderInterface
     *
     * @access public
     * @static
     */
    public static function loadUserProvider($userProviderClass)
    {
        if(isset(self::$providers[$userProviderClass]))
        {
            return self::$providers[$userProviderClass];
        }

        if( substr($userProviderClass,-8) != 'Provider')
        {
            $userProviderClass .= 'Provider';
        }

        if(file_exists( APPPATH . '/security/providers/' . $userProviderClass . '.php') )
        {
            require_once APPPATH . '/security/providers/' . $userProviderClass . '.php';

            if(!class_exists($userProviderClass))
            {
                show_error('User provider class "' .$userProviderClass . '" not found');
            }
        }
        else
        {
            show_error('Unable to find "' . $userProviderClass . '" User Provider class file');
        }

        $userProviderInstance = new $userProviderClass();

        $userClass = $userProviderInstance->getUserClass();

        if(!file_exists( APPPATH . '/security/providers/' . $userClass . '.php') )
        {
            show_error('Unable to find "' . $userClass . '" attached User class file');
        }

        require_once APPPATH . '/security/providers/' . $userClass . '.php';

        if(!class_exists($userClass))
        {
            show_error('User attached class "' . $userClass . '" not found');
        }

        self::$providers[$userClass] = $userProviderInstance;
        return $userProviderInstance;
    }


    /**
     * Checks if the current user is guest (not authenticated)
     *
     * @return bool
     *
     * @access public
     * @static
     */
    public static function isGuest()
    {
        return self::user() === null;
    }


    /**
     * Checks if current user has a specific role
     *
     * @param  string $role Role name or an array of role names
     *
     * @return bool
     *
     * @access public
     * @static
     */
    public static function isRole($user, $role = null)
    {
        if(self::isGuest())
        {
            return false;
        }

        if($role === null)
        {
            $role = $user;
            $user = self::user();
        }

        $roles = $user->getRoles();

        if(!is_array($roles))
        {
            show_error('The getRoles()  method  of ' . get_class($user) . ' class method must return an array', 500, 'Auth error');
        }

        return in_array($role, $user->getRoles());
    }


    /**
     * Checks if current user has a specific permission
     *
     * @param  string  $permission
     *
     * @return bool
     *
     * @access public
     * @static
     */
    public static function isGranted($user, $permission = null)
    {
        if(self::isGuest())
        {
            return false;
        }

        if($permission === null)
        {
            $permission = $user;
            $user = self::user();
        }

        $permissions = $user->getPermissions();

        if(!is_array($permissions))
        {
            show_error('The getPermissions()  method  of ' . get_class($user) . ' class must return an array', 500, 'Auth error');
        }

        if(substr($permission,-2) != '.*')
        {
            return in_array($permission, $permissions);
        }
        else
        {
            foreach($permissions as $_permission)
            {
                if(preg_match('/^' . substr($permission, 0, -2) . '/', $_permission))
                {
                    return true;
                }
            }
            return false;
        }
    }


    /**
     * Stores an user in the auth session
     *
     * @param  UserInterface $user
     * @param  array $data (Optional)
     *
     * @return void
     *
     * @access public
     * @static
     */
    final public static function store(UserInterface $user, $data = [])
    {
        $storedSessionUser = [
            'user'       =>
                [
                    'class'       => get_class($user),
                    'entity'      => $user->getEntity(),
                    'username'    => $user->getUsername(),
                    'roles'       => $user->getRoles(),
                    'permissions' => $user->getPermissions(),
                ],
            'validated'   => false,
            'fully_authenticated' => true,
        ];


        foreach($data as $name => $value)
        {
            if($name == 'user')
            {
                continue;
            }

            $storedSessionUser[$name] = $value;
        }

        ci()->session->set_userdata(config_item('auth_session_var'),$storedSessionUser);
    }


    /**
     * Initializes the Auth library
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function init()
    {
        if(config_item('auth_session_var') === null)
        {
            return;
        }

        if(ci()->session->userdata(config_item('auth_session_var')) === null)
        {
            ci()->session->set_userdata(config_item('auth_session_var'), [
                'user'        => null,
                'validated'   => false,
                'fully_authenticated' => false
            ]);
        }
    }


    /**
     * Get or sets an auth session variable (or the whole auth session if none is provided)
     *
     * @param  string  $name (Optional)
     * @param  mixed  $value (Optional)
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function session($name = null, $value = null)
    {
        $authSession  = ci()->session->userdata(config_item('auth_session_var'));

        if($name === null)
        {
            return $authSession;
        }
        else
        {
            if($value === null)
            {
                return isset($authSession[$name]) ? $authSession[$name] : null;
            }
            else
            {
                $authSession[$name] = $value;
                ci()->session->set_userdata(config_item('auth_session_var'), $authSession);
            }
        }
    }



    /**
     * Get the current authenticated user
     *
     * @param  bool $refresh (Optional) Forces user refresh
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function user($refresh = false)
    {
        $sessionUser = self::session('user');

        if($sessionUser === NULL)
        {
            return null;
        }

        $userInstance = null;
        $userProvider = self::loadUserProvider($sessionUser['class']);
        $userClass    = $sessionUser['class'];

        if(self::session('validated') === false || $refresh === true)
        {
            Debug::log('There is a stored user in session. Attempting to validate...', 'info','auth');

            try
            {
                $userInstance = self::bypass($sessionUser['username'], $userProvider);
            }
            catch(\Exception $e)
            {
                $userInstance = null;
                Debug::log('ERROR! User auth validation failed. Role set to "guest"', 'error','auth');
            }

            Debug::log('SUCCESS! User validated.', 'info','auth');
            self::session('validated', true);
        }
        else
        {
            $userInstance = new $userClass((object) $sessionUser['entity'], $sessionUser['roles'], $sessionUser['permissions']);
        }

        return $userInstance;
    }


    /**
     * Delete the current authenticated user
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function destroy()
    {
        ci()->session->unset_userdata(config_item('auth_session_var'));
        self::init();
    }


     /**
      * Attempt login with the specified username, password and User Provider
      *
      * You must catch any exception produced here manually.
      *
      * @param  string  $username
      * @param  string  $password
      * @param  UserProviderInterface $userProvider
      *
      * @return Luthier\Auth\UserInterface
      *
      * @access public
      * @static
      */
    final public static function attempt($username, $password, UserProviderInterface $userProvider)
    {
        $user = $userProvider->loadUserByUsername($username, $password);

        if(!$user instanceof UserInterface)
        {
            show_error('Returned user MUST be an instance of UserInterface');
        }

        return $user;
    }


    /**
     * Bypass (force) login with the specified User Provider
     *
     * You must catch any exception produced here manually. Even if you can bypass the
     * authentication process with this method, is still required that the target user
     * exists.
     *
     * @param  string  $username
     * @param  UserProviderInterface $userProvider
     *
     * @return Luthier\Auth\UserInterface
     *
     * @access public
     * @static
     */
    final public static function bypass($username, UserProviderInterface $userProvider)
    {
        $user = $userProvider->loadUserByUsername($username, null);

        if(!$user instanceof UserInterface)
        {
            show_error('Returned user MUST be an instance of UserInterface');
        }

        return $user;
    }


    public static function messages()
    {
        $messages = ci()->session->flashdata('_auth_messages');
        return !empty($messages) ? $messages : [];
    }
}
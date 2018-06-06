<?php

/**
 * SimpleAuth Controller class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth\SimpleAuth;

use Luthier\Auth;
use Luthier\Auth\ControllerInterface as AuthControllerInterface;
use Luthier\Auth\SimpleAuth\Middleware as SimpleAuthMiddleware;
use Luthier\Utils;
use Luthier\Debug;

class Controller extends \CI_Controller implements AuthControllerInterface
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Shows a SimpleAuth message
     *
     * @param  string        $title
     * @param  string       $message
     *
     * @return mixed
     *
     * @access private
     */
    private function showMessage($title, $message)
    {
        return $this->loadView('message', compact('title', 'message'));
    }


    /**
     * Loads a SimpleAuth view
     *
     * @param  string       $view View name
     * @param  array        $data (Optional) View data
     *
     * @return mixed
     *
     * @access private
     */
    private function loadView($view, $data = [])
    {
        if(file_exists(VIEWPATH . '/simpleauth/' . $view . '.php'))
        {
            return $this->load->view('simpleauth/' . $view, $data);
        }

        $this->copyAssets(config_item('simpleauth_skin'));
        $assetsPath = base_url(config_item('simpleauth_assets_dir'));

        ob_start();

        foreach($data as $_name => $_value)
        {
            $$_name = $_value;
        }

        require LUTHIER_CI_DIR . '/Resources/SimpleAuth/Assets/skins/' . config_item('simpleauth_skin') . '/views/' . $view . '.php';

        $view = ob_get_clean();

        return $this->output->set_output($view);
    }


    /**
     * Copy required SimpleAuth assets
     *
     * @param  mixed $skin Current skin
     *
     * @return mixed
     *
     * @access private
     */
    final private function copyAssets($skin)
    {
        $target = dirname(APPPATH) . '/' . config_item('simpleauth_assets_dir');

        if(!file_exists($target))
        {
            mkdir($target, 0777, true);
        }

        foreach(['css','js','img'] as $folder)
        {
            $source =  LUTHIER_CI_DIR . '/Resources/Assets/SimpleAuth/skins/' . $skin . '/assets/' . $folder;

            if(file_exists($source))
            {
                Utils::rcopy($source, $target);
            }
        }
    }


    /**
     * Returns an array with fillable user fields
     *
     * This is necessary because sometimes you need to include some extra fields in
     * singup form that doesn't correspond to any column of user's table in database.
     *
     * (In other words, this is the equivalent of the $fillable property of Eloquent ORM
     *  models, but only for auth proposes)
     *
     * @return array
     *
     * @access public
     */
    public function getUserFields()
    {
        return [
            'first_name',
            'last_name',
            'username',
            'gender',
            'email',
            'password',
            'role' => 'user'
        ];
    }


    /**
     * Returns an array of the sign up fields.
     *
     * This allows to make a flexible user registration form without overriding the
     * signup() method
     *
     * The syntax is the following:
     *
     * [
     *      [field name] => [
     *          [field type],
     *          [field label],
     *          [HTML5 validation] (In key => value array format),
     *          [CI Validation] (array of validation rules),
     *          [CI Validation Messages] (array, optional)
     *      ]
     * ]
     *
     * @return mixed
     *
     * @access public
     */
    public function getSignupFields()
    {
        return [
            'first_name' => [
                'text',
                'First Name',
                ['required' => true],
                ['required', 'max_length[45]'],
                null
            ],
            'last_name' => [
                'text',
                'Last Name',
                ['required' => 'required'],
                ['required', 'max_length[45]'],
            ],
            'username' => [
                'text',
                'Username',
                ['required' => true, 'max_length' => 22],
                ['required', 'min_length[3]', 'max_length[22]', 'is_unique[' . config_item('simpleauth_users_table') . '.username' . ']'],
            ],
            'email' => [
                'email',
                'Email',
                ['required' => true],
                ['required', 'valid_email', 'max_length[255]', 'is_unique[' . config_item('simpleauth_users_table') . '.' . config_item('simpleauth_username_col') .']'],
            ],
            'gender' => [
                'radio' => [ 'm' => 'Male', 'f' => 'Female'],
                'Gender',
                ['required' => true],
                ['required', 'max_length[45]'],
            ],
            'password' => [
                'password',
                'Password',
                ['required' => true],
                ['required', 'min_length[8]', 'matches[password_repeat]'],
                ['matches' => 'The passwords does not match.'],
            ],
            'password_repeat' => [
                'password',
                'Repeat Password',
                ['required' => true],
                ['required']
            ],
            'tos_agreement' => [
                'checkbox' => [ 'accept' => 'I accept the Terms and Conditions of Service'],
                'Conditions agreement',
                ['required' => true],
                ['required'],
                ['required' => 'You must accept the Terms and Conditions of Service']
            ],
        ];
    }



    /**
     * Returns the User Provider's name used by authentication process for this controller
     *
     * @return string
     *
     * @access public
     */
    public function getUserProvider()
    {
        return config_item('simpleauth_user_provider');
    }



    /**
     * Returns the (Authentication) Middleware used by authentication process for this
     * controller.
     *
     * @return Luthie\Auth\Middleware
     *
     * @access public
     */
    public function getMiddleware()
    {
        return new SimpleAuthMiddleware();
    }


    /**
     * Login action
     *
     * @return mixed
     *
     * @access public
     */
    public function login()
    {
        $messages = Auth::messages();

        $this->loadView('login', compact('messages'));
    }


    /**
     * Logout action
     *
     * @return mixed
     *
     * @access public
     */
    public function logout()
    {
        redirect(route(config_item('success_logout_route')));
    }


    /**
     * Signup action
     *
     * @return mixed
     *
     * @access public
     */
    public function signup()
    {
        // Loading required libraries

        $this->load->database();
        $this->load->library('form_validation');

        // Setting required paths and fields

        $assetsPath   = base_url(config_item('simpleauth_assets_dir'));
        $signupFields = $this->getSignupFields();

        if($_POST)
        {
            $this->copyAssets(config_item('simpleauth_skin'));

            //
            // Processing the submited form
            //

            $user = [];
            $userFields = $this->getUserFields();

            foreach($signupFields as $fieldName => $attrs)
            {
                // Setting validation

                if(isset($attrs['checkbox']) || isset($attrs['radio']) || isset($attrs['select']))
                {
                    $type = isset($attrs['checkbox']) ? 'checkbox' : (isset($attrs['radio']) ? 'radio' : 'select');
                    unset($attrs[$type]);
                    list($fieldLabel, , $validationRules) = $attrs;
                    $validationMessages =  isset($attrs[3]) ? $attrs[3] : [];
                }
                else
                {
                    list(, $fieldLabel, , $validationRules) = $attrs;
                    $validationMessages =  isset($attrs[4]) ? $attrs[4] : [];
                }

                if((in_array($fieldName, $userFields) || isset($userFields[$fieldName])) && !empty($this->input->post($fieldName)))
                {
                    $user[$fieldName] = $this->input->post($fieldName);

                    if($fieldName == config_item('simpleauth_password_col'))
                    {
                        $user[$fieldName] = Auth::loadUserProvider($this->getUserProvider())->hashPassword($user[$fieldName]);
                    }
                }

                $this->form_validation->set_rules($fieldName, $fieldLabel, $validationRules, $validationMessages);
            }

            foreach($userFields as $fieldName => $defaultValue)
            {
                if(is_string($fieldName) && !isset($user[$fieldName]))
                {
                    $user[$fieldName] = $defaultValue;
                }
            }

            if($this->form_validation->run() === TRUE)
            {
                // Is the form valid? let's store the user

                $emailVerificationEnabled = config_item('simpleauth_enable_email_verification');

                $this->load->library('encryption');

                if($emailVerificationEnabled)
                {
                    $user[config_item('simpleauth_verified_col')] = 0;
                }

                $this->db->insert(config_item('simpleauth_users_table'), $user);

                $title = 'Sign up success!';

                if($emailVerificationEnabled)
                {

                    $emailVerificationKey = bin2hex( $this->encryption->create_key(16) );
                    $emailVerificationUrl = route('email_verification', [ 'token' => $emailVerificationKey])
                        . '?email=' . $user[config_item('simpleauth_email_field')];

                    $this->db->insert(
                        config_item('simpleauth_users_email_verification_table'),
                        [
                            'email'      => $user['email'],
                            'token'      => $emailVerificationKey,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]
                    );

                    // Sending email verification message

                    $this->load->library('email');
                    $this->load->library('parser');

                    if(!empty(config_item('simpleauth_email_configuration')))
                    {
                        $this->email->initialize(config_item('simpleauth_email_configuration'));
                    }

                    $this->email->from(config_item('simpleauth_email_address'), config_item('simpleauth_email_name'));
                    $this->email->to($user[config_item('simpleauth_email_field')]);
                    $this->email->subject('[' . config_item('simpleauth_email_name') . '] Verify your email address');

                    $emailBody = $this->parser->parse_string(
                        config_item('simpleauth_email_verification_message'),
                        [
                            'first_name'       => $user[config_item('simpleauth_email_first_name_field')],
                            'verification_url' => $emailVerificationUrl,
                        ]
                    );

                    Debug::log("Confirmation email:\n$emailBody", 'info','auth');

                    $this->email->message($emailBody);
                    $this->email->send();

                    $message = 'We have sent you an email with the instructions to activate your account. If you can\'t find it, please check your spam folder';
                }
                else
                {
                    $message = 'You can log in to the user area';
                }

                return $this->showMessage($title, $message);
            }
        }

        $validationErrors = $this->form_validation->error_array();

        return $this->loadView('signup', compact('validationErrors', 'signupFields'));
    }


    /**
     * emailVerification action
     *
     * @param  mixed   $token
     *
     * @return mixed
     *
     * @access public
     */
    public function emailVerification($token)
    {
        if(config_item('simpleauth_enable_email_verification') !== true)
        {
            return redirect( route('login') );
        }

        $this->load->database();

        $email = $this->input->get('email');

        if( empty($email) )
        {
            return $this->showMessage(
                'Email verification error',
                'The token is not valid or already used'
            );
        }

        // Verifying the token

        $verificationToken = $this->db->get_where(
            config_item('simpleauth_users_email_verification_table'),
            [
                'token' => $token,
                'email' => $email,
                'created_at <=' => date('Y-m-d H:i:s', time() + (60 * 60 * 2)) // 2 hours
            ]
        )->result();

        $user = $this->db->get_where(
            config_item('simpleauth_users_table'),
            [
                config_item('simpleauth_username_col') => $email,
                config_item('simpleauth_active_col')   => 1,
            ]
        )->result();

        if( empty($verificationToken) || empty($user) )
        {
            return $this->showMessage(
                'Email verification error',
                'The token is not valid or already used'
            );
        }

        $verificationToken = $verificationToken[0];
        $user = $user[0];

        $this->db->update(
            config_item('simpleauth_users_table'),
            [
                config_item('simpleauth_verified_col') => 1
            ],
            [
                'id' => $user->id
            ]
        );

        $this->db->delete(
            config_item('simpleauth_users_email_verification_table'),
            [
                'id' => $verificationToken->id
            ]
        );

        return $this->showMessage(
            'Success!',
            'Your account email is now verified. <a href=" ' . route('login') . '">Login</a>'
        );
    }


    /**
     * passwordReset action
     *
     * @return mixed
     *
     * @access public
     */
    public function passwordReset()
    {
        $messages = [];

        $this->load->library('form_validation');

        if($_POST)
        {

            $this->form_validation->set_rules(
                'email', 'Email',
                [
                    'required', 'valid_email'
                ]
            );

            if($this->form_validation->run() === true)
            {
                $this->load->database();

                // First, check if the user exists

                $user = $this->db->get_where(
                    config_item('simpleauth_users_table'),
                    [
                        config_item('simpleauth_username_col') => $this->input->post('email'),
                        config_item('simpleauth_active_col')   => 1,
                    ]
                )->result();


                Debug::log('Password reset user: ', 'info', 'auth');
                Debug::log($user, 'info', 'auth');

                if(!empty($user))
                {
                    // Then, check how many times the password reset has been requested from
                    // this email address (max 3 within 2 hours)

                    $requestCount = $this->db->where('email', $this->input->post('email'))
                        ->where('created_at >=', date('Y-m-d H:i:s'))
                        ->where('created_at <=', date('Y-m-d H:i:s', time() + (60 * 60 * 2))) // 2 hours
                        ->count_all_results(config_item('simpleauth_password_resets_table'));

                    Debug::log('Password reset count for this email: ' . $requestCount, 'info', 'auth');

                    if($requestCount < 3)
                    {
                        $user = $user[0];

                        $this->load->library('encryption');
                        $this->load->library('email');
                        $this->load->library('parser');

                        $emailPasswordResetKey = bin2hex($this->encryption->create_key(16));
                        $emailPasswordResetUrl = route('password_reset_form', ['token' => $emailPasswordResetKey])
                            . '?email=' . $this->input->post('email');

                        $this->db->update(
                            config_item('simpleauth_password_resets_table'),
                            [
                                'active' => 0,
                            ],
                            [
                                'email' => $this->input->post('email'),
                                'id !=' => null // <--Some MySQL modes doesn't allow update/delete queries without
                                                 // a primary key or unique index
                            ]
                        );

                        $this->db->insert(
                            config_item('simpleauth_password_resets_table'),
                            [
                                'email'      => $this->input->post('email'),
                                'token'      => $emailPasswordResetKey,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );

                        // Sending password reset message

                        if(!empty(config_item('simpleauth_email_configuration')))
                        {
                            $this->email->initialize(config_item('simpleauth_email_configuration'));
                        }

                        $this->email->from(config_item('simpleauth_email_address'), config_item('simpleauth_email_name'));
                        $this->email->to($this->input->post('email'));
                        $this->email->subject('[' . config_item('simpleauth_email_name') . '] Password reset');

                        $emailBody = $this->parser->parse_string(
                            config_item('simpleauth_password_reset_message'),
                            [
                                'first_name'         => $user->{config_item('simpleauth_email_first_name_field')},
                                'password_reset_url' => $emailPasswordResetUrl,
                            ]
                        );

                        Debug::log("Password reset email:\n$emailBody", 'info','auth');

                        $this->email->message($emailBody);
                        $this->email->send();
                    }
                    else
                    {
                        Debug::log("Password reset attempt ignored: limit reached", 'error', 'auth');
                    }
                }
                else
                {
                    Debug::log("Password reset attempt ignored: user not found", 'error', 'auth');
                }
            }

            $messages['success'] = 'If the email address exists, instructions will be sent there';
        }

        $validationErrors = $this->form_validation->error_array();

        return $this->loadView('password_reset', compact('messages', 'validationErrors'));
    }


    /**
     * Password reset form
     *
     * @param  mixed  $token
     *
     * @return mixed
     *
     * @access public
     */
    public function passwordResetForm($token)
    {
        $messages = [];

        $this->load->database();
        $this->load->library('form_validation');

        // Verifying the token

        $email = $this->input->get('email');

        if( empty($email) )
        {
            return $this->showMessage(
                'Error',
                'The password reset token is not valid or already used'
            );
        }

        $verificationToken = $this->db->get_where(
            config_item('simpleauth_password_resets_table'),
            [
                'token'  => $token,
                'email'  => $email,
                'active' => 1,
                'created_at <=' => date('Y-m-d H:i:s', time() + (60 * 60 * 2)) // 2 hours
            ]
        )->result();

        $user = $this->db->get_where(
            config_item('simpleauth_users_table'),
            [
                config_item('simpleauth_username_col') => $email,
                config_item('simpleauth_active_col')   => 1,
            ]
        )->result();

        if( empty($verificationToken) || empty($user) )
        {
            return $this->showMessage(
                'Email verification error',
                'The token is not valid or already used'
            );
        }

        $verificationToken = $verificationToken[0];
        $user = $user[0];

        if($_POST)
        {
            $this->form_validation->set_rules(
                'new_password', 'New password',
                [
                    'required', 'min_length[8]', 'matches[repeat_password]'
                ],
                [
                    'matches' => 'The new passwords does not match.'
                ]
            );
            $this->form_validation->set_rules(
                'repeat_password', 'Repeat password',
                [
                    'required'
                ]
            );

            if($this->form_validation->run() === true)
            {
                $this->db->update(
                    config_item('simpleauth_users_table'),
                    [
                        config_item('simpleauth_password_col') => Auth::loadUserProvider($this->getUserProvider())
                            ->hashPassword( $this->input->post('new_password') )
                    ],
                    [
                        config_item('simpleauth_username_col') => $email,
                    ]
                );

                $this->db->delete(
                    config_item('simpleauth_password_resets_table'),
                    [
                        'active'  => 0,
                    ],
                    [
                        'id'  => $verificationToken->id,
                    ]
                );

                return $this->showMessage(
                    'Password changed successfully',
                    'Go to <a href=" ' . route('login') . '">Login</a> page'
                );
            }
        }

        $validationErrors = $this->form_validation->error_array();

        return $this->loadView('password_reset_form', compact('messages', 'validationErrors'));
    }


    /**
     * Password confirm prompt (if the user is not fully authenticated)
     *
     * @return mixed
     *
     * @access public
     */
    public function confirmPassword()
    {
        if(Auth::isGuest())
        {
            return redirect( route('auth_login_route') );
        }

        if(Auth::session('fully_authenticated') === TRUE)
        {
            return redirect( route('auth_login_route_redirect') );
        }

        $messages = [];

        $this->load->database();
        $this->load->library('form_validation');

        if($_POST)
        {
            $this->form_validation->set_rules(
                'current_password', 'Current Password',
                [
                    'required', ['current_password', function($password){

                        $userProvider = Auth::loadUserProvider($this->getUserProvider());
                        $storedHash   = Auth::user()->{config_item('simpleauth_password_col')};

                        return $userProvider->verifyPassword($password,$storedHash);
                    }]
                ],
                [
                    'required'         => 'Please enter your password',
                    'current_password' => 'The password is incorrect'
                ]
            );

            if($this->form_validation->run() === TRUE)
            {
                $redirectTo = $this->input->get('redirect_to');

                if(empty($redirectTo))
                {
                    $redirectTo = route('auth_login_route_redirect');
                }

                $baseUrl = config_item('base_url');

                if(substr($redirectTo,0,strlen($baseUrl)) != $baseUrl)
                {
                    if(substr($redirectTo,0,7) == 'http://' || substr($redirectTo,0,8) == 'https://')
                    {
                        $redirectTo = route(config_item('auth_login_route_redirect'));
                    }
                    else
                    {
                        $redirectTo = base_url($redirectTo);
                    }
                }

                Auth::session('fully_authenticated', true);

                return redirect( $redirectTo );
            }
        }

        $validationErrors = $this->form_validation->error_array();

        return $this->loadView('password_prompt', compact('messages', 'validationErrors'));
    }
}
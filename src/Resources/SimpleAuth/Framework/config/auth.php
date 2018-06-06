<?php

/**
 * --------------------------------------------------------------------------------------
 *                                General Auth configuration
 * --------------------------------------------------------------------------------------
 */

//
// Basic routing
//

$config['auth_login_route']  = 'login';

$config['auth_logout_route'] = 'logout';

$config['auth_login_route_redirect']  = 'dashboard';

$config['auth_logout_route_redirect'] = 'homepage';

$config['auth_route_auto_redirect']   = [

    # The following routes will redirect to the 'auth_login_route_redirect' if
    # the user is logged in:

    'login',
    'signup',
    'password_reset',
    'password_reset_form'
];

//
// Main login form
//

$config['auth_form_username_field'] = 'email';

$config['auth_form_password_field'] = 'password';

//
// Session & Cookies configuration
//

$config['auth_session_var']  = 'auth';


/**
 * --------------------------------------------------------------------------------------
 *                                SimpleAuth configuration
 * --------------------------------------------------------------------------------------
 */

//
// Enable/disable features
//

$config['simpleauth_enable_signup'] = TRUE;

$config['simpleauth_enable_password_reset'] = TRUE;

$config['simpleauth_enable_remember_me'] = TRUE;

$config['simpleauth_enable_email_verification'] = TRUE;

$config['simpleauth_enforce_email_verification'] = FALSE;

$config['simpleauth_enable_brute_force_protection'] = TRUE;

//
// Email configuration
//

$config['simpleauth_email_configuration'] = null;

$config['simpleauth_email_address'] = 'noreply@example.com';

$config['simpleauth_email_name']    = 'Example';

// Please note: you must configure your email settings in order to use HTML in messages.
// The {first_name} value will be taken of the $config['simpleauth_email_first_name_field']
// field and must exists in your sign up form.

$config['simpleauth_email_verification_message'] = <<<MESSAGE
    <p>Hi {first_name}!</p>

    <p>Please verify your email address by clicking the following link:</p>

    <a href="{verification_url}">Verify my email</a>

    <p>If not works, copy and paste in your browser:<br>
    {verification_url}</p>
MESSAGE;

$config['simpleauth_password_reset_message'] = <<<MESSAGE
    <p>Hi {first_name}!</p>

    <p>To reset your password, follow this link:</p>

    <a href="{password_reset_url}">Reset my password</a>

    <p>If not works, copy and paste in your browser:<br>
    {password_reset_url}</p>
MESSAGE;

//
// General configuration
//

$config['simpleauth_user_provider'] = 'User';

$config['simpleauth_skin'] = 'default';

$config['simpleauth_assets_dir'] = 'assets/auth';

// This must match with the email field of your sign up form:
$config['simpleauth_email_field']  = 'email';

$config['simpleauth_email_first_name_field']  = 'first_name';

$config['simpleauth_remember_me_field'] = 'remember_me';

//
// Database configuration
//

$config['simpleauth_users_table']  = 'users';

$config['simpleauth_users_email_verification_table']  = 'email_verifications';

$config['simpleauth_password_resets_table']  = 'password_resets';

$config['simpleauth_login_attempts_table']  = 'login_attempts';

$config['simpleauth_username_col'] = 'email';

$config['simpleauth_password_col'] = 'password';

$config['simpleauth_role_col']     = 'role';

$config['simpleauth_active_col']   = 'active';

$config['simpleauth_verified_col'] = 'verified';

$config['simpleauth_remember_me_col'] = 'remember_token';

//
// Sessions & Cookies configuration
//

$config['simpleauth_remember_me_cookie'] = 'remember_me';
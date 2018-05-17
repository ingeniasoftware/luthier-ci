<?php

/**
 * Database users table
 */
$config['users_table']  = 'users';

/**
 * The login route
 */
$config['login_route']  = 'login';

/**
 * The logout route
 */
$config['logout_route'] = 'logout';

/**
 * Login form username input name
 */
$config['login_username_field'] = 'email';

/**
 * Login form password input name
 */
$config['login_password_field'] = 'password';

/**
 * Number of failed attempts for display Google ReCaptcha
 */
$config['failed_attempts_for_captcha'] = 5;

/**
 * Number of failed attempts for IP Ban
 */
$config['failed_attempts_for_ban'] = 10;

/**
 * Database login attempts table
 */
$config['failed_login_attempts_table'] = 'login_attemps';
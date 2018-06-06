<?php

/**
 * User Provider Interface
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Auth;

use Luthier\Auth\UserInterface;

interface UserProviderInterface
{
    public function getUserClass();

    public function loadUserByUsername($username, $password = null);

    public function hashPassword($password);

    public function verifyPassword($password, $hash);

    public function checkUserIsActive(UserInterface $user);

    public function checkUserIsVerified(UserInterface $user);
}
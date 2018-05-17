<?php

namespace Luthier\Auth;

use Luthier\Auth\AuthUserInterface;

interface AuthUserProviderInterface
{
    public function loadByUsername($username, $userClass);

    public function refreshUser(AuthUserInterface $user);

    public function hashPassword($password);

    public function verifyPassword($password, $hash);
}
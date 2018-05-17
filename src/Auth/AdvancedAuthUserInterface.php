<?php

namespace Luthier\Auth;

interface AdvancedUserInterface
{
    public function isAccountVerified();

    public function isAccountNotSuspended();

    public function isAccountNotExpired();
}

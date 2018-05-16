<?php

namespace Luthier\Auth\Interfaces;

interface AdvancedUserInterface
{
    public function isGranted($role);
}

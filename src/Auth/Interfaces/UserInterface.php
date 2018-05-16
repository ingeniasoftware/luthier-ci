<?php

namespace Luthier\Auth\Interfaces;

interface UserInterface
{
    public function setRoles($roles);

    public function getRoles();

    public function isRole($role);

    public function isGuess();

    public function update();

    public function store();

    public function destroy();
}


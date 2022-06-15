<?php

namespace NF_FU_VENDOR\Composer\Installers;

class FuelphpInstaller extends BaseInstaller
{
    protected $locations = array('component' => 'components/{$name}/');
}

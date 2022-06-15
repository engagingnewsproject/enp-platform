<?php

namespace NF_FU_VENDOR\Composer\Installers;

class MODULEWorkInstaller extends BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}

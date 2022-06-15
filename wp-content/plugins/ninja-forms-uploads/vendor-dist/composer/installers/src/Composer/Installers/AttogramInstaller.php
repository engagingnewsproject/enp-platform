<?php

namespace NF_FU_VENDOR\Composer\Installers;

class AttogramInstaller extends BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}

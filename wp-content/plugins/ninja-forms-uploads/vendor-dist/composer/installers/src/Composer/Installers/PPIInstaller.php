<?php

namespace NF_FU_VENDOR\Composer\Installers;

class PPIInstaller extends BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}

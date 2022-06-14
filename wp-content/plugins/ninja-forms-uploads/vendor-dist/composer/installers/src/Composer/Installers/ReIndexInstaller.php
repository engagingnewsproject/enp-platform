<?php

namespace NF_FU_VENDOR\Composer\Installers;

class ReIndexInstaller extends BaseInstaller
{
    protected $locations = array('theme' => 'themes/{$name}/', 'plugin' => 'plugins/{$name}/');
}

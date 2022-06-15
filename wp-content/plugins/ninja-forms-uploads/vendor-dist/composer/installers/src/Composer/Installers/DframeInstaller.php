<?php

namespace NF_FU_VENDOR\Composer\Installers;

class DframeInstaller extends BaseInstaller
{
    protected $locations = array('module' => 'modules/{$vendor}/{$name}/');
}

<?php

namespace NF_FU_VENDOR\Composer\Installers;

class CiviCrmInstaller extends BaseInstaller
{
    protected $locations = array('ext' => 'ext/{$name}/');
}

<?php

namespace NF_FU_VENDOR\Composer\Installers;

class ItopInstaller extends BaseInstaller
{
    protected $locations = array('extension' => 'extensions/{$name}/');
}

<?php

namespace NF_FU_VENDOR\Composer\Installers;

class MakoInstaller extends BaseInstaller
{
    protected $locations = array('package' => 'app/packages/{$name}/');
}

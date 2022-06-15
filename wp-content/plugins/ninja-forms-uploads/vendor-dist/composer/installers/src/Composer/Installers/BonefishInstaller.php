<?php

namespace NF_FU_VENDOR\Composer\Installers;

class BonefishInstaller extends BaseInstaller
{
    protected $locations = array('package' => 'Packages/{$vendor}/{$name}/');
}

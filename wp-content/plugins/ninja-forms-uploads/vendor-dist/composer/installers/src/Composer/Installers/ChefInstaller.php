<?php

namespace NF_FU_VENDOR\Composer\Installers;

class ChefInstaller extends BaseInstaller
{
    protected $locations = array('cookbook' => 'Chef/{$vendor}/{$name}/', 'role' => 'Chef/roles/{$name}/');
}

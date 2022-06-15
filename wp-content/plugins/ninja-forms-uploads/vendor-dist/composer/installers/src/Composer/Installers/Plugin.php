<?php

namespace NF_FU_VENDOR\Composer\Installers;

use NF_FU_VENDOR\Composer\Composer;
use NF_FU_VENDOR\Composer\IO\IOInterface;
use NF_FU_VENDOR\Composer\Plugin\PluginInterface;
class Plugin implements PluginInterface
{
    private $installer;
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->installer = new Installer($io, $composer);
        $composer->getInstallationManager()->addInstaller($this->installer);
    }
    public function deactivate(Composer $composer, IOInterface $io)
    {
        $composer->getInstallationManager()->removeInstaller($this->installer);
    }
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}

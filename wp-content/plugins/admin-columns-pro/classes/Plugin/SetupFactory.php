<?php

declare(strict_types=1);

namespace ACP\Plugin;

use AC;
use AC\ListScreenRepository\Storage;
use AC\Plugin\InstallCollection;
use AC\Plugin\UpdateCollection;
use ACA\JetEngine;
use ACA\MetaBox;
use ACP\AdminColumnsPro;
use ACP\Plugin\Update\V7000\FileRepository;

class SetupFactory extends AC\Plugin\SetupFactory
{

    private AdminColumnsPro $plugin;

    private Storage $storage;

    public function __construct(
        string $version_key,
        AdminColumnsPro $plugin,
        Storage $storage,
        ?InstallCollection $installers = null,
        ?UpdateCollection $updates = null
    ) {
        parent::__construct($version_key, $plugin->get_version(), $installers, $updates);

        $this->plugin = $plugin;
        $this->storage = $storage;
    }

    public function create(string $type): AC\Plugin\Setup
    {
        switch ($type) {
            case self::NETWORK:
                $this->updates = new UpdateCollection([
                    new NetworkUpdate\V5000(),
                    new NetworkUpdate\V5700(),
                ]);

                break;
            case self::SITE:
                $this->updates = new UpdateCollection([
                    new Update\V4101(),
                    new Update\V4301($this->plugin->get_dir()),
                    new Update\V5000(),
                    new Update\V5201(),
                    new Update\V5300(),
                    new Update\V5400(),
                    new Update\V5700(),
                    new Update\V6000(),
                    new Update\V6002(),
                    new Update\V6300($this->storage),
                    new Update\V6400($this->storage),
                    new Update\V7000(
                        new FileRepository($this->storage),
                        new MetaBox\FieldRepository(new MetaBox\FieldFactory()),
                        new JetEngine\FieldRepository(new JetEngine\FieldFactory()),
                        new AC\TableScreenFactory\Aggregate()
                    ),
                ]);

                break;
        }

        return parent::create($type);
    }

}
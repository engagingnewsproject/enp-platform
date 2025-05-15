<?php

namespace ACA\MetaBox;

use AC;
use AC\Registerable;
use AC\Services;
use ACP\Service\IntegrationStatus;

class MetaBox implements Registerable
{

    private $location;

    public function __construct(AC\Asset\Location\Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        if ( ! $this->is_metabox_active()) {
            return;
        }

        $this->create_services()->register();
    }

    private function is_metabox_active(): bool
    {
        if (class_exists('RWMB_Loader', false)) {
            return true;
        }

        // All in One loader needs MetaBox to be disabled, all logic is loaded in the `admin_init` hook
        if (class_exists('MBAIO\Loader', false)) {
            return true;
        }

        return false;
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\Columns(new ColumnFactory(), new RelationColumnFactory(), new RelationshipRepository()),
            new Service\ColumnInstantiate(new RelationshipRepository()),
            new Service\QuickAdd(),
            new Service\ListScreens(),
            new Service\Scripts($this->location),
            new Service\Storage(),
            new IntegrationStatus('ac-addon-metabox'),
        ]);
    }

}
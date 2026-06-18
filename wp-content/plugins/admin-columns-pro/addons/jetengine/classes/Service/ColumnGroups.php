<?php

declare(strict_types=1);

namespace ACA\JetEngine\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Type\Group;

final class ColumnGroups implements AC\Registerable
{

    public const JET_ENGINE = 'jet_engine';
    public const JET_ENGINE_RELATION = 'jet_engine_relation';

    private Absolute $location;

    public function __construct(Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        add_action('ac/column/groups', [$this, 'register_column_groups']);
    }

    public function register_column_groups(AC\Type\Groups $groups)
    {
        $groups->add(
            new Group(
                self::JET_ENGINE, __('JetEngine', 'codepress-admin-columns'), 14,
                $this->location->with_suffix('/assets/images/jetengine.svg')->get_url()
            )
        );
        $groups->add(
            new Group(
                self::JET_ENGINE_RELATION,
                __('JetEngine Relationship', 'codepress-admin-columns'),
                14,
                $this->location->with_suffix('/assets/images/jetengine.svg')->get_url()
            )
        );
    }

}
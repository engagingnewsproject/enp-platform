<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\ManageValue;

use AC;
use AC\ListScreen;
use AC\Registerable;
use AC\Table\ManageValueServiceFactory;
use AC\TableScreen;
use ACP\ConditionalFormat\ActiveRulesResolver;
use ACP\ConditionalFormat\Operators;

class RenderableServiceFactory implements ManageValueServiceFactory
{

    private TableScreen\ManageValueServiceFactory $factory;

    private ActiveRulesResolver $activeRulesResolver;

    private Operators $operators;

    public function __construct(
        TableScreen\ManageValueServiceFactory $factory,
        ActiveRulesResolver $activeRulesResolver,
        Operators $operators
    ) {
        $this->factory = $factory;
        $this->activeRulesResolver = $activeRulesResolver;
        $this->operators = $operators;
    }

    public function create(TableScreen $table_screen, ListScreen $list_screen): ?Registerable
    {
        if ( ! $this->factory->can_create($table_screen)) {
            return null;
        }

        $factory = new AC\Table\ManageValue\TableRenderFactory($list_screen);

        $rules = $this->activeRulesResolver->find($list_screen);

        if ($rules) {
            $factory = new TableRenderFactory(
                $list_screen,
                $this->operators,
                $rules
            );
        }

        return $this->factory->create(
            $table_screen,
            $factory
        );
    }

}
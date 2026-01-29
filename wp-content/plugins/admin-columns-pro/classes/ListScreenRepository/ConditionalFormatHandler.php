<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use ACP\ConditionalFormat\RulesRepository;
use ACP\Exception\FailedToSaveConditionalFormattingException;

final class ConditionalFormatHandler
{

    private RulesRepository $repository;

    public function __construct(RulesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function load(ListScreen $list_screen): void
    {
        $list_screen->set_conditional_format(
            $this->repository->find_all_shared($list_screen->get_id())
                             ->with_existing_columns($list_screen)
        );
    }

    /**
     * @throws FailedToSaveConditionalFormattingException
     */
    public function save(ListScreen $list_screen): void
    {
        // We only support a single set (currently), so remove anything that exists
        $this->repository->delete_all_shared($list_screen->get_id());

        $rules = $list_screen->get_conditional_format();

        foreach ($rules as $rule) {
            // Do not store empty shared rules
            if ( ! $rule->has_user_id() && ! $rule->get_rule_collection()->count()) {
                continue;
            }

            $this->repository->save($rule);
        }
    }

    public function delete(ListScreen $list_screen): void
    {
        $this->repository->delete_all($list_screen->get_id());
    }

}
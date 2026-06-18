<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\RequestHandler;

use AC\ListScreenRepository\Storage;
use ACP\ConditionalFormat\ActiveRulesResolver;
use ACP\ConditionalFormat\RequestHandler;
use ACP\Exception\FailedToSaveConditionalFormattingException;

final class ApplyRules extends RequestHandler
{

    private ActiveRulesResolver $active_rules_resolver;

    private Storage $list_screen_repository;

    public function __construct(
        Storage $list_screen_repository,
        ActiveRulesResolver $active_rules_resolver
    ) {
        parent::__construct();

        $this->list_screen_repository = $list_screen_repository;
        $this->active_rules_resolver = $active_rules_resolver;
    }

    protected function get_required_fields(): array
    {
        return ['key', 'list_id'];
    }

    public function handle_validated(): void
    {
        $list_screen = $this->list_screen_repository->find($this->get_list_id());

        if ( ! $list_screen) {
            wp_send_json('List screen not found.', 500);
        }

        try {
            $this->active_rules_resolver->save(
                $list_screen,
                $this->get_key()
            );
        } catch (FailedToSaveConditionalFormattingException $e) {
            wp_send_json($e->getMessage(), 500);
        }

        wp_send_json('', 200);
    }

}
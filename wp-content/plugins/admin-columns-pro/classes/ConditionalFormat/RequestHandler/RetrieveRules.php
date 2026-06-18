<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\RequestHandler;

use AC\ColumnNamesTrait;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use ACP\ConditionalFormat\ActiveRulesResolver;
use ACP\ConditionalFormat\Encoder;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\RequestHandler;
use ACP\ConditionalFormat\RulesCollection;
use ACP\ConditionalFormat\RulesRepository\Database;
use ACP\ConditionalFormat\Type\Key;

final class RetrieveRules extends RequestHandler
{

    use ColumnNamesTrait;

    private Storage $list_screen_repository;

    private Encoder $encoder;

    private Database $database_rules_repository;

    private ActiveRulesResolver $active_rules_resolver;

    public function __construct(
        Storage $list_screen_repository,
        Database $database_rules_repository,
        Encoder $encoder,
        ActiveRulesResolver $active_rules_resolver
    ) {
        parent::__construct();

        $this->list_screen_repository = $list_screen_repository;
        $this->encoder = $encoder;
        $this->database_rules_repository = $database_rules_repository;
        $this->active_rules_resolver = $active_rules_resolver;
    }

    protected function get_required_fields(): array
    {
        return ['list_id'];
    }

    public function handle_validated(): void
    {
        $list_screen = $this->list_screen_repository->find($this->get_list_id());

        if ( ! $list_screen) {
            wp_send_json('List screen not found.', 500);
        }

        // Find by key
        if ($this->has_key()) {
            $rules = $this->find_by_key($list_screen, $this->get_key());

            if ($rules) {
                $this->send_response(new RulesCollection([$rules]));
            }

            wp_send_json([], 200);
        }

        // Find personal rules
        if ($this->has_user_id()) {
            $rules = $this->database_rules_repository
                ->find_all_personal($list_screen->get_id(), $this->get_user_id())
                ->with_existing_columns($list_screen)
                ->first();

            if ($rules) {
                $this->send_response(new RulesCollection([$rules]));
            }

            wp_send_json([], 200);
        }

        // Find by global
        $this->send_response($list_screen->get_conditional_format());
    }

    private function find_by_key(ListScreen $list_screen, Key $key): ?Rules
    {
        // Find personal rules...
        $rules = $this->database_rules_repository->find($list_screen->get_id(), $key);

        if ($rules) {
            return $rules->with_existing_columns($list_screen);
        }

        // ... otherwise find matching global rules
        $collection = $list_screen->get_conditional_format();

        foreach ($collection as $rules) {
            if ($rules->get_key()->equals($key)) {
                return $rules;
            }
        }

        return null;
    }

    private function send_response(RulesCollection $collection): void
    {
        wp_send_json(
            $this->encoder->encode($collection),
            200
        );
    }

}
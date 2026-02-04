<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\RequestHandler;

use AC\Capabilities;
use ACP\ConditionalFormat\RequestHandler;
use ACP\ConditionalFormat\RulesRepository\Database;

final class RemoveRules extends RequestHandler
{

    private Database $rules_repository;

    public function __construct(
        Database $rules_repository
    ) {
        parent::__construct();

        $this->rules_repository = $rules_repository;
    }

    protected function get_required_fields(): array
    {
        return ['list_id', 'key'];
    }

    protected function validate(): void
    {
        parent::validate();
        $this->validate_user_rights();
    }

    protected function validate_user_rights(): void
    {
        parent::validate_user_rights();

        $rules = $this->rules_repository->find($this->get_list_id(), $this->get_key());

        if ( ! $rules) {
            wp_send_json('', 400);
        }

        // You can only delete your own, unless you have wider permissions
        if ( ! current_user_can(Capabilities::MANAGE) &&
             ( ! $rules->has_user_id() || $rules->get_user_id() !== get_current_user_id())
        ) {
            wp_send_json('', 401);
        }
    }

    public function handle_validated(): void
    {
        $this->rules_repository->delete($this->get_list_id(), $this->get_key());

        wp_send_json('', 200);
    }

}
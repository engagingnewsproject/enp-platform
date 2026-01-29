<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\RequestHandler;

use AC\DateFormats;
use AC\ListScreenRepository\Storage;
use ACP\ConditionalFormat\ActiveRulesResolver;
use ACP\ConditionalFormat\Decoder;
use ACP\ConditionalFormat\RequestHandler;
use ACP\ConditionalFormat\RulesCollection;
use ACP\ConditionalFormat\RulesRepository\Database;
use ACP\ConditionalFormat\RulesSchema;
use ACP\ConditionalFormat\Type\KeyGenerator;
use Throwable;

final class SaveRules extends RequestHandler
{

    private Storage $list_screen_repository;

    private Decoder $decoder;

    private ActiveRulesResolver $active_rules_resolver;

    private KeyGenerator $rules_key_generator;

    private Database $rules_repository;

    public function __construct(
        Storage $list_screen_repository,
        Decoder $decoder,
        ActiveRulesResolver $active_rules_resolver,
        KeyGenerator $rules_key_generator,
        Database $rules_repository
    ) {
        parent::__construct();

        $this->list_screen_repository = $list_screen_repository;
        $this->decoder = $decoder;
        $this->active_rules_resolver = $active_rules_resolver;
        $this->rules_key_generator = $rules_key_generator;
        $this->rules_repository = $rules_repository;
    }

    protected function get_required_fields(): array
    {
        $required = ['rules', 'list_id'];

        if ( ! $this->has_user_id()) {
            $required[] = 'name';
        }

        return $required;
    }

    protected function validate(): void
    {
        parent::validate();

        $this->validate_user_rights();
    }

    public function handle_validated(): void
    {
        $list_screen = $this->list_screen_repository->find($this->get_list_id());

        if ( ! $list_screen) {
            wp_send_json('Could not retrieve list screen.', 400);
        }

        try {
            $user_id = $this->has_user_id()
                ? $this->get_user_id()
                : null;

            $key = $this->has_key()
                ? $this->get_key()
                : $this->rules_key_generator->generate();

            $decoded[RulesSchema::DATA] = json_decode($this->request->get('rules', []), true, 512, JSON_THROW_ON_ERROR);
            $decoded[RulesSchema::KEY] = (string)$key;
            $decoded[RulesSchema::LIST_SCREEN_ID] = (string)$list_screen->get_id();
            $decoded[RulesSchema::DATE_MODIFIED] = date(DateFormats::DATE_MYSQL_TIME);
            $decoded[RulesSchema::USER_ID] = $user_id;

            $rules = $this->decoder->decode($decoded);

            if ($user_id) {
                $this->rules_repository->delete_all_personal($list_screen->get_id(), $user_id);
                $this->rules_repository->save($rules);
            } else {
                $list_screen->set_conditional_format(
                    new RulesCollection([$rules])
                );

                $this->list_screen_repository->save($list_screen);
            }

            $this->active_rules_resolver->save($list_screen, $key, $user_id);
        } catch (Throwable $e) {
            wp_send_json($e->getMessage(), 500);
        }

        wp_send_json_success();
    }

}
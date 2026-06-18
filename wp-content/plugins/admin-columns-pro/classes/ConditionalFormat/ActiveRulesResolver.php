<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\ListScreen;
use AC\Preferences\Preference;
use AC\Storage\UserMeta;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\RulesRepository\Database;
use ACP\ConditionalFormat\Type\Key;
use ACP\Exception\FailedToSaveConditionalFormattingException;

final class ActiveRulesResolver
{

    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    // Retrieve

    public function find(ListScreen $list_screen, ?int $user_id = null): ?Rules
    {
        $user_id = $this->resolve_user_id($user_id);

        $key = (string)$this->get_user_preference($user_id)
                            ->find((string)$list_screen->get_id());

        $rules = null;

        if (Key::validate($key)) {
            $rules = $this->find_by_key($list_screen, new Key($key));
        }

        return $rules
            // try personal rules...
            ?: $this->find_current_personal($list_screen, $user_id)
                // ...then global rules
                ?: $this->find_current_global($list_screen);
    }

    private function find_current_personal(ListScreen $list_screen, int $user_id): ?Rules
    {
        return $this->database->find_all_personal($list_screen->get_id(), $user_id)
                              ->with_existing_columns($list_screen)
                              ->first();
    }

    // Save

    /**
     * @throws FailedToSaveConditionalFormattingException
     */
    public function save(ListScreen $list_screen, Key $key, ?int $user_id = null): void
    {
        $user_id = $this->resolve_user_id($user_id);

        if ( ! $this->find_by_key($list_screen, $key)) {
            throw new FailedToSaveConditionalFormattingException(
                sprintf('Could not find rules for list screen %s key %s', $list_screen->get_id(), $key)
            );
        }

        $this->get_user_preference($user_id)
             ->save((string)$list_screen->get_id(), (string)$key);
    }

    // Helpers

    private function resolve_user_id(?int $user_id = null): int
    {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        return $user_id;
    }

    private function find_by_key(ListScreen $list_screen, Key $key): ?Rules
    {
        // Find personal rules...
        $rules = $this->database->find($list_screen->get_id(), $key);

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

    private function find_current_global(ListScreen $list_screen): ?Rules
    {
        return $list_screen->get_conditional_format()
                           ->first();
    }

    private function get_user_preference(int $user_id): Preference
    {
        return new Preference(new UserMeta('_ac_conditional_format_applied_rules', $user_id));
    }

}
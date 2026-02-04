<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\Type\ListScreenId;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\Type\Key;
use ACP\Exception\FailedToSaveConditionalFormattingException;

abstract class RulesRepository
{

    // Retrieve

    public function find(ListScreenId $list_screen_id, Key $key): ?Rules
    {
        return $this->fetch_results($list_screen_id, $key)
                    ->first();
    }

    abstract public function find_all_shared(ListScreenId $list_screen_id): RulesCollection;

    abstract public function find_all_personal(ListScreenId $list_screen_id, int $user_id): RulesCollection;

    public function find_all(ListScreenId $list_screen_id): RulesCollection
    {
        return $this->fetch_results(
            $list_screen_id
        );
    }

    // Save

    /**
     * @throws FailedToSaveConditionalFormattingException
     */
    abstract public function save(Rules $rules): void;

    // Delete

    abstract public function delete(ListScreenId $list_screen_id, Key $key): void;

    abstract public function delete_all(ListScreenId $list_screen_id): void;

    abstract public function delete_all_personal(ListScreenId $list_screen_id, int $user_id): void;

    abstract public function delete_all_shared(ListScreenId $list_screen_id): void;

    // Helpers

    abstract protected function fetch_results(
        ListScreenId $list_screen_id,
        ?Key $key = null
    ): RulesCollection;

}
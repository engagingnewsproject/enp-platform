<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Entity;

use AC\ListScreen;
use AC\Type\ColumnId;
use AC\Type\ListScreenId;
use ACP\ConditionalFormat\RuleCollection;
use ACP\ConditionalFormat\Type\Key;
use DateTime;

final class Rules
{

    private Key $key;

    private string $name;

    private RuleCollection $rule_collection;

    private ListScreenId $list_id;

    private ?int $user_id;

    private DateTime $modified;

    public function __construct(
        Key $key,
        string $name,
        RuleCollection $rule_collection,
        ListScreenId $list_id,
        ?int $user_id = null,
        ?DateTime $modified = null
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->rule_collection = $rule_collection;
        $this->list_id = $list_id;
        $this->user_id = $user_id;
        $this->modified = $modified ?? new DateTime('now');
    }

    public function get_key(): Key
    {
        return $this->key;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_rule_collection(): RuleCollection
    {
        return $this->rule_collection;
    }

    public function get_list_id(): ListScreenId
    {
        return $this->list_id;
    }

    public function has_user_id(): bool
    {
        return $this->user_id !== null;
    }

    public function get_user_id(): ?int
    {
        return $this->user_id;
    }

    public function get_modified(): DateTime
    {
        return $this->modified;
    }

    public function with_existing_columns(ListScreen $list_screen): self
    {
        $rule_collection = new RuleCollection();

        foreach ($this->rule_collection as $rule) {
            if ($list_screen->get_column(new ColumnId($rule->get_column_name()))) {
                $rule_collection->add($rule);
            }
        }

        return $this->with_rule_collection($rule_collection);
    }

    public function with_rule_collection(RuleCollection $rule_collection): self
    {
        return new self(
            $this->key,
            $this->name,
            $rule_collection,
            $this->list_id,
            $this->user_id,
            $this->modified
        );
    }

    public function with_list_id_and_key(ListScreenId $list_id, Key $key): self
    {
        return new self(
            $key,
            $this->name,
            $this->rule_collection,
            $list_id,
            $this->user_id,
            $this->modified
        );
    }
}
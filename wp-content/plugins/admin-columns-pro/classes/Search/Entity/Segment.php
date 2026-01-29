<?php

declare(strict_types=1);

namespace ACP\Search\Entity;

use AC\Type\ListScreenId;
use ACP\Search\Type\SegmentKey;
use DateTime;
use LogicException;

final class Segment
{

    private SegmentKey $key;

    private string $name;

    private array $url_parameters;

    private ListScreenId $list_id;

    private ?int $user_id;

    private ?DateTime $modified;

    public function __construct(
        SegmentKey $key,
        string $name,
        array $url_parameters,
        ListScreenId $list_id,
        ?int $user_id = null,
        ?DateTime $modified = null
    ) {
        if ($modified === null) {
            $modified = $this->get_modified_now();
        }

        $this->key = $key;
        $this->name = $name;
        $this->url_parameters = $url_parameters;
        $this->user_id = $user_id;
        $this->modified = $modified;
        $this->list_id = $list_id;
    }

    public function get_key(): SegmentKey
    {
        return $this->key;
    }

    public function get_user_id(): int
    {
        if ( ! $this->has_user_id()) {
            throw new LogicException('Segment has no user id.');
        }

        return $this->user_id;
    }

    public function has_user_id(): bool
    {
        return $this->user_id !== null;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_url_parameters(): array
    {
        return $this->url_parameters;
    }

    public function get_modified(): DateTime
    {
        return $this->modified;
    }

    public function has_modified(): bool
    {
        return $this->modified !== null;
    }

    public function with_modified_now(): Segment
    {
        return new self(
            $this->key,
            $this->name,
            $this->url_parameters,
            $this->list_id,
            $this->user_id,
            $this->get_modified_now()
        );
    }

    public function get_list_id(): ListScreenId
    {
        return $this->list_id;
    }

    public function with_list_id_and_key(ListScreenId $list_id, SegmentKey $key): self
    {
        return new self(
            $key,
            $this->name,
            $this->url_parameters,
            $list_id,
            $this->user_id,
            $this->modified
        );
    }

    private function get_modified_now(): DateTime
    {
        return new DateTime();
    }

}
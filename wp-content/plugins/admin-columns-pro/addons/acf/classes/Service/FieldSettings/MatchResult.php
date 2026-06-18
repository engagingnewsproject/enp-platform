<?php

declare(strict_types=1);

namespace ACA\ACF\Service\FieldSettings;

use AC\Column;
use AC\Type\Uri;

class MatchResult
{

    private int $added_count;

    private ?Uri $first_editor_url;

    private ?Uri $first_view_url;

    private string $first_added_title;

    /** @var array<string, ?Column> */
    private array $column_matches;

    /** @var array<string, Uri> */
    private array $base_urls;

    /**
     * @param array<string, ?Column> $column_matches
     * @param array<string, Uri>     $base_urls
     */
    public function __construct(
        int $added_count,
        ?Uri $first_editor_url,
        ?Uri $first_view_url,
        string $first_added_title,
        array $column_matches,
        array $base_urls
    ) {
        $this->added_count = $added_count;
        $this->first_editor_url = $first_editor_url;
        $this->first_view_url = $first_view_url;
        $this->first_added_title = $first_added_title;
        $this->column_matches = $column_matches;
        $this->base_urls = $base_urls;
    }

    public function get_added_count(): int
    {
        return $this->added_count;
    }

    public function get_first_editor_url(): ?Uri
    {
        return $this->first_editor_url;
    }

    public function get_first_view_url(): ?Uri
    {
        return $this->first_view_url;
    }

    public function get_first_added_title(): string
    {
        return $this->first_added_title;
    }

    /**
     * @return array<string, ?Column>
     */
    public function get_column_matches(): array
    {
        return $this->column_matches;
    }

    /**
     * @return array<string, Uri>
     */
    public function get_base_urls(): array
    {
        return $this->base_urls;
    }

}

<?php

namespace ACP\Service;

use AC\Registerable;
use AC\Type\Integration;

class IntegrationStatus implements Registerable
{

    private string $slug;

    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    public function register(): void
    {
        add_filter('ac/integration/active', [$this, 'is_active'], 10, 2);
    }

    public function is_active(bool $active, Integration $integration): bool
    {
        if ($integration->get_slug() === $this->slug) {
            return true;
        }

        return $active;
    }

}
<?php

declare(strict_types=1);

namespace ACP\Service;

use AC;
use AC\Registerable;
use ACP\AddonCollection;

final class Addon implements Registerable
{

    private AddonCollection $addons;

    private AC\Storage\Repository\IntegrationStatus $status;

    public function __construct(
        AddonCollection $addons,
        AC\Storage\Repository\IntegrationStatus $status
    ) {
        $this->addons = $addons;
        $this->status = $status;
    }

    public function register(): void
    {
        foreach ($this->addons as $addon) {
            if ( ! $this->is_active($addon->get_id())) {
                continue;
            }

            $addon->register();
        }
    }

    private function is_active(string $id): bool
    {
        $is_active = $this->status->is_active(sprintf('ac-addon-%s', $id));

        return apply_filters('acp/addon/' . $id . '/active', $is_active);
    }

}

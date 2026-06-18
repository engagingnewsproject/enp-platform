<?php

namespace ACP\Editing\Preference;

use AC\Preferences\Preference;
use AC\Preferences\SiteFactory;
use AC\Type\TableId;

class EditState
{

    public function storage(): Preference
    {
        return (new SiteFactory())->create('editability_state');
    }

    public function is_active(TableId $id): bool
    {
        $value = $this->storage()->find((string)$id);

        if (null === $value) {
            $value = apply_filters('acp/editing/inline/button_default_state', false);
        }

        // '1' (string) is for backwards compatibility
        return in_array($value, ['1', 1, true], true);
    }

    public function set_status(TableId $id, bool $is_active): void
    {
        $this->storage()->save(
            (string)$id,
            (int)$is_active
        );
    }

}
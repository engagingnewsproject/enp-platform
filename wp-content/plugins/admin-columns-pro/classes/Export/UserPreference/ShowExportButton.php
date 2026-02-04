<?php

namespace ACP\Export\UserPreference;

use AC\Preferences\Preference;
use AC\Preferences\SiteFactory;
use AC\Type\TableId;

final class ShowExportButton
{

    private function storage(): Preference
    {
        return (new SiteFactory())->create('show_export_button');
    }

    public function is_active(TableId $table_id): bool
    {
        return 0 !== $this->storage()->find((string)$table_id);
    }

    public function set_status(TableId $table_id, bool $active): void
    {
        $this->storage()->save(
            (string)$table_id,
            (int)$active
        );
    }

}
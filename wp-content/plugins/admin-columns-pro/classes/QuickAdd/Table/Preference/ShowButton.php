<?php

namespace ACP\QuickAdd\Table\Preference;

use AC\Preferences\Preference;
use AC\Preferences\SiteFactory;
use AC\Type\TableId;

class ShowButton
{

    private Preference $storage;

    public function __construct(?int $user_id = null)
    {
        $this->storage = (new SiteFactory())->create('show_new_inline_button', $user_id);
    }

    public function is_active(TableId $table_id): bool
    {
        return in_array($this->storage->find((string)$table_id), [true, null], true);
    }

    public function set_status(TableId $table_id, bool $active): void
    {
        $this->storage->save((string)$table_id, $active);
    }

}
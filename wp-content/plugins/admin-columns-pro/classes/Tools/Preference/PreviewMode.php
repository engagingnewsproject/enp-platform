<?php

declare(strict_types=1);

namespace ACP\Tools\Preference;

use AC\Preferences\Preference;
use AC\Preferences\SiteFactory;
use AC\Type\ListScreenId;

class PreviewMode
{

    private Preference $storage;

    public function __construct()
    {
        $this->storage = (new SiteFactory())->create('migrate_preview_mode');
    }

    public function set_active(ListScreenId $id): void
    {
        $this->storage->save('list_screen', (string)$id);
    }

    public function set_inactive(): void
    {
        $this->storage->delete('list_screen');
    }

    public function is_active(ListScreenId $id): bool
    {
        return $this->has_list_screen_id() && $this->get_list_screen_id()->equals($id);
    }

    public function is_enabled(): bool
    {
        return $this->has_list_screen_id();
    }

    private function has_list_screen_id(): bool
    {
        return ListScreenId::is_valid_id($this->storage->find('list_screen'));
    }

    private function get_list_screen_id(): ListScreenId
    {
        return new ListScreenId($this->storage->find('list_screen'));
    }

}
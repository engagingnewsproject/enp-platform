<?php

namespace ACP\Export\UserPreference;

use AC\Preferences\Preference;
use AC\Preferences\SiteFactory;
use AC\Type\ListScreenId;
use LogicException;

final class ExportedColumns
{

    public function storage(): Preference
    {
        return (new SiteFactory())->create('export_columns');
    }

    private function validate_item(array $column_state): void
    {
        if ( ! isset($column_state['column_name'], $column_state['active'])) {
            throw new LogicException('Invalid item.');
        }
    }

    public function save(ListScreenId $id, array $column_states): void
    {
        array_map([$this, 'validate_item'], $column_states);

        $this->storage()->save(
            (string)$id,
            $column_states
        );
    }

    public function exists(ListScreenId $id): bool
    {
        return null !== $this->storage()->find((string)$id);
    }

    public function get(ListScreenId $id): array
    {
        return $this->storage()->find((string)$id);
    }

    public function delete(ListScreenId $id): void
    {
        $this->storage()->delete((string)$id);
    }

}
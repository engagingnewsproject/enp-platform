<?php

declare(strict_types=1);

namespace ACA\RankMath\Editing\Service;

use AC\MetaType;
use AC\Type\ToggleOptions;
use ACP\Editing\Service;
use ACP\Editing\View;

class Robots implements Service
{

    private const META_KEY = 'rank_math_robots';

    private string $key;

    private MetaType $meta_type;

    public function __construct(string $key, MetaType $meta_type)
    {
        $this->key = $key;
        $this->meta_type = $meta_type;
    }

    public function get_view(string $context): ?View
    {
        return new View\Toggle(ToggleOptions::create_from_array([
            '0' => 'False',
            '1' => 'True',
        ]));
    }

    public function get_value(int $id): int
    {
        $base = get_metadata((string)$this->meta_type, $id, self::META_KEY, true);

        if (empty($base)) {
            return 0;
        }

        return in_array($this->key, $base, true) ? 1 : 0;
    }

    public function update(int $id, $data): void
    {
        $robot_data = get_metadata((string)$this->meta_type, $id, self::META_KEY, true);

        if ( ! is_array($robot_data)) {
            $robot_data = [];
        }

        if ($data === '0') {
            $robot_data = $this->unset_key($robot_data, $this->key);

            if ($this->key === 'index') {
                $robot_data[] = 'noindex';
            }

            if ($this->key === 'noindex') {
                $robot_data[] = 'index';
            }
        } else {
            $robot_data[] = $this->key;

            if ($this->key === 'index') {
                $robot_data = $this->unset_key($robot_data, 'noindex');
            }

            if ($this->key === 'noindex') {
                $robot_data = $this->unset_key($robot_data, 'index');
            }
        }

        update_metadata((string)$this->meta_type, $id, self::META_KEY, $robot_data);
    }

    private function unset_key($data, $key)
    {
        $index_key = array_search($key, $data, true);

        if ($index_key !== false) {
            unset($data[$index_key]);
        }

        return $data;
    }

}
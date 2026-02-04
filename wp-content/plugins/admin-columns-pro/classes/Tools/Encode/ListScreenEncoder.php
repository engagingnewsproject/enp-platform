<?php

declare(strict_types=1);

namespace ACP\Tools\Encode;

use AC;
use AC\Column;
use ACP\Search\SegmentCollection;

class ListScreenEncoder
{

    use Column\ColumnLabelTrait;

    public function encode(AC\ListScreen $list_screen, ?string $source = null, ?string $repository = null): array
    {
        return [
            'id'          => (string)$list_screen->get_id(),
            'label'       => trim($list_screen->get_title()) ?: $list_screen->get_label(),
            'edit_url'    => (string)$list_screen->get_editor_url(),
            'view_url'    => (string)$list_screen->get_table_url(),
            'table'       => $list_screen->get_label(),
            'read_only'   => $list_screen->is_read_only(),
            'description' => $this->get_description($list_screen),
            'columns'     => $this->get_columns($list_screen),
            'segments'    => $this->encode_segments($list_screen->get_segments() ?? new SegmentCollection()),
            'source'      => $source,
            'repository'  => $repository,
        ];
    }

    private function encode_segments(SegmentCollection $segments): array
    {
        $data = [];

        foreach ($segments as $segment) {
            $data[(string)$segment->get_key()] = $segment->get_name();
        }

        return $data;
    }

    private function get_description(AC\ListScreen $list_screen): string
    {
        $column_names = [];

        /**
         * @var Column $column
         */
        foreach ($list_screen->get_columns() as $column) {
            $column_names[] = $this->get_column_label($column);
        }

        $segment_names = [];
        $segments = $list_screen->get_segments();

        foreach ($segments as $segment) {
            $segment_names[] = $segment->get_name();
        }

        $html = ac_helper()->html->tooltip(
            sprintf(_n('%s column', '%s columns', count($column_names)), count($column_names)),
            ac_helper()->string->enumeration_list($column_names, 'and')
        );

        if ($segment_names) {
            $html = sprintf(
                "%s %s %s",
                $html,
                __('and', 'codepress-admin-columns'),
                ac_helper()->html->tooltip(
                    sprintf(_n('%s saved filter', '%s saved filters', count($segment_names)), count($segment_names)),
                    ac_helper()->string->enumeration_list($segment_names, 'and')
                )
            );
        }

        return $html;
    }

    private function get_columns(AC\ListScreen $list_screen): array
    {
        $column_names = [];

        /**
         * @var Column $column
         */
        foreach ($list_screen->get_columns() as $column) {
            $column_names[] = $this->get_column_label($column);
        }

        return $column_names;
    }
}
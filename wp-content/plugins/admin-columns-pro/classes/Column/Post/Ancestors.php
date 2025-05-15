<?php

namespace ACP\Column\Post;

use AC\Collection;
use AC\Column;
use AC\Settings;
use ACP\ConditionalFormat;
use ACP\Export;
use ACP\Search;
use ACP\Sorting;

class Ancestors extends Column
    implements Export\Exportable, Search\Searchable, Sorting\Sortable,
               ConditionalFormat\Formattable
{

    use ConditionalFormat\ConditionalFormatTrait;

    public function __construct()
    {
        $this->set_type('column-ancestors');
        $this->set_label(__('Ancestors', 'codepress-admin-columns'));
    }

    /**
     * @return string
     */
    public function get_separator()
    {
        return '<span class="dashicons dashicons-arrow-right-alt2"></span>';
    }

    public function get_value($id)
    {
        $ancestors = $this->get_ancestor_ids($id);

        if ( ! $ancestors) {
            return $this->get_empty_char();
        }

        /**
         * @var Collection $formatted_values
         */
        $formatted_values = $this->get_formatted_value(new Collection($ancestors));

        return $formatted_values->implode($this->get_separator());
    }

    /**
     * @param int $id
     *
     * @return array|false
     */
    public function get_ancestor_ids($id)
    {
        $ancestors = $this->get_raw_value($id);

        if (empty($ancestors)) {
            return false;
        }

        return array_reverse($ancestors);
    }

    public function get_raw_value($id)
    {
        return get_post($id)->ancestors;
    }

    public function register_settings()
    {
        $this->add_setting(new Settings\Column\Post($this));
    }

    public function is_valid()
    {
        return is_post_type_hierarchical($this->get_post_type());
    }

    public function export()
    {
        return new Export\Model\Post\Ancestors($this);
    }

    public function search()
    {
        return new Search\Comparison\Post\Ancestors();
    }

    public function sorting()
    {
        return new Sorting\Model\Post\Depth($this->get_post_type());
    }

}
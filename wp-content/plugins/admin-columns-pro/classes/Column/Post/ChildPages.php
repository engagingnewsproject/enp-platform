<?php

namespace ACP\Column\Post;

use AC;
use ACP\Export;
use ACP\Search;

class ChildPages extends AC\Column
    implements Export\Exportable, Search\Searchable
{

    public function __construct()
    {
        $this->set_type('column-child-pages')
             ->set_label(__('Child Pages', 'codepress-admin-columns'));
    }

    public function get_value($post_id)
    {
        $titles = [];

        $ids = $this->get_raw_value($post_id);

        if ($ids) {
            foreach ($ids as $id) {
                $post = get_post($id);

                $titles[] = ac_helper()->html->link(get_edit_post_link($id), $post->post_title);
            }
        }

        if (empty($titles)) {
            return $this->get_empty_char();
        }

        $number_of_items = $this->get_setting(AC\Settings\Column\NumberOfItems::NAME)->get_value();

        return $number_of_items
            ? ac_helper()->html->more($titles, $number_of_items)
            : ac_helper()->string->enumeration_list($titles, 'and');
    }

    public function get_raw_value($post_id)
    {
        return get_posts([
            'post_type'      => $this->get_post_type(),
            'post_parent'    => $post_id,
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ]);
    }

    protected function register_settings()
    {
        $this->add_setting(new AC\Settings\Column\NumberOfItems($this));
    }

    public function is_valid()
    {
        return is_post_type_hierarchical($this->get_post_type()) || post_type_supports(
                $this->get_post_type(),
                'page-attributes'
            );
    }

    public function export()
    {
        return new Export\Model\Post\ChildPages($this->get_post_type());
    }

    public function search()
    {
        return new Search\Comparison\Post\ChildPages($this->get_post_type());
    }

}
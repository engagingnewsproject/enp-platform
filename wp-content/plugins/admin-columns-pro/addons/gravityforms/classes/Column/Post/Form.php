<?php

namespace ACA\GravityForms\Column\Post;

use AC;
use ACP\Export\Exportable;
use ACP\Export\Model\StrippedValue;
use ACP\Settings;
use GFAPI;

class Form extends AC\Column
    implements Exportable
{

    public function __construct()
    {
        $this->set_type('column-gb_block_gf_form');
        $this->set_label(__('Form (Gutenberg)', 'codepress-admin-columns'));
        $this->set_group('gravity_forms');
    }

    public function get_value($id)
    {
        $post = get_post($id);

        if ( ! has_blocks($post->post_content)) {
            return $this->get_empty_char();
        }

        $blocks = parse_blocks($post->post_content);
        $forms_ids = $this->get_block_structure($blocks, []);

        $form_titles = array_map([$this, 'get_form_title'], $forms_ids);

        return implode(',', $form_titles);
    }

    private function get_block_structure($blocks, $forms_ids = [])
    {
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'gravityforms/form') {
                $forms_ids[] = $block['attrs']['formId'];
            }

            if ( ! empty($block['innerBlocks'])) {
                $forms_ids = $this->get_block_structure($block['innerBlocks'], $forms_ids);
            }
        }

        return $forms_ids;
    }

    private function get_form_title($id)
    {
        $form = GFAPI::get_form($id);

        if ( ! $form) {
            return sprintf(__('Form %d', 'codepress-admin-columns'), $id);
        }

        $title = sprintf(__('%s (#%s)', 'codepress-admin-columns'), $form['title'], $id);

        return ac_helper()->html->link(admin_url('admin.php?page=gf_entries&id=' . $id), $title);
    }

    public function export()
    {
        return new StrippedValue($this);
    }

}
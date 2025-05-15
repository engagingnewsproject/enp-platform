<?php

namespace ACA\YoastSeo\Column\Post;

use AC;
use ACA\YoastSeo;
use ACA\YoastSeo\Editing;
use ACP;
use WP_Term;

class PrimaryTaxonomy extends AC\Column\Meta
    implements ACP\Editing\Editable, ACP\Export\Exportable, ACP\ConditionalFormat\Formattable, ACP\Search\Searchable
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    public function __construct()
    {
        $this->set_label(__('Primary Taxonomy', 'codepress-admin-columns'))
             ->set_group('yoast-seo')
             ->set_type('column-wpseo_column_taxonomy');
    }

    public function get_value($id)
    {
        $raw_value = $this->get_raw_value($id);
        if ( ! $raw_value) {
            return $this->get_empty_char();
        }

        $term = get_term($raw_value, $this->get_taxonomy());

        if ( ! $term instanceof WP_Term) {
            return $this->get_empty_char();
        }

        $terms = ac_helper()->taxonomy->get_term_links(
            [
                $term,
            ],
            $this->get_post_type() ?: null
        );

        if (empty($terms)) {
            return $this->get_empty_char();
        }

        return ac_helper()->string->enumeration_list($terms, 'and');
    }

    public function get_meta_key()
    {
        return '_yoast_wpseo_primary_' . $this->get_taxonomy();
    }

    protected function register_settings()
    {
        $this->add_setting(new YoastSeo\Setting\PrimaryTaxonomy($this));
    }

    public function editing()
    {
        return new Editing\Service\Post\PrimaryTaxonomy($this->get_taxonomy());
    }

    public function export()
    {
        return new ACP\Export\Model\StrippedValue($this);
    }

    public function search()
    {
        return new YoastSeo\Search\Post\PrimaryTaxonomy(
            $this->get_meta_key(),
            $this->get_taxonomy(),
            (new AC\Meta\QueryMetaFactory())->create_with_post_type($this->get_meta_key(), $this->get_post_type())
        );
    }

    /**
     * @return string
     */
    public function get_taxonomy(): string
    {
        $setting = $this->get_setting('primary_taxonomy');

        if ( ! $setting instanceof YoastSeo\Setting\PrimaryTaxonomy) {
            return '';
        }

        return (string)$setting->get_primary_taxonomy();
    }

}
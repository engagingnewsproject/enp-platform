<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Column\Taxonomy;

use ACA\YoastSeo\Column;

class RelatedKeyphrases extends Column\TermMeta
{

    public function __construct()
    {
        $this->set_label(__('Related Keyphrases', 'codepress-admin-columns'))
             ->set_group('yoast-seo')
             ->set_type('wpseo-score-related_keyphrases');
    }

    protected function get_meta_key()
    {
        return 'wpseo_focuskeywords';
    }

    public function get_value($id)
    {
        $raw = json_decode($this->get_raw_value($id));
        $value = [];

        if (empty($raw)) {
            return $this->get_empty_char();
        }

        foreach ($raw as $keyphrase) {
            $value[] = sprintf('<strong>%s</strong>: %s', $keyphrase->keyword, $keyphrase->score);
        }

        return implode('<br>', $value);
    }

}
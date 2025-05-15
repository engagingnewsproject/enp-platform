<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Column\Post;

use AC\Column;

class RelatedKeyphrases extends Column
{

    public function __construct()
    {
        $this->set_label(__('Related Keyphrases', 'codepress-admin-columns'))
             ->set_group('yoast-seo')
             ->set_type('wpseo-score-related_keyphrases');
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

    public function get_raw_value($id)
    {
        return get_post_meta($id, '_yoast_wpseo_focuskeywords', true);
    }

}
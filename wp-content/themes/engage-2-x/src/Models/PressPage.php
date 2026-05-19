<?php
/**
 * Helpers for pages that use the Press page template.
 *
 * Most Press pages list items by category and sort by publication date. Some
 * pages also have a per-page ACF relationship field for drag-to-reorder; see
 * MANUAL_ORDER_PAGES, getManualOrderFieldName(), and orderPressPosts().
 */
namespace Engage\Models;

use Timber\Post;
use WP_Post;

class PressPage extends Post
{
    /**
     * Press pages with manual order.
     *
     * Add a row when you create a new relationship field: page slug, ACF field
     * name, and field key (from acf-json). Slugs are used instead of post IDs.
     */
    private const MANUAL_ORDER_PAGES = [
        'solidarity-journalism-videos' => [
            'field' => 'solidarity_journalism_videos',
            'key' => 'field_6a0c8b0f8713f',
        ],
        'solidarity-reporting-additional-resources' => [
            'field' => 'solidarity_journalism_additional_resources',
            'key' => 'field_6a0c9779b6160',
        ],
        'examples-of-solidarity-reporting' => [
            'field' => 'examples_of_solidarity_reporting',
            'key' => 'field_6a0c99071464f',
        ],
        'climate-change-articles' => [
            'field' => 'climate_change_articles',
            'key' => 'field_6a0c9c53a1315',
        ],
        'dangerous-solidarity-additional-resources' => [
            'field' => 'dangerous_solidarity_additional_resources',
            'key' => 'field_6a0c9e0787f40',
        ],
        'stronger-together-safety-in-networks' => [
            'field' => 'stronger_together_safety_in_networks',
            'key' => 'field_6a0c9f34f7f1b',
        ],
    ];

    public $columns;
    public $rows;
    public $html_string;

    /**
     * ACF relationship field names used for manual press ordering on Press pages.
     *
     * @return string[]
     */
    public static function getManualOrderFieldNames(): array
    {
        return array_column(self::MANUAL_ORDER_PAGES, 'field');
    }

    /**
     * ACF field keys for manual-order relationship fields (for prepare_field/key hooks).
     *
     * @return array<string, string> field_key => field_name
     */
    public static function getManualOrderFieldKeyMap(): array
    {
        $map = [];
        foreach (self::MANUAL_ORDER_PAGES as $config) {
            $map[$config['key']] = $config['field'];
        }

        return $map;
    }

    /**
     * Whether an ACF field is a registered manual-order relationship field.
     */
    public static function isManualOrderField(string $field_name): bool
    {
        return in_array($field_name, self::getManualOrderFieldNames(), true);
    }

    /**
     * Resolves the manual-order field name from an ACF field array.
     *
     * Prefer field key (reliable in acf/prepare_field); fall back to _name / name.
     *
     * @param array<string, mixed> $field ACF field array.
     */
    public static function resolveManualOrderFieldName(array $field): ?string
    {
        $key = (string) ($field['key'] ?? '');
        $key_map = self::getManualOrderFieldKeyMap();
        if ($key !== '' && isset($key_map[$key])) {
            return $key_map[$key];
        }

        $name = (string) ($field['_name'] ?? $field['name'] ?? '');
        if ($name !== '' && self::isManualOrderField($name)) {
            return $name;
        }

        return null;
    }

    /**
     * Page ID for the post currently being edited in wp-admin (ACF screen).
     */
    public static function getEditorPageId(): int
    {
        if (function_exists('acf_get_form_data')) {
            $post_id = acf_get_form_data('post_id');
            if (is_numeric($post_id) && (int) $post_id > 0) {
                return (int) $post_id;
            }
        }

        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            return (int) $_GET['post'];
        }

        global $post;

        if ($post instanceof WP_Post && $post->post_type === 'page') {
            return (int) $post->ID;
        }

        return 0;
    }

    /**
     * Returns the manual-order ACF field name for a Press page, if configured.
     *
     * @param int $page_id WordPress page ID.
     * @return string|null ACF field name, or null when this page uses date sort only.
     */
    public static function getManualOrderFieldName(int $page_id): ?string
    {
        $slug = get_post_field('post_name', $page_id);

        return self::MANUAL_ORDER_PAGES[$slug]['field'] ?? null;
    }

    /**
     * Whether a manual-order field should show in the editor for this page.
     */
    public static function shouldShowManualOrderField(string $field_name, int $page_id): bool
    {
        return self::getManualOrderFieldName($page_id) === $field_name;
    }

    /**
     * Loads drag-order values from the relationship field mapped to this page.
     *
     * @param int $page_id WordPress page ID.
     * @return array<int, mixed>|null Relationship field value, or null if not mapped.
     */
    public static function getManualOrder(int $page_id): ?array
    {
        $field_name = self::getManualOrderFieldName($page_id);
        if ($field_name === null) {
            return null;
        }

        $value = get_field($field_name, $page_id);

        if ($value === null || $value === false || $value === []) {
            return null;
        }

        return is_array($value) ? $value : null;
    }

    /**
     * Fetches every published press item in the categories chosen on the page.
     *
     * Editors pick categories in the Press ACF "Posts" field; this returns all
     * matching press posts. Sorting is handled separately by orderPressPosts().
     *
     * @param int[] $term_ids Press category term IDs from the page ACF field.
     * @return WP_Post[]
     */
    public static function getPostsByTermIds(array $term_ids): array
    {
        if ($term_ids === []) {
            return [];
        }

        return get_posts([
            'post_type' => 'press',
            'numberposts' => -1,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'press-categories',
                    'field' => 'term_id',
                    'terms' => $term_ids,
                ],
            ],
        ]);
    }

    /**
     * Builds the final list order for a Press page.
     *
     * Category membership decides which posts appear. When editors have set a
     * manual order, those posts follow drag order. Any other posts in the category
     * are placed first, newest publication date first, so new items show up
     * without editing the page.
     *
     * @param WP_Post[] $posts All posts from the selected categories.
     * @param WP_Post[]|array<int>|null|false $manual_order Posts from the ACF
     *   relationship field (drag order), or null/false to sort by date only.
     * @return WP_Post[]
     */
    public static function orderPressPosts(array $posts, $manual_order): array
    {
        if ($manual_order === null || $manual_order === [] || $manual_order === false) {
            return self::sortByPublicationDate($posts);
        }

        $posts_by_id = [];
        foreach ($posts as $post) {
            $posts_by_id[$post->ID] = $post;
        }

        $ordered = [];
        foreach ($manual_order as $manual_post) {
            $id = is_object($manual_post) ? $manual_post->ID : (int) $manual_post;
            if (isset($posts_by_id[$id])) {
                $ordered[] = $posts_by_id[$id];
                unset($posts_by_id[$id]);
            }
        }

        $remaining = self::sortByPublicationDate(array_values($posts_by_id));

        return array_merge($remaining, $ordered);
    }

    /**
     * Sorts press posts by publication date, newest first.
     *
     * Used as the default on generic Press pages and for posts that are not yet
     * in the manual order list. Falls back to WordPress post date, then title.
     *
     * @param WP_Post[] $posts
     * @return WP_Post[]
     */
    public static function sortByPublicationDate(array $posts): array
    {
        usort($posts, static function (WP_Post $a, WP_Post $b): int {
            $date_a = (string) get_post_meta($a->ID, 'press_article_publication_date', true);
            $date_b = (string) get_post_meta($b->ID, 'press_article_publication_date', true);

            if ($date_a !== $date_b) {
                return strcmp($date_b, $date_a);
            }

            $post_date_cmp = strcmp($b->post_date, $a->post_date);
            if ($post_date_cmp !== 0) {
                return $post_date_cmp;
            }

            return strcasecmp($a->post_title, $b->post_title);
        });

        return $posts;
    }

    public function generate_table()
    {
        $this->generateTableStructure($this->content);
    }

    public function generateTableStructure($html_input)
    {
        $removed_tags = explode('</p>', implode('', explode('<p>', $html_input)));
        $rows = [];
        foreach ($removed_tags as $comma_row) {
            $row_seperated = explode('|', trim($comma_row));
            if (count($row_seperated) > 0 && strlen($row_seperated[0]) > 0) {
                array_push($rows, $row_seperated);
            }
        }
        $this->columns = array_shift($rows);
        $this->rows = $rows;
    }
}

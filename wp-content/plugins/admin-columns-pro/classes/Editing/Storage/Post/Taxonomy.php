<?php

namespace ACP\Editing\Storage\Post;

use AC\Storage\Transaction;
use ACP\Editing\Storage;
use InvalidArgumentException;
use RuntimeException;
use WP_Error;
use WP_Term;

class Taxonomy implements Storage
{

    /**
     * @var string
     */
    private $taxonomy;

    /**
     * @var string
     */
    private $enable_term_creation;

    public function __construct(string $taxonomy, bool $enable_term_creation)
    {
        $this->taxonomy = $taxonomy;
        $this->enable_term_creation = $enable_term_creation;
    }

    public function get($id)
    {
        $terms = get_the_terms($id, $this->taxonomy);

        if ( ! $terms || is_wp_error($terms)) {
            return [];
        }

        $values = [];

        foreach ($terms as $term) {
            $values[$term->term_id] = htmlspecialchars_decode($term->name);
        }

        return $values;
    }

    public function update(int $id, $data): bool
    {
        $method = $data['method'] ?? null;

        if (null === $method) {
            $term_ids_or_names = $data
                ? $this->sanitize_array((array)$data)
                : [];

            $this->replace_terms($id, $term_ids_or_names);

            return true;
        }

        $term_ids_or_names = $data['value'] ?? [];

        if ( ! is_array($term_ids_or_names)) {
            throw new InvalidArgumentException('Invalid value');
        }

        $term_ids_or_names = $this->sanitize_array($term_ids_or_names);

        switch ($method) {
            case 'add':
                $this->add_terms($id, $term_ids_or_names);

                break;
            case 'remove':
                $this->remove_terms($id, $this->santize_term_ids($term_ids_or_names));

                break;
            default:
                $this->replace_terms($id, $term_ids_or_names);
        }

        return wp_update_post(['ID' => $id]);
    }

    private function remove_terms(int $id, array $term_ids): void
    {
        wp_remove_object_terms($id, $term_ids, $this->taxonomy);
    }

    private function create_non_existing_terms(array $term_ids_or_names): array
    {
        $transaction = new Transaction();

        $term_ids = [];
        $search_numeric_terms = apply_filters('acp/editing/taxonomy/numeric_term_names', false, $this->taxonomy);

        foreach ($term_ids_or_names as $term_id_or_name) {
            if (is_numeric($term_id_or_name)) {
                if ( ! $search_numeric_terms) {
                    $term_ids[] = $term_id_or_name;
                    continue;
                }

                $term = get_term_by('term_id', $term_id_or_name, $this->taxonomy);
                $term_ids[] = $term instanceof WP_Term
                    ? $term->term_id
                    : $this->insert_term($term_id_or_name, $transaction);

                continue;
            }

            $term = get_term_by('name', $term_id_or_name, $this->taxonomy);

            if ($term instanceof WP_Term) {
                $term_ids[] = $term->term_id;
                continue;
            }

            $term_ids[] = $this->insert_term($term_id_or_name, $transaction);
        }

        $transaction->commit();

        return array_map('intval', $term_ids);
    }

    private function insert_term(string $name, Transaction $transaction): int
    {
        $term = wp_insert_term($name, $this->taxonomy);

        if ($term instanceof WP_Error) {
            $transaction->rollback();

            throw new RuntimeException($term->get_error_message());
        }

        return (int)$term['term_id'];
    }

    private function add_terms(int $id, array $term_ids_or_names)
    {
        if ($this->enable_term_creation) {
            $term_ids = $this->create_non_existing_terms($term_ids_or_names);
        } else {
            $term_ids = $this->santize_term_ids($term_ids_or_names);
        }

        $this->set_terms($id, $term_ids, true);
    }

    public function replace_terms(int $id, array $term_ids_or_names)
    {
        if ($this->enable_term_creation) {
            $term_ids = $this->create_non_existing_terms($term_ids_or_names);
        } else {
            $term_ids = $this->santize_term_ids($term_ids_or_names);
        }

        $this->set_terms($id, $term_ids, false);
    }

    private function set_terms(int $id, array $term_ids, bool $append)
    {
        switch ($this->taxonomy) {
            case 'category':
                wp_set_post_categories($id, $term_ids, $append);

                break;
            case 'post_tag' :
                wp_set_post_tags($id, $term_ids, $append);

                break;
            default:
                wp_set_object_terms($id, $term_ids, $this->taxonomy, $append);
        }
    }

    private function santize_term_ids($term_ids): array
    {
        return array_map('intval', array_filter($term_ids, [$this, 'term_exists']));
    }

    protected function sanitize_array(array $term_ids): array
    {
        return array_unique(array_filter($term_ids));
    }

    private function term_exists($term_id): bool
    {
        return is_numeric($term_id) && get_term_by('id', $term_id, $this->taxonomy) instanceof WP_Term;
    }

}
<?php

namespace Engage\Models;

class BlogsFilterMenu extends FilterMenu
{
	
	public function __construct($options)
	{
		parent::__construct($options);
		$this->linkBase =  'postType';
		// The structure property tells the system that this filter menu should be 
		// organized by blogs categories rather than by post types or verticals.
		$this->structure = 'blogs-category';
	}
	
    public function setFilters()
    {
        $filters = [
            'title' => $this->title,
            'slug'  => $this->slug,
            'structure' => $this->structure,
            'link'  => false,
            'terms' => []
        ];

        // Get all blogs-category terms
        $terms = get_terms([
            'taxonomy' => 'blogs-category',
            'hide_empty' => true,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                // Skip uncategorized or any other unwanted terms
                if ($term->slug === 'uncategorized') {
                    continue;
                }
                $filters['terms'][$term->slug] = $this->buildFilterTerm($term, false, 'blogs');
            }
        }

        return $filters;
    }

    /**
     * Override the parent build method to skip buildBaseFilter
     */
    public function build()
    {
        $this->filters = $this->setFilters();
        $this->addManualLinks();
        $this->pruneEmptyFilters();
        return $this->filters;
    }
	
	/**
	 * Build a filter term for a taxonomy term
	 *
	 * @param object $term The term object
	 * @param mixed $unused Not used anymore, kept for backward compatibility.
	 * @param mixed $postType The post type associated with the term.
	 * @return array The filter term array
	 */
	public function buildFilterTerm($term, $unused = false, $postType = false)
	{
		return [
			'ID'    => $term->term_id,
			'slug'  => $term->slug,
			'title' => $term->name,
			'description' => $term->description,
			'link'  => $this->urlConstructor->getTermLink(
				[
					'terms' => [$term],
					'postType' => $postType,
					'base'  => $this->linkBase
				]
			),
			'count' => $term->count,
			'taxonomy' => $term->taxonomy
		];
	}
}
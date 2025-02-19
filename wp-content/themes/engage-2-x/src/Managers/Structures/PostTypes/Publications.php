<?php

namespace Engage\Managers\Structures\PostTypes;

class Publications
{

	// Constructor
	public function __construct() {}

	// Method to run the necessary actions
	public function run()
	{
		// Add action to register the post type
		add_action('init', [$this, 'register']);
		// Add action to register taxonomies (categories)
		add_action('init', [$this, 'registerTaxonomies'], 0);

		// Add meta box actions
		add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
		add_action('save_post_publication', [$this, 'saveMetaBoxes'], 10, 2);
	}

	// Method to register the custom post type 'publication'
	public function register()
	{
		$labels = array(
			'name'               => _x('Publications', 'post type general name'),
			'singular_name'      => _x('Publication', 'post type singular name'),
			'add_new'            => _x('Add New', 'publication paper'),
			'add_new_item'       => __('Add New Publications Paper'),
			'edit_item'          => __('Edit Publication'),
			'new_item'           => __('New Publication'),
			'all_items'          => __('All Publications'),
			'view_item'          => __('View Paper'),
			'search_items'       => __('Search Publications'),
			'not_found'          => __('Paper not found'),
			'not_found_in_trash' => __('Paper not found in trash'),
			'parent_item_colon'  => '',
			'menu_name'          => 'Publications',
			'rewrite'            => array('slug' => 'publication'),
		);
		$args = array(
			'labels'        => $labels,
			'description'   => '',
			'public'        => true,
			'menu_position' => 5,
			'menu_icon'     => 'dashicons-book',
			'supports'      => array('title', 'thumbnail'),
			'has_archive'   => true,
			'exclude_from_search' => false
		);
		// Register the custom post type 'publication'
		register_post_type('publication', $args);
	}

	// Method to register taxonomies (categories) for the custom post type
	public function registerTaxonomies()
	{
		$this->announcementCategory(); // Call the method to register the publication category taxonomy
	}

	// Method to register the 'publication-category' taxonomy
	public function announcementCategory()
	{
		// Labels for the 'publication-category' taxonomy
		$labels = array(
			'name'              => _x('Publications', 'taxonomy general name'),
			'singular_name'     => _x('Publication Category', 'taxonomy singular name'),
			'search_items'      => __('Search Publication Categories'),
			'all_items'         => __('All Publication Categories'),
			'parent_item'       => __('Parent Publication Category'),
			'parent_item_colon' => __('Parent Publication Category:'),
			'edit_item'         => __('Edit Publication Category'),
			'update_item'       => __('Update Publication Category'),
			'add_new_item'      => __('Add New Publication Category'),
			'new_item_name'     => __('New Publication Category Name'),
			'menu_name'         => __('Publication Category'),
		);

		// Arguments for registering the 'publication-category' taxonomy
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'has_archive'       => true,
			'rewrite'           => array('slug' => 'publication-category'),
		);

		// Register the 'publication-category' taxonomy for the 'publication' post type
		register_taxonomy('publication-category', array('publication'), $args);
	}

	/**
	 * Add meta boxes to the publication post type
	 */
	public function addMetaBoxes()
	{
		// Existing URL meta box
		add_meta_box(
			'publication_url_meta_box',
			'Publication URL',
			[$this, 'renderUrlMetaBox'],
			'publication',
			'normal',
			'high'
		);

		// Authors meta box
		add_meta_box(
			'publication_authors_meta_box',
			'Authors',
			[$this, 'renderAuthorsMetaBox'],
			'publication',
			'normal',
			'high'
		);

		// Subtitle meta box
		add_meta_box(
			'publication_subtitle_meta_box',
			'Subtitle',
			[$this, 'renderSubtitleMetaBox'],
			'publication',
			'normal',
			'high'
		);

		// Date meta box
		add_meta_box(
			'publication_date_meta_box',
			'Publication Date',
			[$this, 'renderDateMetaBox'],
			'publication',
			'normal',
			'high'
		);
	}

	/**
	 * Render the URL meta box HTML
	 * 
	 * @param WP_Post $post The post object
	 */
	public function renderUrlMetaBox($post)
	{
		// Add nonce for security
		wp_nonce_field('publication_url_meta_box', 'publication_url_meta_box_nonce');

		// Get the saved URL if it exists
		$url = get_post_meta($post->ID, '_publication_url', true);

		// Output the form field
?>
		<p>
			<label for="publication_url">URL:</label>
			<input
				type="url"
				id="publication_url"
				name="publication_url"
				value="<?php echo esc_url($url); ?>"
				style="width: 100%"
				placeholder="https://example.com" />
		</p>
		<p class="description">
			Enter the URL where this publication can be accessed.
		</p>
	<?php
	}

	public function renderAuthorsMetaBox($post)
	{
		wp_nonce_field('publication_authors_meta_box', 'publication_authors_meta_box_nonce');
		$authors = get_post_meta($post->ID, '_publication_authors', true);
	?>
		<p>
			<label for="publication_authors">Authors:</label>
			<input
				type="text"
				id="publication_authors"
				name="publication_authors"
				value="<?php echo esc_attr($authors); ?>"
				style="width: 100%"
				placeholder="e.g., John Doe, Jane Smith" />
		</p>
		<p class="description">
			Enter the authors' names, separated by commas.
		</p>
	<?php
	}

	public function renderSubtitleMetaBox($post)
	{
		wp_nonce_field('publication_subtitle_meta_box', 'publication_subtitle_meta_box_nonce');
		$subtitle = get_post_meta($post->ID, '_publication_subtitle', true);
	?>
		<p>
			<label for="publication_subtitle">Subtitle/Journal:</label>
			<input
				type="text"
				id="publication_subtitle"
				name="publication_subtitle"
				value="<?php echo esc_attr($subtitle); ?>"
				style="width: 100%"
				placeholder="e.g., Journal of Science Communication" />
		</p>
	<?php
	}

	public function renderDateMetaBox($post)
	{
		wp_nonce_field('publication_date_meta_box', 'publication_date_meta_box_nonce');
		$date = get_post_meta($post->ID, '_publication_date', true);
		$year_date = get_post_meta($post->ID, '_publication_year_date', true);
	?>
		<p>
			<label for="publication_date">Publication Date:</label>
			<input
				type="date"
				id="publication_date"
				name="publication_date"
				value="<?php echo esc_attr($date ? date('Y-m-d', strtotime($date)) : ''); ?>"
				style="width: 200px" />
		</p>
		<p>
			<label>
				<input
					type="checkbox"
					name="publication_year_date"
					value="1"
					<?php checked($year_date, '1'); ?> />
				Show only year in display
			</label>
		</p>
<?php
	}

	/**
	 * Save the meta box data
	 * 
	 * @param int $post_id The post ID
	 * @param WP_Post $post The post object
	 */
	public function saveMetaBoxes($post_id, $post)
	{
		// Check URL nonce
		if (
			isset($_POST['publication_url_meta_box_nonce']) &&
			wp_verify_nonce($_POST['publication_url_meta_box_nonce'], 'publication_url_meta_box')
		) {
			if (isset($_POST['publication_url'])) {
				update_post_meta($post_id, '_publication_url', esc_url_raw($_POST['publication_url']));
			}
		}

		// Check authors nonce
		if (
			isset($_POST['publication_authors_meta_box_nonce']) &&
			wp_verify_nonce($_POST['publication_authors_meta_box_nonce'], 'publication_authors_meta_box')
		) {
			if (isset($_POST['publication_authors'])) {
				update_post_meta($post_id, '_publication_authors', sanitize_text_field($_POST['publication_authors']));
			}
		}

		// Check subtitle nonce
		if (
			isset($_POST['publication_subtitle_meta_box_nonce']) &&
			wp_verify_nonce($_POST['publication_subtitle_meta_box_nonce'], 'publication_subtitle_meta_box')
		) {
			if (isset($_POST['publication_subtitle'])) {
				update_post_meta($post_id, '_publication_subtitle', sanitize_text_field($_POST['publication_subtitle']));
			}
		}

		// Check date nonce
		if (
			isset($_POST['publication_date_meta_box_nonce']) &&
			wp_verify_nonce($_POST['publication_date_meta_box_nonce'], 'publication_date_meta_box')
		) {
			if (isset($_POST['publication_date'])) {
				$date = sanitize_text_field($_POST['publication_date']);
				update_post_meta($post_id, '_publication_date', $date);
				update_post_meta($post_id, '_publication_formatted_date', date('Y-m-d', strtotime($date)));
			}

			$year_date = isset($_POST['publication_year_date']) ? '1' : '0';
			update_post_meta($post_id, '_publication_year_date', $year_date);
		}
	}
}

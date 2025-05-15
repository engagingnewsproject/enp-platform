<?php

namespace AC\Settings\Column;

use AC\Settings;
use AC\View;

class StringLimit extends Settings\Column {

	/**
	 * @var string
	 */
	private $string_limit;

	protected function define_options() {
		return [ 'string_limit' => 'word_limit' ];
	}

	public function create_view() {
		$setting = $this->create_element( 'select' )
		                ->set_attribute( 'data-refresh', 'column' )
		                ->set_options( $this->get_limit_options() );

		$view = new View( [
			'label'   => __( 'Text Limit', 'codepress-admin-columns' ),
			'tooltip' => __( 'Limit text to a certain number of characters or words', 'codepress-admin-columns' ),
			'setting' => $setting,
		] );

		return $view;
	}

	private function get_limit_options() {
		$options = [
			''                => __( 'No limit', 'codepress-admin-columns' ),
			'character_limit' => __( 'Character Limit', 'codepress-admin-columns' ),
			'word_limit'      => __( 'Word Limit', 'codepress-admin-columns' ),
		];

		return $options;
	}

	public function get_dependent_settings() {
		$setting = [];

		switch ( $this->get_string_limit() ) {

			case 'character_limit' :
				$setting[] = new Settings\Column\CharacterLimit( $this->column );

				break;
			case 'word_limit' :
				$setting[] = new Settings\Column\WordLimit( $this->column );

				break;
		}

		return $setting;
	}

	/**
	 * @return string
	 */
	public function get_string_limit() {
		return $this->string_limit;
	}

	/**
	 * @param string $string_limit
	 *
	 * @return true
	 */
	public function set_string_limit( $string_limit ) {
		$this->string_limit = $string_limit;

		return true;
	}

}
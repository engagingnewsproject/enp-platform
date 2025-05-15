<?php

namespace AC\Settings\Column;

use AC\Settings;
use AC\Settings\Column;
use AC\View;

class BeforeAfter extends Column
	implements Settings\FormatValue {

	const NAME = 'before_after';

	/**
	 * @var string
	 */
	private $before;

	/**
	 * @var string
	 */
	private $after;

	protected function set_name() {
		$this->name = self::NAME;
	}

	protected function define_options() {
		return [ 'before', 'after' ];
	}

	public function format( $value, $original_value ) {
		if ( ac_helper()->string->is_empty( $value ) ) {
			return $value;
		}

		if ( $this->get_before() || $this->get_after() ) {
			$value = $this->get_before() . $value . $this->get_after();
		}

		return $value;
	}

	protected function get_before_element() {
		$text = $this->create_element( 'text', 'before' );
		$text->set_attribute( 'placeholder', $this->get_default( 'before' ) );

		return $text;
	}

	protected function get_after_element() {
		$text = $this->create_element( 'text', 'after' );
		$text->set_attribute( 'placeholder', $this->get_default( 'after' ) );

		return $text;
	}

	public function create_view() {
		$setting = $this->get_before_element();

		$for = $setting->get_id();

		$before = new View( [
			'label'       => __( 'Before', 'codepress-admin-columns' ),
			'description' => __( 'This text will appear before the column value.', 'codepress-admin-columns' ),
			'setting'     => $setting,
			'for'         => $for,
		] );

		$setting = $this->get_after_element();

		$after = new View( [
			'label'       => __( 'After', 'codepress-admin-columns' ),
			'description' => __( 'This text will appear after the column value.', 'codepress-admin-columns' ),
			'setting'     => $setting,
			'for'         => $setting->get_id(),
		] );

		return new View( [
			'label'    => __( 'Display Options', 'codepress-admin-columns' ),
			'sections' => [ $before, $after ],
			'for'      => $for,
		] );
	}

	/**
	 * @return string
	 */
	public function get_before() {
		return $this->before;
	}

	/**
	 * @param $before
	 *
	 * @return bool
	 */
	public function set_before( $before ) {
		$this->before = $before;

		return true;
	}

	/**
	 * @return string
	 */
	public function get_after() {
		return $this->after;
	}

	/**
	 * @param $after
	 *
	 * @return bool
	 */
	public function set_after( $after ) {
		$this->after = $after;

		return true;
	}

}
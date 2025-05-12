<?php
/**
 * Base view class.
 *
 * @package Calotes\Base
 */

namespace Calotes\Base;

/**
 * Base class for all views.
 */
class View extends Component {


	/**
	 * Holds the blocks used in the view.
	 *
	 * @var array
	 */
	public $blocks = array();

	/**
	 * Holds parameters that can be passed to the view.
	 *
	 * @var array
	 */
	public $params = array();

	/**
	 * The template file in which this view should be rendered.
	 *
	 * @var null
	 */
	public $layout = null;

	/**
	 * The file contains content of this view, relative path.
	 *
	 * @var null
	 */
	public $view_file = null;

	/**
	 * The folder contains view files, absolute path.
	 *
	 * @var null
	 */
	private $base_path = null;

	/**
	 * Constructor to set the base path of the view.
	 *
	 * @param  mixed $base_path  The base path of the view.
	 */
	public function __construct( $base_path ) {
		$this->base_path = $base_path;
	}

	/**
	 * Render a view file. This will be used to render a whole page.
	 * If a layout is defined, then we will render layout + view.
	 *
	 * @param  string $view  The name of the view file to render.
	 * @param  array  $params  An optional array of parameters to pass to the view file.
	 *
	 * @return string
	 */
	public function render( $view, $params = array() ) {
		$view_file = $this->base_path . DIRECTORY_SEPARATOR . $view . '.php';
		if ( is_file( $view_file ) ) {
			$content = $this->render_php_file( $view_file, $params );

			return $content;
		}

		return '';
	}

	/**
	 * Renders a PHP file and returns its output.
	 *
	 * @param  string $file  The path to the PHP file to render.
	 * @param  array  $params  An optional array of parameters to pass to the PHP file.
	 *
	 * @return string The output of the rendered PHP file.
	 */
	private function render_php_file( $file, $params = array() ) {
		ob_start();
		ob_implicit_flush( false );
		foreach ( $params as $key => $value ) {
			$$key = $value;
		}
		require $file;

		return ob_get_clean();
	}
}
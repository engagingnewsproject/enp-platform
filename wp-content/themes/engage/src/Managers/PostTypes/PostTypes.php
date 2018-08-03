<?php
/*
 * Registers post types
 */
namespace Engage\Managers\PostTypes;

class PostTypes {
	protected $postTypes = [];
	public function __construct($postTypes) {
		$this->postTypes = $postTypes;
	}

	public function run() {
		foreach($this->postTypes as $postType) {
			$className = '\Engage\Managers\PostTypes\\'.$postType;
			$register = new $className;
			$register->run();
		}
	}
}
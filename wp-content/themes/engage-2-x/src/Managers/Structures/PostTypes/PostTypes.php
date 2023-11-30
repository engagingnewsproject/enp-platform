<?php

/*
 * Registers post types
 */
namespace Engage\Managers\Structures\PostTypes;

class PostTypes {
		// An array to store the names of the post types to be registered
		protected $postTypes = [];

		// Constructor to initialize the postTypes array with the provided values
		public function __construct($postTypes) {
				$this->postTypes = $postTypes;
		}

		// Method to iterate through the postTypes array and register each post type
		public function run() {
				foreach ($this->postTypes as $postType) {
						// Constructing the fully qualified class name for the post type
						$className = '\Engage\Managers\Structures\PostTypes\\' . $postType;

						// Creating an instance of the post type class
						$register = new $className;

						// Calling the run method on the post type instance to perform registration
						$register->run();
				}
		}
}

<?php
/*
 * Registers taxonomies
 */
namespace Engage\Managers\Structures\Taxonomies;

class Taxonomies {
		// An array to store the taxonomies that need to be registered
		protected $taxonomies = [];

		/**
		 * Constructor to initialize the class with a list of taxonomies.
		 *
		 * @param array $taxonomies An array of taxonomy method names to be registered.
		 */
		public function __construct($taxonomies) {
				$this->taxonomies = $taxonomies;
		}

		/**
		 * Method to run the registration process for each taxonomy.
		 *
		 * Iterates over the list of taxonomies provided during class instantiation
		 * and calls each taxonomy's registration method.
		 */
		public function run() {
				foreach($this->taxonomies as $taxonomy) {
						$this->$taxonomy();
				}
		}
}

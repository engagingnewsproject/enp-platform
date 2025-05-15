<?php

namespace AC\ListScreenRepository;

use InvalidArgumentException;

final class Rules {

	const MATCH_ALL = 'all';
	const MATCH_ANY = 'any';

	/**
	 * @var string
	 */
	private $match_decision;

	/**
	 * @var Rule[]
	 */
	private $rules = [];

	public function __construct( $match_decision = null ) {
		if ( null === $match_decision ) {
			$match_decision = self::MATCH_ANY;
		}

		$this->match_decision = $match_decision;

		$this->validate();
	}

	private function validate() {
		$match_decisions = [ self::MATCH_ANY, self::MATCH_ALL ];

		if ( ! in_array( $this->match_decision, $match_decisions, true ) ) {
			throw new InvalidArgumentException( 'Invalid match decision.' );
		}
	}

	/**
	 * @param Rule $rule
	 *
	 * @return $this
	 */
	public function add_rule( Rule $rule ) {
		$this->rules[] = $rule;

		return $this;
	}

	public function match( array $args ) {
		$matches = 0;

		foreach ( $this->rules as $rule ) {
			if ( $rule->match( $args ) ) {
				$matches++;
			}
		}

		$has_as_least_one_match = $matches > 0;

		switch ( $this->match_decision ) {
			case self::MATCH_ANY:
				return $has_as_least_one_match;
			case self::MATCH_ALL:
				return $has_as_least_one_match && $matches === count( $this->rules );
		}

		return false;
	}

}
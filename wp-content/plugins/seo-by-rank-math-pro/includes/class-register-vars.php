<?php
/**
 * Register replacement vars.
 *
 * @since      1.0
 * @package    RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Register replacement vars class.
 *
 * @codeCoverageIgnore
 */
class Register_Vars {
	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'rank_math/vars/register_extra_replacements', 'register_replacements' );
	}

	/**
	 * Registers variable replacements for Rank Math Pro.
	 */
	public function register_replacements() {
		rank_math_register_var_replacement(
			'randomword',
			[
				'name'        => esc_html__( 'Random Word', 'rank-math' ),
				'description' => esc_html__( 'Persistent random word chosen from a list', 'rank-math' ),
				'variable'    => 'randomword(word1|word2|word3)',
				'example'     => ' ',
			],
			[ $this, 'get_randomword' ]
		);
	}

	/**
	 * Get random word from list of words using the post ID for the seed.
	 *
	 * @param  string $list Words list in spintax-like format.
	 * @return string       Random word.
	 */
	public function get_randomword( $list = null ) {
		$words = array_map( 'trim', explode( '|', $list ) );
		$max = count( $words );
		if ( ! $max ) {
			return '';
		} elseif ( 1 === $max ) {
			return $words[0];
		}

		$queried_id = (int) get_queried_object_id();
		$hash       = (int) crc32( serialize( $words ) . $queried_id );
		mt_srand( $hash );
		$rand = mt_rand( 0, $max - 1 );

		return $words[ $rand ];
	}

}
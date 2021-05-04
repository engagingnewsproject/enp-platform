<?php

namespace WP_Defender;

use Calotes\Base\Model;
use Calotes\DB\Mapper;
use Calotes\Helper\Array_Cache;

class DB extends Model {
	public function __construct() {
		$this->parse_annotations();
	}

	/**
	 * Save the current instance
	 * @return int
	 */
	public function save() {
		return self::get_orm()->save( $this );
	}

	/**
	 * @return Mapper
	 */
	protected static function get_orm() {
		$orm = wd_di()->get( Mapper::class );

		return $orm;
	}
}
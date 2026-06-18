<?php

declare(strict_types=1);

namespace ACP\Search\Storage\Table;

use AC\Storage\Table;
use ACP\Search\SegmentSchema;

final class Segment extends Table
{

    public function get_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'ac_segments';
    }

    public function get_schema(): string
    {
        global $wpdb;

        $collate = $wpdb->get_charset_collate();

        return "
			CREATE TABLE " . $this->get_name() . " (
				`id` bigint(20) unsigned NOT NULL auto_increment,
				`" . SegmentSchema::KEY . "` varchar(36) NOT NULL,
				`" . SegmentSchema::LIST_SCREEN_ID . "` varchar(36) NOT NULL default '',
				`" . SegmentSchema::USER_ID . "` bigint(20),
				`" . SegmentSchema::NAME . "` varchar(255) NOT NULL default '',
				`" . SegmentSchema::URL_PARAMETERS . "` mediumtext,
				`" . SegmentSchema::DATE_CREATED . "` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `" . SegmentSchema::KEY . "` (`" . SegmentSchema::KEY . "`)
			) $collate
		";
    }

}
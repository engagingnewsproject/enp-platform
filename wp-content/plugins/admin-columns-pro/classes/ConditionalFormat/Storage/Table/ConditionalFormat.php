<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Storage\Table;

use AC\Storage\Table;
use ACP\ConditionalFormat\RulesSchema;

final class ConditionalFormat extends Table
{

    public function get_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'ac_conditional_format';
    }

    public function get_schema(): string
    {
        global $wpdb;

        $collate = $wpdb->get_charset_collate();

        return "
			CREATE TABLE " . $this->get_name() . " (
				`id` bigint(20) unsigned NOT NULL auto_increment,
				`" . RulesSchema::KEY . "` varchar(36) NOT NULL,
				`" . RulesSchema::LIST_SCREEN_ID . "` varchar(36) NOT NULL default '',
				`" . RulesSchema::USER_ID . "` bigint(20),
				`" . RulesSchema::NAME . "` varchar(255) NOT NULL default '',
				`" . RulesSchema::DATA . "` mediumtext,
				`" . RulesSchema::DATE_MODIFIED . "` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `" . RulesSchema::KEY . "` (`" . RulesSchema::KEY . "`)
			) $collate
		";
    }

}
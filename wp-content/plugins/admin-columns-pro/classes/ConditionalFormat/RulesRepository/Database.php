<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\RulesRepository;

use AC\Type\ListScreenId;
use ACP\ConditionalFormat\Decoder;
use ACP\ConditionalFormat\Encoder;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\RulesCollection;
use ACP\ConditionalFormat\RulesRepository;
use ACP\ConditionalFormat\RulesSchema;
use ACP\ConditionalFormat\Storage\Table\ConditionalFormat;
use ACP\ConditionalFormat\Type\Key;
use ACP\Exception\FailedToSaveConditionalFormattingException;
use InvalidArgumentException;

final class Database extends RulesRepository
{

    private ConditionalFormat $table;

    private Encoder $encoder;

    private Decoder $decoder;

    public function __construct(ConditionalFormat $table, Encoder $encoder, Decoder $decoder)
    {
        $this->table = $table;
        $this->encoder = $encoder;
        $this->decoder = $decoder;
    }

    // Retrieval

    public function find_all_shared(ListScreenId $list_screen_id): RulesCollection
    {
        return $this->fetch_results(
            $list_screen_id,
            null,
            0
        );
    }

    public function find_all_personal(ListScreenId $list_screen_id, int $user_id): RulesCollection
    {
        return $this->fetch_results(
            $list_screen_id,
            null,
            $user_id
        );
    }

    // Save

    public function save(Rules $rules): void
    {
        global $wpdb;

        $table = $this->table->get_name();
        $key = $rules->get_key();

        $data = $this->encoder->encode_rules($rules);
        $data[RulesSchema::DATA] = serialize($data[RulesSchema::DATA]);

        $format = ['%s', '%s', '%d', '%s', '%s'];
        $exists = $this->find($rules->get_list_id(), $key) !== null;

        if ($exists) {
            $result = $wpdb->update(
                $table,
                $data,
                [RulesSchema::KEY => (string)$key],
                $format,
                ['%s']
            );
        } else {
            $result = $wpdb->insert(
                $table,
                $data,
                $format
            );
        }

        if ($result === false) {
            throw new FailedToSaveConditionalFormattingException();
        }
    }

    // Delete

    public function delete(ListScreenId $list_screen_id, Key $key): void
    {
        $this->delete_rules($list_screen_id, null, $key);
    }

    public function delete_all(ListScreenId $list_screen_id): void
    {
        $this->delete_rules($list_screen_id);
    }

    public function delete_all_personal(ListScreenId $list_screen_id, int $user_id): void
    {
        if ($user_id < 1) {
            throw new InvalidArgumentException('user_id must be a positive number.');
        }

        $this->delete_rules($list_screen_id, $user_id);
    }

    public function delete_all_shared(ListScreenId $list_screen_id): void
    {
        $this->delete_rules($list_screen_id, 0);
    }

    private function delete_rules(ListScreenId $list_screen_id, ?int $user_id = null, ?Key $key = null): void
    {
        global $wpdb;

        $where = [
            RulesSchema::LIST_SCREEN_ID => (string)$list_screen_id,
        ];

        $where_format = [
            '%s',
        ];

        if (null !== $user_id) {
            $where[RulesSchema::USER_ID] = $user_id;
            $where_format[] = '%d';
        }

        if ($key) {
            $where[RulesSchema::KEY] = (string)$key;
            $where_format[] = '%s';
        }

        $wpdb->delete(
            $this->table->get_name(),
            $where,
            $where_format
        );
    }

    // Helpers

    protected function fetch_results(
        ListScreenId $list_screen_id,
        ?Key $key = null,
        ?int $user_id = null
    ): RulesCollection {
        global $wpdb;

        $sql = "
			SELECT * 
			FROM " . $this->table->get_name() . "
			WHERE 1=1
		";

        $sql .= $wpdb->prepare("\nAND `" . RulesSchema::LIST_SCREEN_ID . "` = %s", (string)$list_screen_id);

        if ($user_id !== null) {
            $sql .= $wpdb->prepare("\nAND `" . RulesSchema::USER_ID . "` = %d", $user_id);
        }

        if ($key !== null) {
            $sql .= $wpdb->prepare("\nAND `" . RulesSchema::KEY . "` = %s", (string)$key);
        }

        $sql .= "\nORDER BY `" . RulesSchema::NAME . "`";

        $collection = new RulesCollection();

        // Prepare data for decoder
        foreach ($wpdb->get_results($sql, ARRAY_A) as $row) {
            $row[RulesSchema::DATA] = unserialize($row[RulesSchema::DATA], [
                'allowed_classes' => false,
            ]);

            $collection->add(
                $this->decoder->decode($row)
            );
        }

        return $collection;
    }

}
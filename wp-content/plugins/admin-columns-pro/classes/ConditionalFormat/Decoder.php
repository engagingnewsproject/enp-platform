<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\DateFormats;
use AC\Type\ListScreenId;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\Type\Format;
use ACP\ConditionalFormat\Type\Key;
use ACP\ConditionalFormat\Type\Rule;
use DateTime;

final class Decoder
{

    /**
     * Expects an encoded Rules object
     */
    public function decode(array $data): Rules
    {
        return new Rules(
            new Key($data[RulesSchema::KEY]),
            $data[RulesSchema::NAME] ?? '',
            $this->decode_data($data[RulesSchema::DATA]),
            new ListScreenId($data[RulesSchema::LIST_SCREEN_ID]),
            $data[RulesSchema::USER_ID] ? (int)$data[RulesSchema::USER_ID] : null,
            DateTime::createFromFormat(DateFormats::DATE_MYSQL_TIME, (string)$data[RulesSchema::DATE_MODIFIED])
        );
    }

    private function decode_data(array $encoded_rules): RuleCollection
    {
        $collection = new RuleCollection();

        foreach ($encoded_rules as $encoded_rule) {
            $collection->add(
                new Rule(
                    $encoded_rule[RuleSchema::COLUMN_NAME] ?? '',
                    new Format($encoded_rule[RuleSchema::FORMAT]),
                    $encoded_rule[RuleSchema::OPERATOR],
                    $encoded_rule[RuleSchema::FACT] ?? null
                )
            );
        }

        return $collection;
    }

}
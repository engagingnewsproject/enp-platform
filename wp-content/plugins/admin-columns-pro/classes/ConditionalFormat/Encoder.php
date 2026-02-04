<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\DateFormats;
use ACP\ConditionalFormat\Entity\Rules;

final class Encoder
{

    public function encode(RulesCollection $collection): array
    {
        $encoded = [];

        foreach ($collection as $rules) {
            $encoded[] = $this->encode_rules($rules);
        }

        return $encoded;
    }

    public function encode_rules(Rules $rules): array
    {
        return [
            RulesSchema::KEY            => (string)$rules->get_key(),
            RulesSchema::LIST_SCREEN_ID => (string)$rules->get_list_id(),
            RulesSchema::USER_ID        => $rules->has_user_id() ? $rules->get_user_id() : 0,
            RulesSchema::DATA           => $this->encode_data($rules->get_rule_collection()),
            RulesSchema::DATE_MODIFIED  => $rules->get_modified()->format(DateFormats::DATE_MYSQL_TIME),
        ];
    }

    private function encode_data(RuleCollection $collection): array
    {
        $encoded = [];

        foreach ($collection as $rule) {
            $encoded[] = [
                RuleSchema::COLUMN_NAME => $rule->get_column_name(),
                RuleSchema::FORMAT      => (string)$rule->get_format(),
                RuleSchema::OPERATOR    => $rule->get_operator(),
                RuleSchema::FACT        => $rule->has_fact() ? $rule->get_fact() : null,
            ];
        }

        return $encoded;
    }

}
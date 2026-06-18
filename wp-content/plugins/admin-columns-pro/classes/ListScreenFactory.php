<?php

declare(strict_types=1);

namespace ACP;

use AC\ListScreen;
use AC\Type\ListScreenIdGenerator;
use ACP\ConditionalFormat\RulesCollection;
use ACP\ConditionalFormat\Type\KeyGenerator;
use ACP\Search\SegmentCollection;
use ACP\Search\Type\SegmentKeyGenerator;

class ListScreenFactory
{

    private ListScreenIdGenerator $list_screen_id_generator;

    private SegmentKeyGenerator $segment_key_generator;

    private KeyGenerator $rules_key_generator;

    public function __construct(
        ListScreenIdGenerator $list_screen_id_generator,
        SegmentKeyGenerator $segment_key_generator,
        KeyGenerator $rules_key_generator
    ) {
        $this->list_screen_id_generator = $list_screen_id_generator;
        $this->segment_key_generator = $segment_key_generator;
        $this->rules_key_generator = $rules_key_generator;
    }

    /**
     * Duplicates list screen and makes sure all identities/ keys are (re)generated.
     */
    public function duplicate(ListScreen $list_screen): ListScreen
    {
        $list_id = $this->list_screen_id_generator->generate();

        $preferences = $list_screen->get_preferences();

        $segments_source = $list_screen->get_segments();
        $segments = new SegmentCollection();

        foreach ($segments_source as $segment_source) {
            $segment = $segment_source->with_list_id_and_key($list_id, $this->segment_key_generator->generate());

            $segments->add($segment);

            // Update pre-applied filter
            $filter = $preferences[ListScreenPreferences::FILTER_SEGMENT] ?? null;

            if ((string)$segment_source->get_key() === $filter) {
                $preferences[ListScreenPreferences::FILTER_SEGMENT] = (string)$segment->get_key();
            }
        }

        $rules_source = $list_screen->get_conditional_format();
        $rules = new RulesCollection();

        foreach ($rules_source as $rule_source) {
            $rules->add(
                $rule_source->with_list_id_and_key(
                    $list_id,
                    $this->rules_key_generator->generate()
                )
            );
        }

        return new ListScreen(
            $list_id,
            $list_screen->get_title(),
            $list_screen->get_table_screen(),
            $list_screen->get_columns(),
            $preferences,
            $list_screen->get_status(),
            null,
            $segments,
            $rules
        );
    }

}
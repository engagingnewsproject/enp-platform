<?php

declare(strict_types=1);

namespace ACP\Storage;

use AC;
use AC\Plugin\Version;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\RulesCollection;
use ACP\Search;
use ACP\Search\SegmentCollection;

final class Encoder extends AC\Storage\Encoder\BaseEncoder
{

    public const CONDITIONAL_FORMAT = 'conditional_format';
    public const SEGMENTS = 'segments';

    private ?SegmentCollection $segments = null;

    private ?RulesCollection $conditional_format = null;

    private Search\Encoder $segments_encoder;

    private ConditionalFormat\Encoder $conditional_format_encoder;

    public function __construct(
        Version $version,
        Search\Encoder $segments_encoder,
        ConditionalFormat\Encoder $conditional_format_encoder
    ) {
        parent::__construct($version);

        $this->segments_encoder = $segments_encoder;
        $this->conditional_format_encoder = $conditional_format_encoder;
    }

    public function set_segments(SegmentCollection $segments): self
    {
        $this->segments = $segments;

        return $this;
    }

    public function set_conditional_format(RulesCollection $rules): self
    {
        $this->conditional_format = $rules;

        return $this;
    }

    public function encode(): array
    {
        $encoded_data = parent::encode();

        $encoded_data[self::SEGMENTS] = $this->segments_encoder->encode(
            $this->segments ?? new SegmentCollection()
        );

        $encoded_data[self::CONDITIONAL_FORMAT] = $this->conditional_format_encoder->encode(
            $this->conditional_format ?? new RulesCollection()
        );

        return $encoded_data;
    }

}
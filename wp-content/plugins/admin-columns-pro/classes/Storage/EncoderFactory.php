<?php

declare(strict_types=1);

namespace ACP\Storage;

use AC;
use AC\Plugin\Version;
use ACP\ConditionalFormat;
use ACP\Search;

final class EncoderFactory extends AC\Storage\EncoderFactory\BaseEncoderFactory
{

    private Search\Encoder $segments_encoder;

    private ConditionalFormat\Encoder $conditional_format_encoder;

    public function __construct(
        Version $version,
        Search\Encoder $segments_encoder,
        ConditionalFormat\Encoder $conditional_format_encoder
    ) {
        parent::__construct($version);

        $this->conditional_format_encoder = $conditional_format_encoder;
        $this->segments_encoder = $segments_encoder;
    }

    public function create(): Encoder
    {
        return new Encoder(
            $this->version,
            $this->segments_encoder,
            $this->conditional_format_encoder
        );
    }

}
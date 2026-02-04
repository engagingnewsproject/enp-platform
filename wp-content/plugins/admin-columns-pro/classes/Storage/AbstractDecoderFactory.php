<?php

declare(strict_types=1);

namespace ACP\Storage;

use ACP\Exception\DecoderNotFoundException;

final class AbstractDecoderFactory
{

    /**
     * @var DecoderFactory[]
     */
    private array $factories = [];

    public function __construct(array $factories)
    {
        foreach ($factories as $factory) {
            $this->add($factory);
        }
    }

    private function add(DecoderFactory $factory): void
    {
        $this->factories[] = $factory;
    }

    public function supports(array $encoded_data): bool
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($encoded_data)) {
                return true;
            }
        }

        return false;
    }

    public function create(array $encoded_data): Decoder
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($encoded_data)) {
                return $factory->create($encoded_data);
            }
        }

        throw new DecoderNotFoundException($encoded_data);
    }

}
<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

interface GlossaryOptions
{

    public function has_glossary_options(): bool;

    public function get_glossary_options(): array;

}
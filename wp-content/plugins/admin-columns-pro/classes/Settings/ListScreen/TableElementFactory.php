<?php

declare(strict_types=1);

namespace ACP\Settings\ListScreen;

interface TableElementFactory
{

    public function create(): TableElement;

}
<?php

namespace ACP\Export;

interface EscapeData
{

    public function escape(string $data): string;

}
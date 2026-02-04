<?php

namespace ACP\Editing\View;

trait PlaceholderTrait
{

    public function set_placeholder(string $placeholder): self
    {
        return $this->set('placeholder', $placeholder);
    }

}
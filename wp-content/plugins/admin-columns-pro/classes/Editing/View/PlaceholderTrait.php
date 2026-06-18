<?php

namespace ACP\Editing\View;

trait PlaceholderTrait
{

    /**
     * @return static
     */
    public function set_placeholder(string $placeholder): self
    {
        return $this->set('placeholder', $placeholder);
    }

}
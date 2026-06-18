<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

class AjaxSelect extends View
{

    use AjaxTrait;
    use MethodTrait;
    use MultipleTrait;
    use TagsTrait;

    public function __construct()
    {
        parent::__construct('select2_dropdown');

        $this->set_ajax_populate(true);
        $this->set_store_values(false);
    }

    public function set_tags(bool $enable_tags): AjaxSelect
    {
        $this->set('tags', $enable_tags);

        return $this;
    }

    public function set_store_values(bool $store_values): AjaxSelect
    {
        $this->set('store_values', $store_values);

        return $this;
    }

}
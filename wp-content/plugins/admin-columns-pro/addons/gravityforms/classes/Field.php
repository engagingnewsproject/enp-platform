<?php

namespace ACA\GravityForms;

interface Field
{

    public function get_form_id(): int;

    public function get_id(): string;

    public function is_required(): bool;

}
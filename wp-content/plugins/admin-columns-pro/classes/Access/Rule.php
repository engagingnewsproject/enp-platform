<?php

namespace ACP\Access;

interface Rule
{

    public function modify(Permissions $permissions): Permissions;

}
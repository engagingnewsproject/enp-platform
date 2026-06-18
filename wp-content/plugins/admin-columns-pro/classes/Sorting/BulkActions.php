<?php

namespace ACP\Sorting;

use ACP\Table\HideElement;

class BulkActions implements HideElement
{

    public function hide(): void
    {
        add_action('ac/admin_head', [$this, 'render']);
    }

    public function render()
    {
        ?>
		<style>
			<?= sprintf( '%s { display: none; }', '.tablenav div.actions.bulkactions' ); ?>
		</style>
        <?php
    }

}
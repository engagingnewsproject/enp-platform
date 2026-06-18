<?php

/**
 * @global array $items
 */
$items = $this->items;
?>
<table class="ac-table-items -clean -subscriptions">
	<thead>
	<tr>
		<th class="col-subscription"><?= __('Subscription', 'codepress-admin-columns') ?></th>
		<th class="col-status"><?= __('Status', 'codepress-admin-columns') ?></th>
		<th class="col-product"><?= __('Product', 'codepress-admin-columns') ?></th>
		<th class="col-total"><?= __('Total', 'codepress-admin-columns') ?></th>
	</tr>
	</thead>
	<tbody>
    <?php
    foreach ($this->items as $item) : ?>
		<tr>
			<td class="col-subscription">
                <?= $item['subscription'] ?>
			</td>
			<td class="col-status">
                <?= $item['status'] ?>
			</td>
			<td class="col-product">
                <?= $item['product'] ?>
			</td>
			<td class="col-total">
                <?= $item['total'] ?>
			</td>
		</tr>
    <?php
    endforeach; ?>
	</tbody>
</table>
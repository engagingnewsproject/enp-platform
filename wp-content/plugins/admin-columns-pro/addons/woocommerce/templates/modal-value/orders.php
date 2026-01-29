<table class="ac-table-items -clean -orders">
	<thead>
	<tr>
		<th class="col-orders"><?= __('Order', 'codepress-admin-columns') ?></th>
		<th class="col-date"><?= __('Date', 'codepress-admin-columns') ?></th>
		<th class="col-products"><?= __('Products', 'codepress-admin-columns') ?></th>
		<th class="col-quantity"><?= __('Quantity', 'codepress-admin-columns') ?></th>
		<th class="col-total"><?= __('Total', 'codepress-admin-columns') ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ($this->items as $item) : ?>

		<tr>
			<td class="col-order">
				<?= $item['order'] ?>
			</td>
			<td class="col-date">
				<?= $item['date']; ?>
			</td>
			<td class="col-products">
				<?= $item['products']; ?>
			</td>
			<td class="col-quantity">
				<?= $item['quantity']; ?>
			</td>
			<td class="col-total">
				<?= $item['total']; ?>
			</td>
		</tr>

	<?php
	endforeach; ?>
	</tbody>
</table>
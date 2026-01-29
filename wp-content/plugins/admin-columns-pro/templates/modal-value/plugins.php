<?php

$title = sprintf(
    _n(
        '%d Plugin',
        '%d Plugins',
        (int)$this->amount,
        'codepress-admin-columns'
    ),
    (int)$this->amount
);
?>

<h3><?= esc_html($title) ?></h3>

<table class="ac-table-items -clean -plugins">
	<thead>
	<tr>
		<th class="col-name"><?= __('Plugin', 'codepress-admin-columns') ?></th>
		<th class="col-network-activated"><?= __('Status', 'codepress-admin-columns') ?></th>
		<th class="col-version"><?= __('Version', 'codepress-admin-columns') ?></th>
		<th class="col-update"><?= __('Update Available', 'codepress-admin-columns') ?></th>
	</tr>
	</thead>
	<tbody>
    <?php
    foreach ($this->items as $item) : ?>
		<tr>
			<td class="col-name">
                <?= esc_html($item['name']) ?>
			</td>
			<td class="col-status">
                <?= esc_html($item['status']) ?>
			</td>
			<td class="col-version">
                <?= esc_html($item['version']) ?>
			</td>
			<td class="col-update">
                <?= esc_html($item['update']) ?>
			</td>
		</tr>
    <?php
    endforeach; ?>
	</tbody>
</table>
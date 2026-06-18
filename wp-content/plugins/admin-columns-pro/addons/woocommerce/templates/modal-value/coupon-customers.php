<table class="ac-table-items -clean -customers">
	<thead>
	<tr>
		<th class="col-user">
            <?= __('Customer', 'codepress-admin-columns') ?>
		</th>
	</tr>
	</thead>
	<tbody>
    <?php
    foreach ($this->users as $item) : ?>
		<tr>
			<td class="col-name">
                <?= $item ?>
			</td>
		</tr>
    <?php
    endforeach; ?>
	</tbody>
</table>
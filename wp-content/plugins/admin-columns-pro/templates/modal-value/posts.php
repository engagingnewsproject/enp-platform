<?php
/**
 * @var string $title
 * @var int    $total
 * @var string $image
 * @var array  $posts
 * @var array  $post_types
 */

$title = $this->title;
$shown = count($this->posts);
?>

<?php if ($this->image) : ?>
	<div class="modal-attachment-preview"><?= $this->image ?></div>
<?php endif; ?>
	<h3><?= $title ?></h3>
<?php if ($shown < $this->total) : ?>
	<p class="description"><?= sprintf(
		__('Showing the first %1$s of %2$s posts.', 'codepress-admin-columns'),
		number_format_i18n($shown),
		number_format_i18n($this->total)
	) ?></p>
<?php endif; ?>
	<table class="ac-table-items -user-posts">
		<tbody>
        <?php
        foreach ($this->posts as $post) : ?>
			<tr>
				<td class="col-id">
					#<?= $post['id'] ?>
				</td>
				<td class="col-title">
					<span><?= $post['post_title'] ?: '-' ?></span>
				</td>
				<td class="col-status">
					<span><?= $post['post_status'] ?: '-' ?></span>
				</td>
				<td class="col-date">
                    <?= $post['post_date'] ?>
				</td>
				<td class="col-post-type">
					<span class="ac-badge"><?= $post['post_type'] ?></span>
				</td>
			</tr>
        <?php
        endforeach; ?>
		</tbody>
	</table>
<?php
if ($this->post_types) : ?>
	<h3><?= __('Total items by post type', 'codepress-admin-columns') ?></h3>
    <?php
    foreach ($this->post_types as $post_type) : ?>
		<a target="_blank" href="<?= esc_url($post_type['link']) ?>" class="ac-badge-post-count">
			<span class="-label"><?= $post_type['post_type'] ?></span>
			<span class="-count"><?= $post_type['count'] ?></span>
		</a>
    <?php
    endforeach;
endif;
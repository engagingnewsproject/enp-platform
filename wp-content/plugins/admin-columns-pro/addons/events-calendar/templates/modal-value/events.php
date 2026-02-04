<?php
/**
 * @var string $title
 * @var array  $posts
 * @var array  $post_types
 */

$items = $this->items;
?>
<table class="ac-table-items  -user-posts">
	<tbody>
    <?php
    foreach ($this->items as $post) : ?>
		<tr>
			<td class="col-id">
				#<?= $post->ID ?>
			</td>
			<td class="col-title">
				<span><?= $post->post_title ?: '-' ?></span>
			</td>
			<td class="col-date">
                <?php
                $startDate = get_post_meta($post->ID, '_EventStartDate', true);
                $endDate = get_post_meta($post->ID, '_EventEndDate', true);

                if ($startDate) {
                    $formattedStartDate = date_i18n(get_option('date_format'), strtotime($startDate));
                    echo $formattedStartDate;
                }

                if ($endDate) {
                    $formattedEndDate = date_i18n(get_option('date_format'), strtotime($endDate));
                    if ($startDate) {
                        echo ' - ';
                    }
                    echo $formattedEndDate;
                }
                ?>

			</td>

		</tr>
    <?php
    endforeach; ?>
	</tbody>
</table>
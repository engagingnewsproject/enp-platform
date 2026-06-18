<?php
/**
 * @var string $title
 * @var array  $posts
 * @var array  $post_types
 */

use AC\Helper\Date;

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
                    echo wp_date(Date::create()->get_date_format(), strtotime($startDate), new DateTimeZone('UTC'));
                }

                if ($endDate) {
                    if ($startDate) {
                        echo ' - ';
                    }

                    echo wp_date(Date::create()->get_date_format(), strtotime($endDate), new DateTimeZone('UTC'));
                }
                ?>

			</td>

		</tr>
    <?php
    endforeach; ?>
	</tbody>
</table>
<?php
/**
 * @var WP_Post $series
 * @var array   $items
 */

$series = $this->series;
$items = $this->items;
?>

<h3><?= esc_html($series->post_title) ?></h3>

<?php if (empty($items)) : ?>
    <p><?= __('No events found in this series', 'codepress-admin-columns') ?></p>
<?php else : ?>
    <table class="ac-table-items -clean -plugins">
        <thead>
            <tr>
                <th class="col-id"><?= __('ID', 'codepress-admin-columns') ?></th>
                <th class="col-title"><?= __('Event Title', 'codepress-admin-columns') ?></th>
                <th class="col-date"><?= __('Event Date', 'codepress-admin-columns') ?></th>
                <th class="col-actions"><?= __('Actions', 'codepress-admin-columns') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $event) : ?>
                <tr>
                    <td class="col-id">
                        #<?= esc_html($event->ID) ?>
                    </td>
                    <td class="col-title">
                        <span><?= esc_html($event->post_title ?: '-') ?></span>
                    </td>
                    <td class="col-date">
                        <?php
                        $startDate = get_post_meta($event->ID, '_EventStartDate', true);
                        $endDate = get_post_meta($event->ID, '_EventEndDate', true);

                        if ($startDate) {
                            $formattedStartDate = date_i18n(get_option('date_format'), strtotime($startDate));
                            echo esc_html($formattedStartDate);
                        }

                        if ($endDate) {
                            $formattedEndDate = date_i18n(get_option('date_format'), strtotime($endDate));
                            if ($startDate) {
                                echo ' - ';
                            }
                            echo esc_html($formattedEndDate);
                        }
                        ?>
                    </td>
                    <td class="col-actions">
                        <a href="<?= esc_url($event->admin_edit_link) ?>" target="_blank">
                            <?= __('Edit', 'codepress-admin-columns') ?>
                        </a>
                        |
                        <a href="<?= esc_url($event->public_view_link) ?>" target="_blank">
                            <?= __('View', 'codepress-admin-columns') ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

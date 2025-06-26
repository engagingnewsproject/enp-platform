<?php
/**
 * Plugin Name: CME Sync Image Alt Text with Media Library
 * Description: Syncs the alt text of images in post content with the current value in the Media Library.
 * Version: 1.0
 * Author: Center for Media Engagement
 * Version:           1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author URI:        https://mediaengagement.org
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       sync-alt-text
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_menu', function() {
    add_management_page(
        'Sync Alt Text',
        'Sync Alt Text',
        'manage_options',
        'sync-alt-text',
        function() {
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            if (isset($_POST['sync_alt_text']) && check_admin_referer('sync_alt_text_action', 'sync_alt_text_nonce')) {
                // Output a JS redirect instead of wp_redirect
                echo "<script>window.location.href='" . add_query_arg([
                    'page' => 'sync-alt-text',
                    'run_sync' => '1',
                    'paged' => 1
                ], admin_url('tools.php')) . "';</script>";
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . add_query_arg([
                    'page' => 'sync-alt-text',
                    'run_sync' => '1',
                    'paged' => 1
                ], admin_url('tools.php')) . '" /></noscript>';
                return;
            }

            $batch_size = 200;
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $updated = 0;
            $total = 0;
            $done = false;
            $show_form = true;

            if ($paged === 1) {
                update_option('cme_sync_alt_text_updated', 0);
            }

            if (isset($_GET['run_sync']) && $_GET['run_sync'] === '1') {
                echo "<div class='notice notice-info'><p>Batch process started (page {$paged})</p></div>";
                $args = [
                    'post_type'      => 'any',
                    'post_status'    => 'any',
                    'posts_per_page' => $batch_size,
                    'paged'          => $paged,
                    'fields'         => 'all',
                ];
                $query = new WP_Query($args);
                $total = $query->found_posts;
                echo "<div class='notice notice-info'><p>Found {$total} posts to process.</p></div>";
                foreach ($query->posts as $post) {
                    $content = $post->post_content;
                    $new_content = preg_replace_callback(
                        '/<img[^>]+class="[^"]*wp-image-(\d+)[^"]*"[^>]*>/i',
                        function($matches) {
                            $img_tag = $matches[0];
                            $attachment_id = $matches[1];
                            $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                            if (!$alt) {
                                $alt = get_the_title($attachment_id);
                            }
                            if (preg_match('/alt="[^"]*"/i', $img_tag)) {
                                $img_tag = preg_replace('/alt="[^"]*"/i', 'alt="' . esc_attr($alt) . '"', $img_tag);
                            } else {
                                $img_tag = preg_replace('/<img/i', '<img alt="' . esc_attr($alt) . '"', $img_tag);
                            }
                            return $img_tag;
                        },
                        $content
                    );
                    if ($new_content !== $content) {
                        wp_update_post([
                            'ID' => $post->ID,
                            'post_content' => $new_content,
                        ]);
                        $updated++;
                    }
                }
                $total_updated = get_option('cme_sync_alt_text_updated', 0) + $updated;
                update_option('cme_sync_alt_text_updated', $total_updated);

                $processed = ($paged - 1) * $batch_size + count($query->posts);
                if ($processed < $total && $query->have_posts()) {
                    // More posts to process, auto-refresh to next batch
                    $next_url = add_query_arg([
                        'page' => 'sync-alt-text',
                        'run_sync' => '1',
                        'paged' => $paged + 1
                    ], admin_url('tools.php'));
                    echo "<div class='notice notice-info'><p>Processed {$processed} of {$total} posts. Updated alt text in {$updated} posts this batch. Continuing...</p></div>";
                    echo "<meta http-equiv='refresh' content='1;url={$next_url}' />";
                    $show_form = false;
                } else {
                    // Done
                    echo "<div class='notice notice-success'><p>Alt text sync complete! Processed {$processed} posts. Updated alt text in {$total_updated} posts total.</p></div>";
                    // Optionally, delete the option after displaying
                    delete_option('cme_sync_alt_text_updated');
                    $done = true;
                }
            }
            ?>
            <div class="wrap">
                <h1>CME Sync Image Alt Text with Media Library</h1>
                <?php if ($show_form): ?>
                <form method="post">
                    <?php wp_nonce_field('sync_alt_text_action', 'sync_alt_text_nonce'); ?>
                    <p>This tool will scan all posts in batches of <strong><?php echo $batch_size; ?></strong> and update the <code>alt</code> attribute of images in post content to match the current value in the Media Library.</p>
                    <p><strong>Backup your database before running!</strong></p>
                    <p><input type="hidden" name="run_sync" value="1" />
                    <input type="submit" name="sync_alt_text" class="button button-primary" value="Sync Alt Text Now"></p>
                </form>
                <?php elseif ($done): ?>
                    <a href="<?php echo admin_url('tools.php?page=sync-alt-text'); ?>" class="button">Back</a>
                <?php endif; ?>
            </div>
            <?php
        }
    );
});

<?php
/**
 * Plugin Name: Sync Image Alt Text with Media Library
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

            $updated = 0;
            if (isset($_POST['sync_alt_text']) && check_admin_referer('sync_alt_text_action', 'sync_alt_text_nonce')) {
                $args = [
                    'post_type'      => 'any',
                    'post_status'    => 'any',
                    'posts_per_page' => -1,
                ];
                $query = new WP_Query($args);

                foreach ($query->posts as $post) {
                    $content = $post->post_content;
                    $new_content = preg_replace_callback(
                        '/<img[^>]+class="[^"]*wp-image-(\d+)[^"]*"[^>]*>/i',
                        function($matches) {
                            $img_tag = $matches[0];
                            $attachment_id = $matches[1];
                            $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                            // Fallback to attachment title if no alt
                            if (!$alt) {
                                $alt = get_the_title($attachment_id);
                            }
                            // Replace or add alt attribute
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
                echo "<div class='notice notice-success'><p>Alt text synced for {$updated} posts.</p></div>";
            }
            ?>
            <div class="wrap">
                <h1>Sync Image Alt Text with Media Library</h1>
                <form method="post">
                    <?php wp_nonce_field('sync_alt_text_action', 'sync_alt_text_nonce'); ?>
                    <p>This tool will scan all posts and update the <code>alt</code> attribute of images in post content to match the current value in the Media Library.</p>
                    <p><strong>Backup your database before running!</strong></p>
                    <p><input type="submit" name="sync_alt_text" class="button button-primary" value="Sync Alt Text Now"></p>
                </form>
            </div>
            <?php
        }
    );
});

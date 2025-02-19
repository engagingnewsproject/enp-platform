<?php
// Check if running via WP-CLI
if (!defined('WP_CLI') || !WP_CLI) {
    die('This script must be run via WP-CLI');
}

/**
 * Verify imported publications data
 */
class PublicationVerifier {
    private $required_fields = [
        'title' => 'post_title',
        'url' => '_publication_url',
        'date' => '_publication_date',
        'year_date' => '_publication_year_date',
        'authors' => '_publication_authors',
        'subtitle' => '_publication_subtitle'
    ];

    public function verify() {
        $args = [
            'post_type' => 'publication',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];

        $publications = get_posts($args);
        
        if (empty($publications)) {
            WP_CLI::warning("No publications found!");
            return;
        }

        WP_CLI::log(sprintf("Found %d publications. Checking data...\n", count($publications)));

        $issues_found = 0;
        
        foreach ($publications as $pub) {
            WP_CLI::log("\nChecking publication: " . $pub->post_title);
            
            $missing_fields = [];
            
            // Check each required field
            foreach ($this->required_fields as $field_name => $meta_key) {
                if ($meta_key === 'post_title') {
                    $value = $pub->post_title;
                } else {
                    $value = get_post_meta($pub->ID, $meta_key, true);
                }
                
                if (empty($value)) {
                    $missing_fields[] = $field_name;
                } else {
                    WP_CLI::log(sprintf("✓ %s: %s", $field_name, substr($value, 0, 50)));
                }
            }
            
            if (!empty($missing_fields)) {
                $issues_found++;
                WP_CLI::warning(sprintf(
                    "Publication ID %d missing fields: %s",
                    $pub->ID,
                    implode(', ', $missing_fields)
                ));
            } else {
                WP_CLI::success("All fields present for ID " . $pub->ID);
            }
        }

        WP_CLI::log("\nVerification complete!");
        if ($issues_found > 0) {
            WP_CLI::warning(sprintf("Found issues in %d publications", $issues_found));
        } else {
            WP_CLI::success("All publications have complete data!");
        }
    }
}

// Register WP-CLI command
if (defined('WP_CLI')) {
    WP_CLI::add_command('verify-publications', function() {
        $verifier = new PublicationVerifier();
        $verifier->verify();
    });
}
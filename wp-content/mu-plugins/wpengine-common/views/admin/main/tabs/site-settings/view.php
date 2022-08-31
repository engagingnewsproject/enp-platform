<?php
/**
 * Admin UI - Site Settings Tab
 * Adds the WP Engine Admin "Site Settings" tab.
 *
 * @package wpengine/common-mu-plugin
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Check user capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

?>

<!-- WordPress settings tab -->
<div class="wpe-common-plugin-container">
	<form method="post" name="file-perms">
		<?php wp_nonce_field( PWP_NAME . '-site-settings-file-perm-reset' ); ?>
		<h2>File Permissions</h2>
		<p>
			This will properly set your <strong>WP file permissions</strong> to defaults that are needed for normal operation. Use this button after uploading files via SFTP.
			<br>
			<a href="https://wpengine.com/support/file-permissions/#Reset_File_Permissions" target="_blank" rel="noopener noreferrer">Learn about File Permissions here.</a>
		</p>

		<div class="wpe-admin-button-controls">
			<input
				type="submit"
				name="file-perms"
				value="Reset file permissions"
				class="wpe-admin-button-primary"
				onclick="return confirm('Please be patient, this sometimes takes a while.');"
			>
		</div>
	</form>
</div>
<div class="wpe-common-plugin-container">
	<h2>Advanced Configuration</h2>
	<p>
		These tools have the potential to cause significant issues for your site.
		<a href="https://my.wpengine.com/support" target="_blank" rel="noopener noreferrer">Contact support if you have any questions.</a>
	</p>
	<form method="post" name="advanced">
		<?php wp_nonce_field( PWP_NAME . '-site-settings-advanced-options' ); ?>
		<details class="wpe-common-settings-panel">
			<summary>
				<h3 class="wpe-common-cta-heading">Random order (<code>ORDER BY RAND()</code>)</h3>
			</summary>
			<div class="wpe-details-content">
				<hr>
				<input
					id="wpe-order-by-rand"
					name="rand_enabled"
					type="checkbox"
					value="1"
					<?php checked( $wpe_common->is_rand_enabled() ); ?>
				>
				<label for="wpe-order-by-rand" class="wpe-checkbox-label">
					<strong>Allow <code>ORDER BY RAND()</code></strong>
				</label>
				<p>
					We disable <code>ORDER BY RAND()</code> orderings in MySQL queries because this is not advised for sites with large databases as this may significantly decrease performance.
					If you enable this, it is recommended that you cache the results for 5 to 15 minutes to reduce the number of large server requests.
				</p>
			</div>
		</details>
		<details class="wpe-common-settings-panel">
			<summary>
				<h3 class="wpe-common-cta-heading"><abbr title="HyperText Markup Language">HTML</abbr> Post-Processing</h3>
			</summary>
			<div class="wpe-details-content">
				<hr>
				<p>
					A mapping of <strong>PHP regular expressions to replacement values</strong> which are executed on all blog HTML after WordPress finishes emitting the entire page.
					The pattern and replacement behavior is in the manner of
					<a href="http://php.net/manual/en/function.preg-replace.php" target="_blank" rel="noopener noreferrer">preg_replace()</a>.
				</p>
				<textarea
					id="regex-html-post-process"
					class="wpe-common-textarea"
					name="post_process_regex"
					rows="5"
					cols="120"
				><?php echo esc_textarea( $wpe_common->get_regex_html_post_process_text() ); // phpcs:ignore ?></textarea>
				<p>
					The following example removes all HTML comments in the first pattern, and causes a favicon (with any filename extension) to be loaded from another domain in the second pattern:
					<ul>
						<li><code>#&lt;!--.*?--&gt;#s =></code></li>
						<li><code>#\bsrc="/(favicon\..*)"# => src="http://mycdn.somewhere.com/$1"</code></li>
					</ul>
					To test regular expressions, check out
					<a href="https://www.regexpal.com/" target="_blank" rel="noopener noreferrer">Regexpal</a>,
					a free online tool.
				</p>
			</div>
		</details>
		<div class="wpe-admin-button-controls">
			<input type="submit" name="advanced" value="Save" class="wpe-admin-button-primary">
		</div>
	</form>
</div>

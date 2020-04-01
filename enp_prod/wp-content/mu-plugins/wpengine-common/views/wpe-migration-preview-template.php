<?php
/**
 * WPE Migration Preview Template
 *
 * @package wpengine/common-mu-plugin
 */

// Set up and sanitize REQUEST data.
$request_domain = '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $_REQUEST['domain'] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$request_domain = wp_strip_all_tags( wp_unslash( $_REQUEST['domain'] ) );
}
$wpe_domain = PWP_NAME . '.wpengine.com';

// Javascript location.
$rewrite_script = WPMU_PLUGIN_URL . '/wpengine-common/js/wpe-rewrite.js';

// Data array for script.
$data = array(
	'domain'      => preg_quote( $request_domain, '/' ),
	'wpeDomain'   => $wpe_domain,
	'replacement' => trailingslashit( "http://$wpe_domain" ),
);

// Determine whether we're doing any rewriting.
$doing_rewrite = ( '' !== $request_domain && $request_domain !== $wpe_domain );

?>
<html>
	<head>

<?php if ( $doing_rewrite ) : ?>

		<script>
			function inject() {
				var iFrameHead = window.frames[0].document.getElementsByTagName( "head" )[0];

				var scriptdata = document.createElement( 'script' );
				scriptdata.type = 'text/javascript';
				scriptdata.innerText = 'var WPERewriteData = <?php echo wp_json_encode( $data ); ?>;';
				iFrameHead.appendChild( scriptdata );

				var myscript = document.createElement( 'script' );
				myscript.type = 'text/javascript';
				myscript.src = '<?php echo esc_html( $rewrite_script ); ?>';
				iFrameHead.appendChild( myscript );
			}
		</script>

<?php endif; ?>

		<style>
			body {
				margin: 0;
				text-align: center;
			}
			.header {
				background-color: #eb6126;
				border-radius: 0 0 12px 12px;
				color: #f0f0f0;
				font-family: 'Open Sans', Helvetica, sans-serif;
				left: 50%;
				margin-left: -100px;
				padding: 4px 0;
				position: fixed;
				width: 200px;
				z-index: 123;
			}
			iframe {
				border: 0;
			}
		</style>
	</head>
	<body>
		<div class="header">
			<p>WP Engine Preview<?php echo $doing_rewrite ? '' : ' - No Rewriting'; ?></p>
		</div>
		<iframe width="100%" height="100%" src="/" 
		<?php
		if ( $doing_rewrite ) :
			?>
			onLoad='inject()'<?php endif; ?>>
			iframe is not supported
		</iframe>
	</body>
</html>

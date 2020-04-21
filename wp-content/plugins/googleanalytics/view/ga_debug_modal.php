<div id="ga_debug_modal" class="ga-modal" tabindex="-1">
	<div class="ga-modal-dialog">
		<div id="ga_debug_modal_content" class="ga-modal-content">
			<div class="ga-modal-header">
				<span id="ga_close" class="ga-close">&times;</span>
				<h4 class="ga-modal-title"><?php _e( 'Copy and paste this debug info into an email and send to support@sharethis.com' ) ?></h4>
			</div>
			<div class="ga-modal-body">
                <div class="ga-loader-wrapper">
                    <div class="ga-loader"></div>
                </div>
                <div class="ga-debug-form-div">
					<label for="ga_debug_info" class="ga-debug-form-label"><strong><?php _e( 'Debug info' ); ?></strong>:</label>
					<textarea id="ga_debug_info" class="ga-debug-form-field" rows="8" cols="50"><?php echo $debug_info ?></textarea>
				</div>
			</div>
			<div class="ga-modal-footer">
				<button type="button" id="copy-debug" class="button"><?php esc_html_e( 'Copy', 'google-analytics' ); ?></button>
				<button id="ga_btn_close" type="button" class="button">Close</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
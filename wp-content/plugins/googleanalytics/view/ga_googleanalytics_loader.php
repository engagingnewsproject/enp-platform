<script>
    jQuery(document).ready(function () {
		jQuery.post('<?php echo esc_attr( $ajaxurl ); ?>', {action: 'googleanalytics_get_script'}, function(response) {
			var s = document.createElement("script");
			s.type = "text/javascript";
			s.innerHTML = response;
			jQuery("head").append(s);
		});
    });
</script>
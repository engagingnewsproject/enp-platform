

	jQuery(document).ready(function($) {

		//Tabs
		$('.nav-tab').on('click', function() {
			$('#settings_last_tab').val($(this).attr('data-tab-index'));
		});

		//Images
		var file_frame;
		var file_frame_field_id;
		file_frame = wp.media.frames.file_frame = wp.media({
			title: texts.select_image,
			button: {
				text: texts.use_this_image
			},
			multiple: false
		});
		file_frame.on("select", function() {
			var image = file_frame.state().get("selection").first().toJSON();
			$("#"+file_frame_field_id).val(image.url);
		});
		//Default
		$('#fb_image_button').on('click', function(event) {
			event.preventDefault();
			file_frame_field_id='fb_image';
			if (file_frame) {
				file_frame.open();
			}
		});
		//Overlay
		$('#fb_image_overlay_button').on('click', function(event) {
			event.preventDefault();
			file_frame_field_id='fb_image_overlay_image';
			if (file_frame) {
				file_frame.open();
			}
		});

		//Default or mShot
		$('#fb_image_use_default').on('change', function(event) {
			if ( $(this).is(':checked') ) {
				$('#fb_image_use_mshot').prop('checked', false);
			}
		});
		$('#fb_image_use_mshot').on('change', function(event) {
			if ( $(this).is(':checked') ) {
				$('#fb_image_use_default').prop('checked', false);
			}
		});

		//General
		showDescriptionCustomText(false);
		showDescriptionDefaultCustomText(false);
		showImageOverlayOptions();
		showUrlTrail();
		//OG
		showImageOptions();
		showTypeOptions();
		showPublisherOptions();
		showLocaleOptions();
		showAdminOptions();
		showAppidOptions();
		showFBNotifyOptions();
		//Twitter
		showPublisherTwitterOptions();
		//Schema
		showTypeSchemaOptions();
		showPublisherSchemaOptions();
		//3rd Party
		showYoastSEOOptions();
		showSubheadingOptions();

		//Tools
		$('.fb-og-tool').on('click', function(event) {
			if ( !confirm(texts.confirm_tool) ) {
				event.preventDefault();
			}
		});

		//Functions
		function showDescriptionCustomText(focus) {
			if ($('#fb_desc_homepage').val()=='custom') {
				$('.fb_desc_homepage_customtext_div').show();
				$('#fb_desc_homepage_customtext').val( $.trim($('#fb_desc_homepage_customtext').val()) );
				if ( $('#fb_desc_homepage_customtext').val()=='' ) {
					$('#fb_desc_homepage_customtext').addClass('error');
				} else {
					$('#fb_desc_homepage_customtext').removeClass('error');
				}
				if (focus) $('#fb_desc_homepage_customtext').focus();
			} else {
				$('.fb_desc_homepage_customtext_div').hide();
			}
		}
		$('#fb_desc_homepage').on('change', function() {
			showDescriptionCustomText(true);
		});

		function showDescriptionDefaultCustomText(focus) {
			if ($('#fb_desc_default_option').val()=='custom') {
				$('.fb_desc_default_customtext_div').show();
				$('#fb_desc_default').val( $.trim($('#fb_desc_default').val()) );
				if ( $('#fb_desc_default_option').val()=='' ) {
					$('#fb_desc_default').addClass('error');
				} else {
					$('#fb_desc_default').removeClass('error');
				}
				if (focus) $('#fb_desc_default').focus();
			} else {
				$('.fb_desc_default_customtext_div').hide();
			}
		}
		$('#fb_desc_default_option').on('change', function() {
			showDescriptionDefaultCustomText(true);
		});

		function showImageOverlayOptions() {
			if ($('#fb_image_overlay').is(':checked')) {
				$('.fb_image_overlay_options').show();
				$('#fb_image_overlay_image').val( $.trim($('#fb_image_overlay_image').val()) );
				if ( $('#fb_image_overlay_image').val()=='' ) {
					$('#fb_image_overlay_image').addClass('error');
				} else {
					$('#fb_image_overlay_image').removeClass('error');
				}
			} else {
				$('.fb_image_overlay_options').hide();
			}
		}
		$('#fb_image_overlay').on('click', function() {
			showImageOverlayOptions();
		});

		function showUrlTrail() {
			if ($('#fb_url_add_trailing').is(':checked')) {
				$('#fb_url_add_trailing_example').show();
			} else {
				$('#fb_url_add_trailing_example').hide();
			}
		}
		$('#fb_url_add_trailing').on('click', function() {
			showUrlTrail();
		});

		function showImageOptions() {
			if ($('#fb_image_show').is(':checked')) {
				$('.fb_image_options').show();
			} else {
				$('.fb_image_options').hide();
			}
		}
		$('#fb_image_show').on('click', function() {
			showImageOptions();
		});

		function showTypeOptions() {
			if ($('#fb_type_show').is(':checked')) {
				$('.fb_type_options').show();
			} else {
				$('.fb_type_options').hide();
			}
		}
		$('#fb_type_show').on('click', function() {
			showTypeOptions();
		});

		function showTypeSchemaOptions() {
			if ($('#fb_type_show_schema').is(':checked')) {
				$('.fb_type_schema_options').show();
			} else {
				$('.fb_type_schema_options').hide();
			}
		}
		$('#fb_type_show_schema').on('click', function() {
			showTypeSchemaOptions();
		});

		function showPublisherOptions() {
			if ($('#fb_publisher_show').is(':checked')) {
				$('.fb_publisher_options').show();
				$('#fb_publisher').val( $.trim($('#fb_publisher').val()) );
				if ( $('#fb_publisher').val()=='' ) {
					$('#fb_publisher').addClass('error');
				} else {
					$('#fb_publisher').removeClass('error');
				}
			} else {
				$('.fb_publisher_options').hide();
			}
		}
		$('#fb_publisher_show').on('click', function() {
			showPublisherOptions();
		});

		function showLocaleOptions() {
			if ($('#fb_locale_show').is(':checked')) {
				$('.fb_locale_options').show();
			} else {
				$('.fb_locale_options').hide();
			}
		}
		$('#fb_locale_show').on('click', function() {
			showLocaleOptions();
		});

		function showAdminOptions() {
			if ($('#fb_admin_id_show').is(':checked')) {
				$('.fb_admin_id_options').show();
				$('#fb_admin_id').val( $.trim($('#fb_admin_id').val()) );
				if ( $('#fb_admin_id').val()=='' ) {
					$('#fb_admin_id').addClass('error');
				} else {
					$('#fb_admin_id').removeClass('error');
				}
			} else {
				$('.fb_admin_id_options').hide();
			}
		}
		$('#fb_admin_id_show').on('click', function() {
			showAdminOptions();
		});

		function showAppidOptions() {
			if ($('#fb_app_id_show').is(':checked')) {
				$('.fb_app_id_options').show();
				$('#fb_app_id').val( $.trim($('#fb_app_id').val()) );
				if ( $('#fb_app_id').val()=='' ) {
					$('#fb_app_id').addClass('error');
				} else {
					$('#fb_app_id').removeClass('error');
				}
			} else {
				$('.fb_app_id_options').hide();
			}
		}
		$('#fb_app_id_show').on('click', function() {
			showAppidOptions();
		});

		function showFBNotifyOptions() {
			if ($('#fb_adv_notify_fb').is(':checked')) {
				$('.fb_adv_notify_fb_options').show();
			} else {
				$('.fb_adv_notify_fb_options').hide();
			}
		}
		$('#fb_adv_notify_fb').on('click', function() {
			showFBNotifyOptions();
		});

		function showPublisherTwitterOptions() {
			if ($('#fb_publisher_show_twitter').is(':checked')) {
				$('.fb_publisher_twitter_options').show();
				$('#fb_publisher_twitteruser').val( $.trim($('#fb_publisher_twitteruser').val()) );
				if ( $('#fb_publisher_twitteruser').val()=='' ) {
					$('#fb_publisher_twitteruser').addClass('error');
				} else {
					$('#fb_publisher_twitteruser').removeClass('error');
				}
			} else {
				$('.fb_publisher_twitter_options').hide();
			}
		}
		$('#fb_publisher_show_twitter').on('click', function() {
			showPublisherTwitterOptions();
		});

		function showPublisherSchemaOptions() {
			if ($('#fb_publisher_show_schema').is(':checked')) {
				$('.fb_publisher_schema_options').show();
				$('#fb_publisher_schema').val( $.trim($('#fb_publisher_schema').val()) );
				if ( $('#fb_publisher_schema').val()=='' ) {
					$('#fb_publisher_schema').addClass('error');
				} else {
					$('#fb_publisher_schema').removeClass('error');
				}
			} else {
				$('.fb_publisher_schema_options').hide();
			}
		}
		$('#fb_publisher_show_schema').on('click', function() {
			showPublisherSchemaOptions();
		});

		function showYoastSEOOptions() {
			if ($('#fb_show_wpseoyoast').is(':checked')) {
				$('.fb_wpseoyoast_options').show();
			} else {
				$('.fb_wpseoyoast_options').hide();
			}
		}
		$('#fb_show_wpseoyoast').on('click', function() {
			showYoastSEOOptions();
		});

		function showSubheadingOptions() {
			if ($('#fb_show_subheading').is(':checked')) {
				$('.fb_subheading_options').show();
			} else {
				$('.fb_subheading_options').hide();
			}
		}
		$('#fb_show_subheading').on('click', function() {
			showSubheadingOptions();
		});

	});
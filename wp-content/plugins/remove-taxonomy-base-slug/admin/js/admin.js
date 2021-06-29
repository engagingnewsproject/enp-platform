var remove_taxonomy_base_slug_admin_panel;
( function( $ ) {
	remove_taxonomy_base_slug_admin_panel = {
		init : function() {
			
			$('#remove_taxonomy_base_slug-admin-panel .remove_taxonomy_base_slug-admin-panel-menu-link:first').addClass('visible');
			$('#remove_taxonomy_base_slug-admin-panel .remove_taxonomy_base_slug-admin-panel-content-box:first').addClass('visible');
			$('.remove_taxonomy_base_slug-admin-panel-menu-link').click(function(event) {
				event.preventDefault();
			});

			$('.remove_taxonomy_base_slug-admin-panel-menu-link').click(function() {
				wpi_title = $(this).attr("id").replace('remove_taxonomy_base_slug-admin-panel-menu-', '');
				$('.remove_taxonomy_base_slug-admin-panel-menu-link').removeClass('visible');
				$('#remove_taxonomy_base_slug-admin-panel-menu-' + wpi_title).addClass('visible');
				$('.remove_taxonomy_base_slug-admin-panel-content-box').removeClass('visible');
				$('.remove_taxonomy_base_slug-admin-panel-content-box').hide();
				$('#remove_taxonomy_base_slug-admin-panel-content-' + wpi_title).fadeIn("fast");
				$('.remove_taxonomy_base_slug-admin-panel-content-box').removeClass('visible');
			});
			
			//the settings page id
			var data_name = $('#remove_taxonomy_base_slug-admin-panel-footer input').attr('id');

			//on form submit function
			$('#remove_taxonomy_base_slugform').submit(function(){
				if(!$(this).hasClass('noclick')) {
					$('#remove_taxonomy_base_slug__settings_array').removeClass('button-primary');
					$('#remove_taxonomy_base_slug__settings_array').addClass('button-disabled');
				
					//add class noclick for disabling the user to save multiple times that eats a lot of cpu power
					$(this).addClass('noclick');
					var serializedReturn = $('#remove_taxonomy_base_slugform').serialize();
					
					var data = {
						id: data_name,
						action: 'remove_taxonomy_base_slug_admin_save',
						data: serializedReturn,
						remove_taxonomy_base_slug_nonce: $('#remove_taxonomy_base_slug_nonce').val()
					};
					
					$.post(ajaxurl, data, function(response){
						if(response != 1) {
							alert(response);
						}
						$('#remove_taxonomy_base_slugform').removeClass('noclick');
						$('#remove_taxonomy_base_slug__settings_array').removeClass('button-disabled');
						$('#remove_taxonomy_base_slug__settings_array').addClass('button-primary');
					});
				}
				return false;
			});   	
		}
	};

	$( document ).ready( function( $ ) { remove_taxonomy_base_slug_admin_panel.init(); } );
} ) ( jQuery );
(function($){
	var menu_flip_speed = 200,
		recent_work_opacity_speed = 400,
		featured_controllers_opacity_speed = 500,
		featured_bar_animation_speed = 500,
		featured_bar_animation_easing = 'easeOutExpo',
		$et_mobile_nav_button = $('#mobile_nav'),
		$main_menu = $('ul.nav'),
		$featured = $('#featured'),
		$featured_controllers_container = $('#featured-controllers'),
		$featured_control_item = $featured_controllers_container.find('li'),
		et_container_width = $('#container').innerWidth(),
		$et_footer_widget = $('.footer-widget'),
		$cloned_nav,
		et_slider;

	$(document).ready(function(){
		var $recent_work_thumb = $('#recent-work .thumb'),
			$comment_form = jQuery('form#commentform');

		$main_menu.superfish({
			delay:       300,                            // one second delay on mouseout
			animation:   {opacity:'show',height:'show'},  // fade-in and slide-down animation
			speed:       'fast',                          // faster animation speed
			autoArrows:  true,                           // disable generation of arrow mark-up
			dropShadows: false                            // disable drop shadows
		});

		if ( $('ul.et_disable_top_tier').length ) $("ul.et_disable_top_tier > li > ul").prev('a').attr('href','#');

		$main_menu.find('>li').each( function(){
			var $this_li = $(this),
				li_text = $this_li.find('>a').html();

			$this_li.find('>a').html( '<span class="main_text">' + li_text + '</span>' + '<span class="menu_slide">' + li_text + '</span>' )
		} );

		$main_menu.find('>li').hover( function(){
			$(this).addClass( 'et_hover' );
		}, function(){
			$(this).removeClass( 'et_hover' );
		} );

		if ( ! $('html#ie7').length ){
			$main_menu.find('>li>a').live({
				mouseenter: function(){
					if ( ! $(this).parent('li').hasClass('current-menu-item') )
						$(this).find('span.main_text').animate( { 'marginTop' : '-44px' }, menu_flip_speed );
				},
				mouseleave: function(){
					$(this).find('span.main_text').stop(true,true).animate( { 'marginTop' : '0' }, menu_flip_speed );
				}
			});
		}

		$('.js #main-menu').show();

		$recent_work_thumb.hover( function(){
			$(this).stop(true,true).animate( { 'opacity' : '.5' }, recent_work_opacity_speed );
		}, function(){
			$(this).stop(true,true).animate( { 'opacity' : '1' }, recent_work_opacity_speed );
		} );

		$featured_control_item.hover( function(){
			if ( ! $(this).hasClass('active-slide') )
				$(this).find('.et_slide_hover').css({'display':'block','opacity':'0'}).stop(true,true).animate( { 'opacity' : '1' }, featured_controllers_opacity_speed );
		}, function(){
			$(this).find('.et_slide_hover').stop(true,true).animate( { 'opacity' : '0' }, featured_controllers_opacity_speed );
		} );

		if ( $featured.length ){
			et_slider_settings = {
				slideshow: false,
				before: function(slider){
					var $this_control = $featured_control_item.eq(slider.animatingTo),
						width_to = '239px';

					if ( et_container_width == 748 ) width_to = '186px';

					if ( $('#featured_controls').length ){
						$('#featured_controls li').removeClass().eq(slider.animatingTo).addClass('active-slide');

						return;
					}

					$featured_control_item.removeClass('active-slide');

					if ( ! $this_control.find('.et_animated_bar').length ) $this_control.append('<div class="et_animated_bar"></div>');
					$this_control.find('.et_animated_bar').css({'display':'block', 'width' : '7px', 'left' : '120px'}).stop(true,true).animate( { width : width_to, 'left' : 0 }, featured_bar_animation_speed, featured_bar_animation_easing, function(){
						$this_control.find('.et_animated_bar').hide()
						.end().find('.et_slide_hover').stop(true,true).animate( { 'opacity' : '0' }, featured_controllers_opacity_speed )
						.end().addClass('active-slide');
					} );
				},
				start: function(slider) {
					et_slider = slider;
				}
			}

			if ( $featured.hasClass('et_slider_auto') ) {
				var et_slider_autospeed_class_value = /et_slider_speed_(\d+)/g;

				et_slider_settings.slideshow = true;

				et_slider_autospeed = et_slider_autospeed_class_value.exec( $featured.attr('class') );

				et_slider_settings.slideshowSpeed = et_slider_autospeed[1];
			}

			if ( $featured.hasClass('et_slider_effect_slide') ){
				et_slider_settings.animation = 'slide';
			}

			et_slider_settings.pauseOnHover = true;

			$featured.flexslider( et_slider_settings );
		}

		if ( ! jQuery('html#ie7').length ) {
			$main_menu.clone().attr('id','mobile_menu').removeClass().appendTo( $et_mobile_nav_button );
			$cloned_nav = $et_mobile_nav_button.find('> ul');
			$cloned_nav.find('span.menu_slide').remove().end().find('span.main_text').removeClass();

			$et_mobile_nav_button.click( function(){
				if ( $(this).hasClass('closed') ){
					$(this).removeClass( 'closed' ).addClass( 'opened' );
					$cloned_nav.slideDown( 500 );
				} else {
					$(this).removeClass( 'opened' ).addClass( 'closed' );
					$cloned_nav.slideUp( 500 );
				}
				return false;
			} );

			$et_mobile_nav_button.find('a').click( function(event){
				event.stopPropagation();
			} );
		}

		$comment_form.find('input:text, textarea').each(function(index,domEle){
			var $et_current_input = jQuery(domEle),
				$et_comment_label = $et_current_input.siblings('label'),
				et_comment_label_value = $et_current_input.siblings('label').text();
			if ( $et_comment_label.length ) {
				$et_comment_label.hide();
				if ( $et_current_input.siblings('span.required') ) {
					et_comment_label_value += $et_current_input.siblings('span.required').text();
					$et_current_input.siblings('span.required').hide();
				}
				$et_current_input.val(et_comment_label_value);
			}
		}).bind('focus',function(){
			var et_label_text = jQuery(this).siblings('label').text();
			if ( jQuery(this).siblings('span.required').length ) et_label_text += jQuery(this).siblings('span.required').text();
			if (jQuery(this).val() === et_label_text) jQuery(this).val("");
		}).bind('blur',function(){
			var et_label_text = jQuery(this).siblings('label').text();
			if ( jQuery(this).siblings('span.required').length ) et_label_text += jQuery(this).siblings('span.required').text();
			if (jQuery(this).val() === "") jQuery(this).val( et_label_text );
		});

		// remove placeholder text before form submission
		$comment_form.submit(function(){
			$comment_form.find('input:text, textarea').each(function(index,domEle){
				var $et_current_input = jQuery(domEle),
					$et_comment_label = $et_current_input.siblings('label'),
					et_comment_label_value = $et_current_input.siblings('label').text();

				if ( $et_comment_label.length && $et_comment_label.is(':hidden') ) {
					if ( $et_comment_label.text() == $et_current_input.val() )
						$et_current_input.val( '' );
				}
			});
		});

		$('.et_slide_video iframe').each( function(){
			var this_src = $(this).attr('src') + '&amp;wmode=opaque';
			$(this).attr('src',this_src);
		} );

		et_search_bar();

		function et_search_bar(){
			var $searchform = $('#top-area div#search-form'),
				$searchinput = $searchform.find("input#searchinput"),
				searchvalue = $searchinput.val();

			$searchinput.focus(function(){
				if (jQuery(this).val() === searchvalue) jQuery(this).val("");
			}).blur(function(){
				if (jQuery(this).val() === "") jQuery(this).val(searchvalue);
			});
		}
	});

	$(window).load( function(){
		var $flexnav = $('#featured .flex-direction-nav'),
			$flexcontrol = $('#featured .flex-control-nav');

		$('#featured-controllers li, #featured_controls a').click( function(){
			var $this_control = $(this),
				order = ! $('#featured_controls').length ? $(this).prevAll('li').length : $(this).parent().prevAll('li').length;

			if ( $this_control.hasClass('active-slide') ) return;

			$featured.flexslider( order );

			return false;
		} );

		et_columns_height_fix();
	} );

	function et_columns_height_fix(){
		var featured_control_min_height = 0,
			footer_min_height = 0;

		$featured_control_item.css( 'minHeight', 0 );
		$et_footer_widget.css( 'minHeight', 0 );

		if ( et_container_width <= 460 ) return;

		$featured_control_item.each( function(){
			var this_height = $(this).innerHeight();

			if ( featured_control_min_height < this_height ) featured_control_min_height = this_height;
		} ).each( function(){
			$(this).css( 'minHeight', featured_control_min_height );
		} );

		$et_footer_widget.each( function(){
			var this_height = $(this).innerHeight();

			if ( footer_min_height < this_height ) footer_min_height = this_height;
		} ).each( function(){
			$(this).css( 'minHeight', footer_min_height );
		} );
	}

	$(window).resize( function(){
		if ( et_container_width != $('#container').innerWidth() ){
			et_container_width = $('#container').innerWidth();
			et_columns_height_fix();
			if ( ! $featured.is(':visible') ) $featured.flexslider( 'pause' );
		}
	} );
})(jQuery)
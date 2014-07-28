jQuery(document).ready(function ($) {

	
	// Filter items when filter link is clicked
	$('.gallery-filter a').click(function(){
	  var selector = $(this).attr('data-filter');
		$('.gallery-filter a').removeClass('selected-gallery-filter');
		$(this).addClass('selected-gallery-filter');
	  $container.isotope({ filter: selector });
	  return false;
	});

	// Portfolio container
	var $container = $('.gallery-content');
	$container.imagesLoaded(function() {
		$container.isotope({
			layoutMode : 'fitRows'	// masonry, fitRows
	  });
	});
	
		$(".gallery-content").infinitescroll({
			navSelector  : '.gallery-navi',    // selector for the paged navigation 
			nextSelector : '.gallery-navi a.next',  // selector for the NEXT link (to page 2)
			itemSelector : '.gallery-content .photo-stack',     // selector for all items you'll retrieve
			animate      : true,
			loading: {
				finishedMsg: 'No more pages to load.',
				img: df.imageUrl+'loading.gif'
			}
		},
			function(newElements) {
				jQuery.each(newElements, function( i ) {
					jQuery( this ).css('display', 'none');
					
				} );

				$(newElements).imagesLoaded(function(){

					//gallery image load
					$( ".gallery-image",newElements ).each(function() {
						$(".gallery-image").fadeIn('slow');
						$(".waiter-gallery").removeClass("loading").addClass("loaded");
					
					});


					$(".gallery-content").isotope('insert', $(newElements));	

					jQuery.each(newElements, function( i ) {
						jQuery( this ).css('display', 'block');
						
					} );

					
				});  
	
				
			}
		);
});
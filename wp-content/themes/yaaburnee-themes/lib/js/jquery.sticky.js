jQuery( document ).ready(function($) {
	var sticky_navigation_offset_top = $('#primary-navigation').offset().top;
    var addpoint = 0;
    if($("body").hasClass("admin-bar")){
      addpoint = 28;
    }
	var sticky_navigation = function(){
		var scroll_top = $(window).scrollTop();		
		if (scroll_top+addpoint > sticky_navigation_offset_top) { 
			$('#header').css({ 'margin-top': '89px'});
			$('#primary-navigation').css({ 'position': 'fixed', 'top':0, 'left':0});
			$('body.logged-in #primary-navigation').css({ 'position': 'fixed', 'top':28, 'left':0 });
			$('body.admin-bar #primary-navigation').css({ 'position': 'fixed', 'top':28, 'left':0 });
		} else {
			$('#header').css({ 'margin-top': '0px'});
			$('#primary-navigation').css({ 'position': 'relative', 'top':0 });
		}   
	};	
	sticky_navigation();	
	$(window).scroll(function() {
		if($(window).width()>=df.responsiveON) {
		 	sticky_navigation();
		}
	});	
});
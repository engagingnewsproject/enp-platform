(function ($) {
  $(function() {
    
    if ( $('#poll-answer-pie-graph').length > 0 ) {
      var data = [
        ['Answer A', parseInt($('#poll-responses-option-1').val())],
        ['Answer B', parseInt($('#poll-responses-option-2').val())], 
        ['Answer C', parseInt($('#poll-responses-option-3').val())], 
        ['Answer D', parseInt($('#poll-responses-option-4').val())]
      ];
      var plot1 = jQuery.jqplot ('poll-answer-pie-graph', [data], 
        { 
          seriesDefaults: {
            // Make this a pie chart.
            renderer: jQuery.jqplot.PieRenderer, 
            rendererOptions: {
              // Put data labels on the pie slices.
              // By default, labels show the percentage of the slice.
              showDataLabels: true
            }
          }, 
          legend: { show:true, location: 'e' }
        }
      );
    }
    
    // Sort the answers
    // $( "#mc-answers" ).sortable();
    
    $("#mc-answers").sortable({
      start: function(event, ui) {
        //ui.item.startPos = ui.item.index();
      },
      stop: function(event, ui) {
        //console.log("Start position: " + ui.item.startPos);
        //console.log("New position: " + ui.item.index());
        
        $('.mc-answers input:hidden').each(function(index){
          $(this).val(index + 1);
        });
        // Update item that is moved
        //$('#mc-answer-order-' + ui.item.startPos).val(ui.item.index());
      }
    });
    
  	// Validate
  	// http://bassistance.de/jquery-plugins/jquery-plugin-validation/
  	// http://docs.jquery.com/Plugins/Validation/
  	// http://docs.jquery.com/Plugins/Validation/validate#toptions

  		$('#poll-form').validate({
  	    rules: {
  	      "input-title": {
  	        minlength: 2,
  	        required: true
  	      },
  	      "input-question": {
  	      	minlength: 2,
  	        required: true
  	      }
  	    },
  			highlight: function(element) {
  				$(element).closest('.form-group').removeClass('success').addClass('error');
  			},
  			success: function(element) {
  				element
  				.text('').addClass('valid')
  				.closest('.form-group').removeClass('error').addClass('success');
  			}
  	  });
      
      $('#foo').slider()
        .on('slide', function(ev){
          $('#slider-value').val(ev.value);
        });
  });
}(jQuery));
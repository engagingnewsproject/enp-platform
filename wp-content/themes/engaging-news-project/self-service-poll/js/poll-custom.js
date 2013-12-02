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
    
    $("input[name='poll-type']").change(function(){
      $('.multiple-choice-answers').fadeToggle();
      $('.slider-answers').fadeToggle();
    });
    
    // Sort the answers
    // $( "#mc-answers" ).sortable();
    
    $("#mc-answers").sortable({
      start: function(event, ui) {
        //ui.item.startPos = ui.item.index();
      },
      stop: function(event, ui) {
        //console.log("Start position: " + ui.item.startPos);
        //console.log("New position: " + ui.item.index());
        
        updateAnswerOrder();
        // Update item that is moved
        //$('#mc-answer-order-' + ui.item.startPos).val(ui.item.index());
      }
    });
    
    $("ul.mc-answers .glyphicon-remove").click(function(){
      $(this).parent().remove();
      updateAnswerOrder();
    });
    
    $("ul.mc-answers li.additional-answer").click(function(){
      var last_index = parseInt($("ul#mc-answers li .form-control").last().val()) + 1;
      var new_answer = $("ul#mc-answers li").last().clone();
      new_answer.children(".form-control").val('');
      new_answer.children(".mc-answer-order").attr('id', 'mc-answer-order-' + last_index);
      new_answer.children(".mc-answer-order").attr('name', 'mc-answer-order-' + last_index);
      new_answer.children(".form-control").attr('id', 'mc-answer-' + last_index);
      new_answer.children(".form-control").attr('name', 'mc-answer-' + last_index);
      new_answer.appendTo("ul#mc-answers");
      $("ul#mc-answers li .form-control").last().select();
      $("ul#mc-answers li .form-control").last().focus();
      
      $("#mc-answer-count").val(last_index);
      //$(this).removeClass("additional-answer");
      updateAnswerOrder();
    });
    
    $("ul.mc-answers .glyphicon-check").click(function(){
      if ( $.trim($(this).siblings(".form-control").val()) ) {
        $("ul#mc-answers .form-control").removeClass("correct-option");
        $(this).siblings(".form-control").addClass("correct-option");
        $('#correct-option').val($(this).siblings(".form-control").attr("id"));
      } else {
        alert("Sorry.  This is not a valid answer.");
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
    
    $('#poll-form').submit(function(event){
      if ( !$('#correct-option').val() ) {
        $('<span class="error correct-option-error">Please indicate the correct answer.</span>').appendTo('#poll-form');
        event.preventDefault();
      }
    });
    
    $('#foo').slider()
      .on('slide', function(ev){
        $('#slider-value').val(ev.value);
      });
      
    function updateAnswerOrder() {
      $('.mc-answers input:hidden').each(function(index){
        $(this).val(index + 1);
      });
    }
  });
}(jQuery));
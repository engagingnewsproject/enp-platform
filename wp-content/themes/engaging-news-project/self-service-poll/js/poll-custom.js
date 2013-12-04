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
    
    function createSlider(minRange, maxRange){
       $('#preview-slider').slider({
            min: minRange,
            max: maxRange
        });
    }
    
    $('#slider-high').keyup(function(){
      //$('#preview-slider').attr('data-slider-max', '20');
      
      $(".slider").html('');   
      $(".slider").after("<input id='preview-slider' type='text' style='display: none;'/>");
      
      createSlider(10, 50);
    });
    
    $("input[name='poll-type']").change(function(){
      //TODO not a good way to go this
      //http://stackoverflow.com/questions/17335373/bootstrap-slider-change-max-value
      $('.slider').css('width', '210px');
      
      $('.multiple-choice-answers').toggle();
      $('.slider-answers').toggle();
      //fadeToggle
    });
    
    $('.form-control').focus(function(){
      toggleAnswerToolTips('hide');
    });
    
    if ( $('.entry_content').hasClass('new_poll') ) {
      toggleAnswerToolTips('show');
    } else {
      toggleAnswerToolTips();
    }
    
    
    function toggleAnswerToolTips(action){
      $('.select-answer').tooltip(action);
      $('.move-answer').tooltip(action);
      $('.remove-answer').tooltip(action);
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
        
        updateAnswerOrder();
        // Update item that is moved
        //$('#mc-answer-order-' + ui.item.startPos).val(ui.item.index());
      }
    });
    
    //Update the correct answer value 
    $('#correct-option').val($('ul#mc-answers .form-control.correct-option').attr("id"));
    
    $("ul.mc-answers").on("click", ".glyphicon-remove", function(){
      if ( $("ul#mc-answers li").length > 1 ) {
        $(this).parent().remove();
        updateAnswerOrder();
      
        if( $(this).siblings('.form-control').hasClass('correct-option') ) {
          $('#correct-option').val('');
        }
      }
    });
    
    $("ul.mc-answers li.additional-answer .form-control").focus(function(){
      var last_index = parseInt($("ul#mc-answers li .mc-answer-order").last().val()) + 1;
      var new_answer = $("ul#mc-answers li").last().clone();
      new_answer.children(".form-control").val('');
      new_answer.children(".mc-answer-order").attr('id', 'mc-answer-order-' + last_index);
      new_answer.children(".mc-answer-order").attr('name', 'mc-answer-order-' + last_index);
      new_answer.children(".mc-answer-id").attr('id', 'mc-answer-id-' + last_index);
      new_answer.children(".mc-answer-id").attr('name', 'mc-answer-id-' + last_index);
      new_answer.children(".mc-answer-id").val('');
      new_answer.children(".form-control").attr('id', 'mc-answer-' + last_index);
      new_answer.children(".form-control").attr('name', 'mc-answer-' + last_index);
      new_answer.children(".form-control").removeClass("correct-option");
      new_answer.appendTo("ul#mc-answers");
      $("ul#mc-answers li .form-control").last().select();
      $("ul#mc-answers li .form-control").last().focus();
      
      $("#mc-answer-count").val(last_index);
      //$(this).removeClass("additional-answer");
      updateAnswerOrder();
    });
    
    $("ul.mc-answers").on("click", ".glyphicon-check", function(){
      if ( $.trim($(this).siblings(".form-control").val()) ) {
        $("ul#mc-answers .form-control").removeClass("correct-option");
        $(this).siblings(".form-control").addClass("correct-option");
        $('#correct-option').val($(this).siblings(".form-control").attr("id"));
        $('.correct-option-error').remove();
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
        $('<label class="error correct-option-error">Please indicate the correct answer.</label>').appendTo('#mc-answers');
        event.preventDefault();
      }
    });
    
    $('#preview-slider').slider()
      .on('slide', function(ev){
        $('#slider-value').val(ev.value);
      });
      
    function updateAnswerOrder() {
      $('.mc-answers .mc-answer-order').each(function(index){
        $(this).val(index + 1);
      });
    }
  });
}(jQuery));
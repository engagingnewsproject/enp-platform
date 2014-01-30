(function ($) {
  $(function() {
    ///////////////////
    // BEGIN REPORT PAGE 
    ///////////////////
    
    $('.delete-responses-button').click(function(){
      var delete_responses_confirmation = confirm("Are you sure you want to delete all response data?  This cannot be undone.");
      if ( delete_responses_confirmation == false ) {
        return event.preventDefault();
      }
    });
    
    if ( $('#quiz-mc-answer-pie-graph').length > 0 ) {
      var data = [];
      
      $('.quiz-responses-option').each( function(index) {
        data[index] = [$(this).val(), parseInt($('#quiz-responses-option-count-' + $(this).attr("id")).val())];
      });
      
      var plot1 = jQuery.jqplot ('quiz-mc-answer-pie-graph', [data], 
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
    
    if ( $('#quiz-slider-answer-pie-graph').length > 0 ) {
      var data = [];
      
      data[0] = ["Percentage correct", parseInt($('#percentage-correct').val())];
      data[1] = ["Percentage answering above", parseInt($('#percentage-answering-above').val())];
      data[2] = ["Percentage answering below", parseInt($('#percentage-answering-below').val())];
      
      var plot1 = jQuery.jqplot ('quiz-slider-answer-pie-graph', [data], 
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
    
    ///////////////////
    // END REPORT PAGE
    ///////////////////
    
    ///////////////////
    // BEGIN CONFIGURE QUIZ PAGE
    ///////////////////
    
    // BEGIN SHOW TOOLTIPS
    
    $('.form-control').focus(function(){
      toggleMCAnswerToolTips('hide');
    });
    
    toggleSliderToolTips();
    
    if ( $('.entry_content').hasClass('new_quiz') ) {
      toggleMCAnswerToolTips('show');
    } else {
      toggleMCAnswerToolTips();
    }
    
    
    function toggleMCAnswerToolTips(action){
      $('.select-answer').tooltip(action);
      $('.move-answer').tooltip(action);
      $('.remove-answer').tooltip(action);
    }
    
    function toggleSliderToolTips(action){
      $('.glyphicon-question-sign').tooltip(action);
    }
    
    // END SHOW TOOLTIPS
    
    // BEGIN HANDLE ANSWER ACTIONS
    
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
      var mc_answer_count = $("ul#mc-answers li").length;
      
      // Minimum of 1 answer
      if ( mc_answer_count > 1 ) {
        $(this).parent().remove();
        updateAnswerOrder();
      
        if( $(this).siblings('.form-control').hasClass('correct-option') ) {
          $('#correct-option').val('');
        }
        
        if ( mc_answer_count == 7) {
          $('.additional-answer-wrapper').show();
        }
      }
      
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
    
    $("ul.mc-answers li.additional-answer .form-control").focus(function(){
      // Max of 7 answers
      var mc_answer_count = $("ul#mc-answers li").length;
      if ( mc_answer_count < 7 ) {
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
        
        if (mc_answer_count == 6) {
          $('.additional-answer-wrapper').hide();
        }
      } else {
        $('.additional-answer-wrapper').hide();
      }
    });
    
    function updateAnswerOrder() {
      $('.mc-answers .mc-answer-order').each(function(index){
        $(this).val(index + 1);
      });
    }
    
    // END HANDLE ANSWER ACTIONS
    
    // BEGIN CHANGE QUIZ TYPE
    
    $("input[name='quiz-type']").change(function(){
      changeQuizType();
    });
    
    $("#quiz-type-label-slider").click(function(){
      $('.slider').css('width', '210px');
      
      $('#qt-slider').attr("checked", "checked");
      
      $('.multiple-choice-answers').hide();
      $('.slider-answers').show();
    });
    
    $("#quiz-type-label-mc").click(function(){
      $('#qt-multiple-choice').attr("checked", "checked");
      
      $('.multiple-choice-answers').show();
      $('.slider-answers').hide();
    });
    
    function changeQuizType() {
      //TODO not a good way to go this
      //http://stackoverflow.com/questions/17335373/bootstrap-slider-change-max-value
      $('.slider').css('width', '210px');
      
      $('.multiple-choice-answers').toggle();
      $('.slider-answers').toggle();
    }
    
    // END CHANGE QUIZ TYPE
    
    // BEGIN SLIDER OPTIONS
    
    $("#use-slider-range").click(function(){
      $(".slider-high-answer-element").toggle();
    });
    
    
    // END SLIDER OPTIONS
    
    // BEGIN LIVE PREVIEW SLIDER
    $('#slider-high').keyup(function(){
      updateSlider();
    });
    
    $('#slider-low').keyup(function(){
      updateSlider();
    });
    
    $('#slider-start').keyup(function(){
      updateSlider();
    });
    
    $('#slider-increment').keyup(function(){
      updateSlider();
    });
    
    $('#slider-label').keyup(function(){
      updateSlider();
    });
    
    $('#preview-slider').slider()
      .on('slide', function(ev){
        var slider_label = $($('.slider-display-label')[0]).text();
        
        $('#slider-value').val(ev.value);
        $('#slider-value-label').text(ev.value + "" + slider_label);
    });
    
    // END LIVE PREVIEW SLIDER
    
    // BEGIN VALIDATE
    
  	// Validate
  	// http://bassistance.de/jquery-plugins/jquery-plugin-validation/
  	// http://docs.jquery.com/Plugins/Validation/
  	// http://docs.jquery.com/Plugins/Validation/validate#toptions

		$('#quiz-form').validate({
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
			}
      ,success: function(element) {
        $(element).closest('.form-group').removeClass('error');
        $(element).remove();
      }
	  });
    
    function validateMCForm(){
      if ( !$('#correct-option').val() ) {
        $('<label class="error correct-option-error">Please indicate the correct answer.</label>').appendTo('#mc-answers');
        $('.select-answer:first').tooltip('show');
        return event.preventDefault();
      }
    }
    
    function validateSliderForm() {
      if ( $('input#use-slider-range:checked').val() != "use-slider-range" ) {
        // Match high value with low value
        $('#slider-high-answer').val($('#slider-low-answer').val());
      }
    }
    
    $('#quiz-form').submit(function(event){
      // if ( $('#input-title') == "This field is required." 
      //      || $('textarea[name="input-question"]').text() == "This field is required." ) {
      //   return event.preventDefault();
      // }
      
      if ( $("input[name='quiz-type']:checked").val() == "multiple-choice" || $("#quiz-type").val() == "multiple-choice") {
        validateMCForm();
      } else {
        validateSliderForm();
      }
    });
    
    // END VALIDATE
    
    ///////////////////
    // END CONFIGURE QUIZ PAGE
    ///////////////////
    
    ///////////////////
    // BEGIN IFRAME PAGE
    ///////////////////
    
    $(".mc-radio-answer-label").click(function(){
      $('#option-radio-' + $(this).attr("id")).attr("checked", "checked");
    });
    
    $('#quiz-display-form').submit(function(event){
      if ( $('.mc-radio-answers').length > 0 ) {
        validateiframeMCForm();
      } else {
        //validateSliderForm();
      }
    });
    
    function validateiframeMCForm() {
      if ( !$("input[name='mc-radio-answers']:checked").val() ) {
        if ( $('.mc-radio-answers-error').length == 0 ) {
          $('<label class="error mc-radio-answers-error">Please select an answer.</label>').appendTo('#quiz-display-form');
        }
        return event.preventDefault();
      }
    }
    
    ///////////////////
    // END IFRAME PAGE
    ///////////////////
    
    ///////////////////
    // BEGIN VIEW QUIZ PAGE
    ///////////////////
    
    // Select all iframe code when clicking in the box
    $("#quiz-iframe-code").focus(function() {
      var $this = $(this);
      $this.select();

      // Work around Chrome's little problem
      $this.mouseup(function() {
          // Prevent further mouseup intervention
          $this.unbind("mouseup");
          return false;
      });
    });
    
    ///////////////////
    // END VIEW QUIZ PAGE
    ///////////////////
  });
  
  // BEGIN CONFIGURE QUIZ LIVE PREVIEW SLIDER
  
  $(window).load(function() {
    $('.input-group').on("click", ".input-group-addon", function(){
      updateSlider();
    });
    
    // Fix bug where slider label not updating on slider click
    $('#preview-slider').slider()
      .on('slideStop', function(ev){
        $('.slider .tooltip .tooltip-inner').text($('#slider-value').val());
      });
  });
  
  function updateSlider() {
    var slider_high_value = $('#slider-high').val() ? parseInt($('#slider-high').val()) : 10;
    var slider_low_value = $('#slider-low').val() ? parseInt($('#slider-low').val()) : 0;
    // var slider_high_answer = $('#slider-high-answer').val();
//       var slider_low_answer = $('#slider-low-answer').val();
    var slider_start_value = $('#slider-start').val() ? $('#slider-start').val() : 0;
    var slider_increment_value = $('#slider-increment').val() ? $('#slider-increment').val() : 1;
    var slider_label = $('#slider-label').val();
    
    $(".slider").after("<input id='preview-slider' type='text' style='display: none;'/>");
    $(".slider").remove(''); 
  
    createSlider(slider_low_value, slider_high_value, slider_increment_value);
  
    $('#preview-slider').slider('setValue', slider_start_value);
    $('#slider-value').val(slider_start_value);
    $('#slider-value-label').text(slider_start_value + slider_label);
    $('.slider-low-label').text(slider_low_value);
    $('.slider-high-label').text(slider_high_value);
    $('.slider-display-label').text(slider_label);
  }

  function createSlider(minRange, maxRange, incrementValue){
     $('#preview-slider').slider({
          min: minRange,
          max: maxRange,
          step: incrementValue
      }).on('slide', function(ev){
        var slider_label = $('#slider-label').val();
        
        $('#slider-value').val(ev.value);
        $('#slider-value-label').text(ev.value + slider_label);
      });;
    
      $('#slider-value').val('');
      $('#slider-value-label').text('');
  }
  
  // END CONFIGURE QUIZ LIVE PREVIEW SLIDER
}(jQuery));





(function ($) {

  // IE Versions 
  var ie = (function(){

      var undef,
          v = 3,
          div = document.createElement('div'),
          all = div.getElementsByTagName('i');

      while (
          div.innerHTML = '<!--[if gt IE ' + (++v) + ']><i></i><![endif]-->',
          all[0]
      );

      return v > 4 ? v : undef;
  }());
  
  $(function() {
    // TODO REMOVE
    jQuery.fn.uislider = jQuery.fn.slider;
    
    ///////////////////
    // BEGIN REPORT PAGE 
    ///////////////////
    
    if ( ie <= 9 ) {
      $('body').addClass('ie9-and-lower');
      $('input, textarea').placeholder();
    }
    
    $('#add-my-ip').click(function(event){
      var ip = $(this).data('user-agent-ip');
      if( ip !== 0 )
        addIPAddress(ip);
      
      return false;
    });
    
    function addIPAddress(current_ip_address) {
      var current_ip_address_list = $('#input-report-ip-addresses').val();
      var add_current_ip_address = true;
      
      current_ip_address_list_array = current_ip_address_list.split(",");
      
      $(current_ip_address_list_array).each(function(index) {
        if ( current_ip_address == this) {
          add_current_ip_address = false;
          alert("The IP Address of " + current_ip_address +  " is on the list. Thanks.");
        }
      });
      
      if ( add_current_ip_address ) {
        var new_current_ip_address_list = current_ip_address_list;
        if ( current_ip_address_list == "") {
          new_current_ip_address_list = current_ip_address;
        } else {
          new_current_ip_address_list = current_ip_address_list + "," + current_ip_address;
        }
        
        $('#input-report-ip-addresses').val( new_current_ip_address_list );
      }
    }
    
    $('.delete-responses-button').click(function(){
      var delete_responses_confirmation = confirm("Are you sure you want to delete all response data?  This cannot be undone.");
      if ( delete_responses_confirmation == false ) {
        return event.preventDefault() ? event.preventDefault() : event.returnValue = false;
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
      
      //data[0] = ["Percentage exact", parseInt($('#percentage-exact').val())];
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
    
    // BEGIN HIDE AND SHOW OPTIONS
    
    $('.panel-info').on("click", ".panel-heading", function(){
      $(this).siblings('.panel-body').toggle();
      if ( $(this).parent().hasClass("style-options") ) {
        $('.style-options .btn.reset-styling').toggle();
      }
    });
    
    // BEGIN SHOW TOOLTIPS
    
    $('.form-control').focus(function(){
      toggleMCAnswerToolTips('hide');
    });
    
    toggleSliderToolTips();
    
    if ( $('.entry_content').hasClass('new_quiz') ) {
      //toggleMCAnswerToolTips('show');
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
      if ( mc_answer_count > 2 ) {
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
    
    $("ul.mc-answers").on('click', '.glyphicon-check', function() {
      //click: function(){//".glyphicon-check"
      //console.log('attempting to select a correct answer');
      if ( $.trim($(this).siblings(".form-control").val()) ) {
        $("ul#mc-answers .form-control").removeClass("correct-option");
        $(this).siblings(".form-control").addClass("correct-option");
        $('#correct-option').val($(this).siblings(".form-control").attr("id"));
        $('.correct-option-error').remove();
      } else {
        alert("Sorry.  This is not a valid answer.");
      }
      
       updateAnswerPreview();
     });
    
     $("ul.mc-answers").on('touchend', '.glyphicon-check', function() {
      if ( $.trim($(this).siblings(".form-control").val()) ) {
        $("ul#mc-answers .form-control").removeClass("correct-option");
        $(this).siblings(".form-control").addClass("correct-option");
        $('#correct-option').val($(this).siblings(".form-control").attr("id"));
        $('.correct-option-error').remove();
      } else {
        alert("Sorry.  This is not a valid answer.");
      }
      
       updateAnswerPreview();
     
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
    
    $("input[name='quiz-type']").change(function() {
      var quiz_type = $("input[name='quiz-type']:checked").val();
      changeQuizType(quiz_type);
    });
    
    $("#quiz-type-label-mc").click(function() {      
      $('#qt-multiple-choice').attr("checked", "checked");
      
      var quiz_type = $("input[name='quiz-type']:checked").val();
      changeQuizType(quiz_type);
    });
    
    $("#quiz-type-label-slider").click(function(){
      $('#qt-slider').attr("checked", "checked");
      
      var quiz_type = $("input[name='quiz-type']:checked").val();
      changeQuizType(quiz_type);
    });
    
    function changeQuizType(quiz_type) {
      //NTH not a good way to go this
      //http://stackoverflow.com/questions/17335373/bootstrap-slider-change-max-value
      $('.quiz-answers-panel .panel-body').show();
      $('.slider').css('width', '210px');
      
      $('.multiple-choice-answers').toggle();
      $('.slider-answers').toggle();
      
      if ( quiz_type == "multiple-choice" ) {
        $('.multiple-choice-answers').show();
        $('.slider-answers').hide();
        $('#incorrect-answer-message-slider-label, #correct-answer-message-slider-label').hide();
        toggleMCAnswerToolTips('show');
      } else {
        $('.multiple-choice-answers').hide();
        $('.slider-answers').show();
        $('#incorrect-answer-message-slider-label, #correct-answer-message-slider-label').show();
      }
      
      resetAllAnswerMessages();
    }
    
    // END CHANGE QUIZ TYPE
    
    // BEGIN SLIDER OPTIONS
    
    $("#use-slider-range").click(function(){
      //var slider_range = $("input[name='use-slider-range']:checked").val();
      $(".slider-high-answer-element").toggle();
      $('.slider-options').toggle();
      
      // if ( slider_range == "use-slider-range" ) {
//         resetAnswerMessage('correct');
//         resetAnswerMessage('incorrect'); 
//       } else {
//         resetAnswerMessage('correct');
//         resetAnswerMessage('incorrect'); 
//       }

      resetAllAnswerMessages();
    });
    
    // END SLIDER OPTIONS
    
    // BEGIN LIVE ANSWER PREVIEW
    
    
    /////////Multiple Chioce
    
    $('#input-question').keyup(function(){
      updateAnswerPreview();
    });
    
    $('#mc-answers .mc-answer').keyup(function(){
      updateAnswerPreview();
    });
    
    ////////Slider
    
    $('#slider-correct-answer').keyup(function(){
      updateAnswerPreview();
    });
    
    $('#slider-low-answer').keyup(function(){
      updateAnswerPreview();
    });
    
    $('#slider-high-answer').keyup(function(){
      updateAnswerPreview();
    });
    
    $('#input-correct-answer-message').keyup(function(){
      updateAnswerPreview();
    });
    
    $('#input-incorrect-answer-message').keyup(function(){
      updateAnswerPreview();
    });

    $('#input-summary-message').keyup(function(){
        updateSummaryPreview();
    });
    
    $('#correct-answer-message-reset').click(function(event){
        event.preventDefault();
        resetAnswerMessage('correct');
        //return event.preventDefault() ? event.preventDefault() : event.returnValue = false;
    });
    
    $('#incorrect-answer-message-reset').click(function(event){
        event.preventDefault();
        resetAnswerMessage('incorrect');
        //return event.preventDefault() ? event.preventDefault() : event.returnValue = false;
    });
    
    function resetAllAnswerMessages() {
      resetAnswerMessage('correct');
      resetAnswerMessage('incorrect');
    }
    
    function resetAnswerMessage( message_type ) {
      var default_answer_message = "";
      var target_message = "";
      
      var quiz_type = $("input[name='quiz-type']:checked").val() ? $("input[name='quiz-type']:checked").val() : $('#quiz-type').val();
      var slider_range = $("input[name='use-slider-range']:checked").val();
      
      if ( quiz_type == "multiple-choice" ) {
        quiz_type = 'mc';
      } else if ( quiz_type == "slider" && slider_range == "use-slider-range" ) {
        quiz_type = 'slider-range';
      } else if ( quiz_type == "slider" ) {
        quiz_type = 'slider';
      }
    
      if ( message_type == "correct" ) {
        target_message = "#input-correct-answer-message";
        default_answer_message = $('#default-' + quiz_type + '-correct-answer-message').val();
      } else {
        target_message = "#input-incorrect-answer-message";
        default_answer_message = $('#default-' + quiz_type + '-incorrect-answer-message').val();
      }
      
      $(target_message).val(default_answer_message);
      
      updateAnswerPreview();
    }
    
    $('#correct-answer-message-user-answer').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-correct-answer-message', '[user_answer]');
    });

    $('#correct-answer-message-slider-label').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-correct-answer-message', '[slider_label]');
    });
    
    $('#correct-answer-message-lower-range').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-correct-answer-message', '[lower_range]');
    });
    
    $('#correct-answer-message-upper-range').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-correct-answer-message', '[upper_range]');
    });

    $('#correct-answer-message-correct-value').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-correct-answer-message', '[correct_value]');
    });
    
    $('#incorrect-answer-message-user-answer').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-incorrect-answer-message', '[user_answer]');
    });

    $('#incorrect-answer-message-slider-label').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-incorrect-answer-message', '[slider_label]');
    });
    
    $('#incorrect-answer-message-lower-range').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-incorrect-answer-message', '[lower_range]');
    });
    
    $('#incorrect-answer-message-upper-range').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-incorrect-answer-message', '[upper_range]');
    });
    
    $('#incorrect-answer-message-correct-value').click(function(event){
        event.preventDefault();
        addVariableToAnswerMessage('input-incorrect-answer-message', '[correct_value]');
    });
    
    function addVariableToAnswerMessage(target_answer_message_selector, variable_text) {      
      insertAtCaret(target_answer_message_selector, variable_text);
      
      updateAnswerPreview();
      
      return false;
    }
    
    function insertAtCaret(areaId, text) {
      var txtarea = document.getElementById(areaId);
      var scrollPos = txtarea.scrollTop;
      var strPos = 0;
      var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ? 
          "ff" : (document.selection ? "ie" : false ) );
      if (br == "ie") { 
          txtarea.focus();
          var range = document.selection.createRange();
          range.moveStart ('character', -txtarea.value.length);
          strPos = range.text.length;
      }
      else if (br == "ff") strPos = txtarea.selectionStart;

      var front = (txtarea.value).substring(0,strPos);  
      var back = (txtarea.value).substring(strPos,txtarea.value.length); 
      txtarea.value=front+text+back;
      strPos = strPos + $(text).length;
      if (br == "ie") { 
          txtarea.focus();
          var range = document.selection.createRange();
          range.moveStart ('character', -txtarea.value.length);
          range.moveStart ('character', strPos);
          range.moveEnd ('character', 0);
          range.select();
      }
      else if (br == "ff") {
          txtarea.selectionStart = strPos;
          txtarea.selectionEnd = strPos;
          txtarea.focus();
      }
      txtarea.scrollTop = scrollPos;

      return false;
    }
    
    // END LIVE ANSWER PREVIEW
    
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
    
    $('#preview-slider').bootstrapSlider()
      .on('slide', function(ev){
        var slider_label = $($('.slider-display-label')[0]).text();
        
        $('#slider-value').val(ev.value);
        var space = ''; if( slider_label.indexOf('%') === -1 ) space = ' ';
        $('#slider-value-label').text(ev.value + space + slider_label);
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
	      },
	      "quiz-type": {
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
    
    function validateMCForm(event){
      if ( !$('#correct-option').val() ) {
        $('<label class="error correct-option-error">Please indicate the correct answer.</label>').appendTo('#mc-answers');
        $('.select-answer:first').tooltip('show');
        return event.preventDefault() ? event.preventDefault() : event.returnValue = false;
      }
    }

    function validateSliderForm() {
      var slider_error = false;
      var use_slider_range = $('input#use-slider-range:checked').val() == "use-slider-range" ? true : false;

      var slider_correct_value = $('#slider-correct-answer').val() ? parseInt($('#slider-correct-answer').val()) : 0;
      var slider_start_value = $('#slider-start').val() ? $('#slider-start').val() : 0;
      var slider_increment_value = $('#slider-increment').val() ? $('#slider-increment').val() : 1;
      var slider_low_value = $('#slider-low').val() ? parseInt($('#slider-low').val()) : 0;
      var slider_high_value = $('#slider-high').val() ? parseInt($('#slider-high').val()) : 10;
      
      // Slider CONDITIONS
      // Slider high value greater than low value
      if ( slider_low_value > slider_high_value ) {
        slider_error = true;
        $('#slider-low').parent('.input-group').addClass('error');
        $('#slider-high').parent('.input-group').addClass('error');
      }
      
      // Slider start value is within the slider range
      if ( slider_start_value < slider_low_value || slider_start_value > slider_high_value ) {
        slider_error = true;
        $('#slider-start').parent('.input-group').addClass('error');
      }
      
      // Slider increment is within the slider range
      if ( slider_increment_value > (slider_high_value - slider_low_value)) {
        slider_error = true;
        $('#slider-increment').parent('.input-group').addClass('error');
      }
      
      // Is the correct value within the slider range
      if ( slider_correct_value < slider_low_value || slider_correct_value > slider_high_value ) {
        slider_error = true;
        $('#slider-correct-answer').parent('.input-group').addClass('error');
      }
      
      // Slider Range CONDITIONS
      if ( use_slider_range ) {
        var slider_low_answer = $('#slider-low-answer').val() ? parseInt($('#slider-low-answer').val()) : 0;
        var slider_high_answer = $('#slider-high-answer').val() ? parseInt($('#slider-high-answer').val()) : 10;

        // Slider low answer higher than low value
        if ( slider_low_answer < slider_low_value || slider_low_answer > slider_high_value) {
          slider_error = true;
          $('#slider-low-answer').parent('.input-group').addClass('error');
        }
        
        // Slider high answer lower than high value
        if ( slider_high_answer > slider_high_value || slider_high_answer < slider_low_value ) {
          slider_error = true;
          $('#slider-high-answer').parent('.input-group').addClass('error');
        }
        
        // Is the correct value within the slider range
        if ( slider_correct_value > slider_high_answer || slider_correct_value < slider_low_answer ) {
          slider_error = true;
          $('#slider-correct-answer').parent('.input-group').addClass('error');
        }
      } else {
        // Match high value with low value
        $('#slider-high-answer').val($('#slider-low-answer').val());
      }
      
      if ( slider_error ) {
        $('<label class="error correct-option-error">Please check the slider values.</label>').prependTo('#quiz-answers');
        return event.preventDefault() ? event.preventDefault() : event.returnValue = false;
      }
    }
    
    $('#quiz-form').submit(function(event){
      // if ( $('#input-title') == "This field is required." 
      //      || $('textarea[name="input-question"]').text() == "This field is required." ) {
      //   return event.preventDefault() ? event.preventDefault() : event.returnValue = false;
      // }
      
      if ( $("input[name='quiz-type']:checked").val() == "multiple-choice" || $("#quiz-type").val() == "multiple-choice") {
        validateMCForm();
      } else {
        validateSliderForm();
      }
    });
    
    // END VALIDATE
    
    // BEING STYLE/STYLING
    
    $('.reset-styling').click(function(){
      $('#quiz-background-color').val('#ffffff');
      $('#quiz-text-color').val('#000000');
      $('#quiz-display-width').val('336px');
      $('#quiz-display-height').val('280px');
      $('#quiz-display-css').val('');
      $('#quiz-show-title').prop('checked', false);
      
      return false;
    });
    
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

        validateiframeMCForm(event);
      } else {
        //validateSliderForm();
      }
    });
    
    function validateiframeMCForm(event) {
      if ( !$("input[name='mc-radio-answers']:checked").val() ) {
        if ( $('.mc-radio-answers-error').length == 0 ) {
            event.preventDefault();
          $('<label class="error mc-radio-answers-error">Please select an answer.</label>').appendTo('#quiz-display-form');
        }
        return false;
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
    $('#preview-slider').bootstrapSlider()
      .on('slideStop', function(ev){
        $('.slider .tooltip .tooltip-inner').text($('#slider-value').val());
      });
      
    sliderUsabilityNote();
    
    $('#preview-slider').bootstrapSlider('setValue', $('#preview-slider').data('slider-value'));
  });

  function updateSummaryPreview() {
      var summary_message = $('#input-summary-message').val() ? $('#input-summary-message').val() : 'Thanks for taking our quiz!';
      $('#summary_message').html(summary_message);
  }
  
  function updateAnswerPreview() {
    var quiz_question = $('#input-question').val() ? $('#input-question').val() : "Enter Quiz Question";
    $('.quiz-question-preview').html(quiz_question);
    
    if ( $('.multiple-choice-answers').is(":visible") ) {
      updateMCAnswer();
    } else {
      updateSliderAnswer();
    }
  }
  
  function updateMCAnswer() {
    var correct_answer_message = $('#input-correct-answer-message').val();
    var correct_mc_value = $('.correct-option').val() ? $('.correct-option').val() : " [correct_answer] ";
    var user_mc_value = $('.correct-option').val() ? $('.correct-option').val() : " [user_answer] ";
    var incorrect_mc_value = $('.mc-answers .mc-answer:not(.correct-option)').val() ?  
      $('.mc-answers .mc-answer:not(.correct-option)').val() : "[user_answer]";
    
    correct_answer_message = answerMessageReplacements(correct_answer_message, correct_mc_value, 
      user_mc_value, "", "");
      
    var incorrect_answer_message = $('#input-incorrect-answer-message').val();
      
    incorrect_answer_message = answerMessageReplacements(incorrect_answer_message, correct_mc_value, 
      incorrect_mc_value, "", "");
      
    $('.correct-answer-message').html(correct_answer_message);
    $('.incorrect-answer-message').html(incorrect_answer_message);
  }
  
  function updateSliderAnswer() {
    var slider_low_answer = $('#slider-low-answer').val() ? parseInt($('#slider-low-answer').val()) : 0;
    var slider_high_answer = $('#slider-high-answer').val() ? parseInt($('#slider-high-answer').val()) : 10;
    var slider_correct_value = $('#slider-correct-answer').val() ? parseInt($('#slider-correct-answer').val()) : 0;
    var slider_start_value = $('#slider-start').val() ? $('#slider-start').val() : 0;
    var slider_label = $('#slider-label').val() ? $('#slider-label').val() : '%';
    
    var correct_answer_message = $('#input-correct-answer-message').val();
    
    correct_answer_message = answerMessageReplacements(correct_answer_message, slider_correct_value, slider_correct_value, 
    slider_low_answer, slider_high_answer, slider_label);
      
    var incorrect_answer_message = $('#input-incorrect-answer-message').val();
      
    incorrect_answer_message = answerMessageReplacements(incorrect_answer_message, slider_correct_value, slider_high_answer+1, 
    slider_low_answer, slider_high_answer, slider_label);
          
    $('.correct-answer-message').html(correct_answer_message);
    $('.incorrect-answer-message').html(incorrect_answer_message);
    
    clearSliderErrors();
  }
  
  function answerMessageReplacements(answer_message, correct_value, user_answer, 
    slider_low_answer, slider_high_answer, slider_label ) {

    answer_message = answer_message.replace(/\[correct_value\]/g, correct_value);
    answer_message = answer_message.replace(/\[user_answer\]/g, user_answer);
    if( typeof slider_label != 'undefined' ) {
      answer_message = answer_message.replace(/\[slider_label\]/g, slider_label);
    }
    answer_message = answer_message.replace(/\[lower_range\]/g, slider_low_answer);
    answer_message = answer_message.replace(/\[upper_range\]/g, slider_high_answer);
    
    return answer_message;
  }
  
  function updateSlider() {
    var slider_high_value = $('#slider-high').val() ? parseInt($('#slider-high').val()) : 10;
    var slider_low_value = $('#slider-low').val() ? parseInt($('#slider-low').val()) : 0;
    var slider_start_value = $('#slider-start').val() ? $('#slider-start').val() : 0;
    var slider_increment_value = $('#slider-increment').val() ? $('#slider-increment').val() : 1;
    var slider_label = $('#slider-label').val();
    
    $(".slider").after("<input id='preview-slider' type='text' style='display: none;'/>");
    $(".slider").remove(''); 
  
    createSlider(slider_low_value, slider_high_value, slider_increment_value);
  
    $('#preview-slider').bootstrapSlider('setValue', slider_start_value);
    // TODO not working
    //$('#preview-slider').bootstrapSlider.val(slider_start_value);
    $('#slider-value').val(slider_start_value);
    var space = ''; if( slider_label.indexOf('%') === -1 ) space = ' ';
    $('#slider-value-label').text(slider_start_value + space + slider_label);
    $('.slider-low-label').text(slider_low_value);
    $('.slider-high-label').text(slider_high_value);
    $('.slider-display-label').text( space + slider_label);
    
    sliderUsabilityNote();
      
    updateAnswerPreview();
    
    clearSliderErrors();
  }

  function createSlider(minRange, maxRange, incrementValue){
     $('#preview-slider').bootstrapSlider({
          min: minRange,
          max: maxRange,
          step: incrementValue
      }).on('slide', function(ev){
        var slider_label = $('#slider-label').val();
        
        $('#slider-value').val(ev.value);
        var space = ''; if( slider_label.indexOf('%') === -1 ) space = ' ';
        $('#slider-value-label').text(ev.value + space + slider_label);
      });;
    
      $('#slider-value').val('');
      $('#slider-value-label').text('');
  }
  
  function sliderUsabilityNote() {
    var slider_high_value = $('#slider-high').val() ? parseInt($('#slider-high').val()) : 10;
    var slider_low_value = $('#slider-low').val() ? parseInt($('#slider-low').val()) : 0;
    var slider_increment_value = $('#slider-increment').val() ? $('#slider-increment').val() : 1;
    var slider_selectable_values = (slider_high_value - slider_low_value)/slider_increment_value;
    
    if ( slider_selectable_values > 100 ) {
      $('.slider-usability-note').show();
      $('#slider-selectable-values').text(slider_selectable_values);
    } else {
      $('.slider-usability-note').hide();
      $('#slider-selectable-values').text();
    }
  }
  
  function clearSliderErrors() {
    // Clear on value change and only show new error on form submit, to give user time to change all values
    $('#quiz-answers .input-group').removeClass('error');
  }
  
  // END CONFIGURE QUIZ LIVE PREVIEW SLIDER
}(jQuery));





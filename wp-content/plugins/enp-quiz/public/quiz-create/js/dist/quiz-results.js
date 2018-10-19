jQuery( document ).ready( function( $ ) {
var yScaleMax = _.max(quiz_results_json.quiz_scores);
var yScaleMin = _.min(quiz_results_json.quiz_scores);
// get the difference between them
var yScaleLength = yScaleMax - yScaleMin;
// pad by 10% of the length
var yScalePad = Math.ceil(yScaleLength * 0.1);
// pad the max number by 10% of the length
yScaleMax = yScaleMax + yScalePad;
yScaleMin = yScaleMin - yScalePad;

if(yScaleMin < 0) {
    yScaleMin = 0;
}

var chart = new Chartist.Line('.enp-quiz-score__line-chart', {
  labels: quiz_results_json.quiz_scores_labels,
  series: [quiz_results_json.quiz_scores]
}, {
  high: yScaleMax,
  low: yScaleMin,
  fullWidth: true,
  chartPadding: {
    right: 38
  },
  lineSmooth: Chartist.Interpolation.cardinal({
      fillHoles: true,
  }),
  axisY: {
      onlyInteger: true,
  }
});

chart.on('draw', function(data) {
  if(data.type === 'line' || data.type === 'area') {
    data.element.animate({
      d: {
        begin: 1400 * data.index,
        dur: 1400,
        from: data.path.clone().scale(1, 0).translate(0, data.chartRect.height()).stringify(),
        to: data.path.clone().stringify(),
        easing: Chartist.Svg.Easing.easeOutQuint
      }
    });
  }
});

// set-up accordions for question results
$('.enp-results-question').each(function() {
    var accordion = {header: $('.enp-results-question__header', this), content: $('.enp-results-question__content', this)};
    enp_accordion__setup(accordion);
});

// set-up slider question accordions and charts
$('.enp-results-question--slider').each(function() {
    // create the title and content accordion object so our headings can get created
    accordion = {title:  'Slider Response Data', content: $('.enp-slider-responses-table__content', this), baseID: $(this).attr('id')};
    //returns an accordion object with the header object and content object
    accordion = enp_accordion__create_headers(accordion);
    // set-up all the accordion classes and start classes (so they're closed by default)
    enp_accordion__setup(accordion);

    // hide zero slider responses by default
    $('.enp-slider-responses-table').addClass('enp-slider-responses-table--hide-zero');
    // write button to control showing/hiding of zero'd data
    $('.enp-slider-responses-table__response-frequency', this).append('<button class="enp-slider-responses-table__toggle-zero-frequency" type="button">Show All</button>');

    //set-up slider response chart
    // slider chart container
    sliderChartID = $('.enp-slider-responses__line-chart',this).attr('id');
    sliderID = $('.enp-slider-responses__line-chart',this).attr('data-slider-id');
    sliderJSON = slider_results_json[sliderID];

    var yScaleMax = _.max(sliderJSON.slider_response_frequency);
    var yScaleMin = _.min(sliderJSON.slider_response_frequency);
    // get the difference between them
    var yScaleLength = yScaleMax - yScaleMin;
    // pad by 10% of the length
    var yScalePad = Math.ceil(yScaleLength * 0.1);
    // pad the max number by 10% of the length
    yScaleMax = yScaleMax + yScalePad;
    yScaleMin = yScaleMin - yScalePad;

    if(yScaleMin < 0) {
        yScaleMin = 0;
    }

    var sliderChart = new Chartist.Line('#'+sliderChartID, {
      labels: sliderJSON.slider_response,
      series: [{
          value: sliderJSON.slider_response_low_frequency,
          className: 'enp-slider-responses__line-chart--low',
      },{
          value: sliderJSON.slider_response_high_frequency,
          className: 'enp-slider-responses__line-chart--high',
      },{
          value: sliderJSON.slider_response_correct_frequency,
          className: 'enp-slider-responses__line-chart--correct',
      }]
    }, {
      high: yScaleMax,
      low: yScaleMin,
      fullWidth: true,
      chartPadding: {
        right: 38
      },
      axisY: {
          onlyInteger: true,
      },
      axisX: {
          labelInterpolationFnc: function(value, index) {
             // show every other one
            return index % 5 === 0 ? value : '';
          }
      }
    });

});

$(document).on('click', '.enp-slider-responses-table__toggle-zero-frequency', function() {
    responseTable = $(this).parent().parent().parent().parent();
    if(responseTable.hasClass('enp-slider-responses-table--show-zero')) {
        $('.enp-slider-responses-table__frequency--zero', responseTable).hide();
        responseTable.removeClass('enp-slider-responses-table--show-zero');
        responseTable.addClass('enp-slider-responses-table--hide-zero');
        $(this).text('Show All');
    } else {
        $('.enp-slider-responses-table__frequency--zero', responseTable).show();
        responseTable.removeClass('enp-slider-responses-table--hide-zero');
        responseTable.addClass('enp-slider-responses-table--show-zero');
        $(this).text('Hide Zeros');
    }

});

// show the chart/draw it on click
$(document).on('click', '.enp-results-question--slider .enp-results-question__header.enp-accordion-header--closed', function() {
    sliderChartID = $(this).parent().find('.enp-slider-responses__line-chart').attr('id');
    sliderChart = document.querySelector('#'+sliderChartID);
    sliderChart.__chartist__.update();

});
});
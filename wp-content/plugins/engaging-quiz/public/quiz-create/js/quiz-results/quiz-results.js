
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

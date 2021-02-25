

var yScaleMaxA = _.max(ab_results_json.quiz_a_scores);
var yScaleMaxB = _.max(ab_results_json.quiz_b_scores);
var yScaleMinA = _.min(ab_results_json.quiz_a_scores);
var yScaleMinB = _.min(ab_results_json.quiz_b_scores);

// see what our real max/min should be
if(yScaleMaxA <= yScaleMaxB) {
    yScaleMax = yScaleMaxB;
} else {
    yScaleMax = yScaleMaxA;
}
// set the min
if(yScaleMinA <= yScaleMinB) {
    yScaleMin = yScaleMinA;
} else {
    yScaleMin = yScaleMinB;
}

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

// set the winner/loser classes
chart = new Chartist.Line('.enp-quiz-score__line-chart', {
  labels: ab_results_json.ab_results_labels,
  series: [{
      value: ab_results_json.quiz_a_scores,
      className: ab_results_json.quiz_a_class,
  },
  {
      value: ab_results_json.quiz_b_scores,
      className: ab_results_json.quiz_b_class,
  }]
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

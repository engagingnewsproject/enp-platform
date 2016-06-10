// turn on mustache/handlebars style templating
_.templateSettings = {
  interpolate: /\{\{(.+?)\}\}/g
};
// Templates
if($('#question_template').length) {
    var questionTemplate = _.template($('#question_template').html());
    var mcOptionTemplate = _.template($('#mc_option_template').html());
    var sliderTemplate = _.template($('#slider_template').html());
    var sliderRangeHelpersTemplate = _.template($('#slider_range_helpers_template').html());
    var questionImageTemplate = _.template($('#question_image_template').html());
}
if($('#question_explanation_template').length) {
    var questionExplanationTemplate = _.template($('#question_explanation_template').html());
}
if($('#quiz_end_template').length) {
    var quizEndTemplate = _.template($('#quiz_end_template').html());
}

/*
* Set-up Underscore Templates
*/
// set-up templates
// turn on mustache/handlebars style templating
_.templateSettings = {
  interpolate: /\{\{(.+?)\}\}/g
};
var questionTemplate = _.template($('#question_template').html());
var questionImageTemplate = _.template($('#question_image_template').html());
var questionImageUploadButtonTemplate = _.template($('#question_image_upload_button_template').html());
var questionImageUploadTemplate = _.template($('#question_image_upload_template').html());
var mcOptionTemplate = _.template($('#mc_option_template').html());
var sliderTemplate = _.template($('#slider_template').html());
var sliderTakeTemplate = _.template($('#slider_take_template').html());
var sliderRangeHelpersTemplate = _.template($('#slider_take_range_helpers_template').html());
//$('#enp-quiz').prepend(questionTemplate({question_id: '999', question_position: '53'}));

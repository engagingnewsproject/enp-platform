// set-up accordions for question results
$('.enp-results-question').each(function() {
    var accordion = {header: $('.enp-results-question__header', this), content: $('.enp-results-question__content', this)};
    enp_accordion__setup(accordion);
});

jQuery(document).ready(function($){
    if (typeof ajaxurl === "undefined") {
        ajaxurl = ir_review.ajaxurl;
    }

    $('.irreview-hide-review').click(function(e){
        var slug = ($(e.target).parents('.irreview-notice').attr('data-slug'));
        hide_review(slug);

    });

    $('.irreview-already-review').on('click', function (e) {
        e.preventDefault();
        var slug = ($(e.target).parents('.irreview-notice').attr('data-slug'));
        var linkreview = $(this).attr('href');

        window.open(linkreview);
        hide_review(slug);
    });

    function hide_review(slug) {
        $.ajax({
            url: ajaxurl,
            dataType: 'json',
            method: 'POST',
            data: {
                action: 'irreview_ajax_hide_review_' + slug,
                ajaxnonce: ir_review.token
            },
            success: function () {
                $('.irreview-notice[data-slug="' + slug + '"]').hide('fade');
            }
        });
    }
});
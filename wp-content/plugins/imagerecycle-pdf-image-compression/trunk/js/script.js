jQuery(document).ready(function($) {
    initButtons = function() {
        if (typeof wpio_disable_optimization != 'undefined' && wpio_disable_optimization) {
            $('.ioa-proceed').prop('disabled', true).addClass('disabled');
        }
        $('.ioa-proceed').click(function(e) {
            e.preventDefault();
            if ($(e.currentTarget).hasClass('disabled')) return;

            var btnOptimze =  $(e.currentTarget);
            btnOptimze.prop('disabled', true).addClass('disabled');
            btnOptimze.closest('tr').find('.spinner').css('visibility', 'visible');

            var action = $(e.currentTarget).data('action');
            var file = $(e.currentTarget).data('file');
            $.ajax({
                url: ajaxurl,
                data: { action: action, file: file },
                type: 'post',
                dataType: 'json',
                success: function(response) {
                    btnOptimze.prop('disabled', false).removeClass('disabled');
                    btnOptimze.closest('tr').find('.spinner').css('visibility', 'hidden');
                    // response = $.parseJSON(response);                
                    if (typeof(response.status) !== 'undefined' && response.status === true) {
                        if (action === 'wpio_optimize') {
                            btnOptimze.parents('tr').find('.optimizationStatus').html(response.datas.msg);
                            btnOptimze.replaceWith('<a class="button ioa-proceed" data-action="wpio_revert" data-file="' + file + '"></span>Revert to original</a>');
                        } else {
                            btnOptimze.parents('tr').find('.optimizationStatus').html('');
                            btnOptimze.replaceWith('<a class="button button-primary ioa-proceed" data-action="wpio_optimize" data-file="' + file + '">Optimize</a>');
                        }
                        initButtons();
                    } else {
                        if (typeof(response.datas) !== 'undefined' && response.datas != null && typeof(response.datas.msg) !== 'undefined') {
                            error = response.datas.msg;
                        } else {
                            error = "An error occured";
                        }
                        btnOptimze.parents('tr').find('.optimizationStatus').html(error);
                    }

                }
            });
        });

        $(".image-small").show();
    };
    initButtons();

    initQueuedButton = function() {
        $('.ioa-queued').click(function(e) {
            e.preventDefault();
            r = confirm("Remove this file from optimization queue?");
            if (r == true) {

                $(e.currentTarget).closest('tr').find('.spinner').css('visibility', 'visible');
                var action = $(e.currentTarget).data('action');
                var file = $(e.currentTarget).data('file');
                $.ajax({
                    url: ajaxurl,
                    data: { action: action, file: file },
                    type: 'post',
                    dataType: 'json',
                    success: function(response) {

                        $(e.currentTarget).closest('tr').find('.spinner').css('visibility', 'hidden');
                        // response = $.parseJSON(response);                
                        if (typeof(response.status) !== 'undefined' && response.status === true) {

                            $(e.currentTarget).parents('tr').find('.optimizationStatus').html('');
                            $(e.currentTarget).replaceWith('<a class="button button-primary ioa-proceed" data-action="wpio_optimize" data-file="' + file + '">Optimize</a>');

                            initButtons();
                        } else {
                            if (typeof(response.datas) !== 'undefined' && typeof(response.datas.msg) !== 'undefined') {
                                error = response.datas.msg;
                            } else {
                                error = "An error occured";
                            }
                            $(e.currentTarget).parents('tr').find('.optimizationStatus').html(error);
                        }

                    }
                });
            } else {
                //do nothing            
            }

        });
    };
    initQueuedButton();

    startTime = 0;
    totalImagesProcessing = 0;
    $('#doaction').click(function() {
        if ($('#bulk-action-selector-top').val() === 'optimize_selected') {
            $('#the-list').find('input[name="image[]"]:checked').parents('tr').find('.ioa-proceed[data-action="wpio_optimize"]').trigger('click');
        } else if ($('#bulk-action-selector-top').val() === 'revert_selected') {
            $('#the-list').find('input[name="image[]"]:checked').parents('tr').find('.ioa-proceed[data-action="wpio_revert"]').trigger('click');
        }
    });
    $('#doaction2').click(function() {
        if ($('#bulk-action-selector-bottom').val() === 'optimize_selected') {
            $('#the-list').find('input[name="image[]"]:checked').parents('tr').find('.ioa-proceed[data-action="wpio_optimize"]').trigger('click');
        } else if ($('#bulk-action-selector-bottom').val() === 'revert_selected') {
            $('#the-list').find('input[name="image[]"]:checked').parents('tr').find('.ioa-proceed[data-action="wpio_revert"]').trigger('click');
        }
    });

    callOptimizeAll = function() {
        //init progress bar
        irBox = $("#progress_status");
        if (irBox.length === 0) {
            $("#dooptimizeall").after('<div id="progress_status" style="width: 300px; position: relative;float:left;margin-left: 10px"><section class="progress_wraper">' + $("#progress_init").html() + '</section></div>');
            $("#stopoptimizeall").after('<div id="progress_status" style="width: 300px; position: relative;float:left;margin-left: 10px"><section class="progress_wraper">' + $("#progress_init").html() + '</section></div>');

            irBox = $("#progress_status");

            $('progress').each(function() {
                var max = $(this).val();
                $(this).val(0).animate({ value: max }, { duration: 2000 });
            });
        }

        startTime = Date.now();
        $.post(
            ajaxurl, {
                action: 'wpio_optimize_all'
            },
            function(response) {
                response = $.parseJSON(response);
                if (typeof(response.status) !== 'undefined' && response.status === true) {

                    if (response.datas.totalImagesProcessing) {
                        totalImagesProcessing = response.datas.totalImagesProcessing;
                        addNoticeMsg("The optimization of all your images has been launched! It's running in background so you can use WordPress as usual!");

                    }

                    if ($("#dooptimizeall").length > 0) {
                        setTimeout(function() { window.location.reload(); }, 5000);
                    }

                    checkTimeRemain();
                    // $("#dooptimizeall").attr("id","stopoptimizeall").removeClass("button-primary").prop('value', 'Stop optimization');
                    // initOAButtons();
                    //return true;
                }
                $(this).removeClass('clicked');
            }
        )

    }

    initOAButtons = function() {
        $('#dooptimizeall').unbind("click").click(function() {
            if ($(this).hasClass('clicked')) {
                return; //avoid double click
            }
            $(this).addClass('clicked');
            //turn on optimize all flag
            $.post(
                ajaxurl, {
                    action: 'wpio_optimize_all_on'
                },
                function(response) {}
            )

            callOptimizeAll();
        });
        $('#stopoptimizeall').unbind("click").click(function() {
            stopOptimizeAll();
        });
    }

    function stopOptimizeAll() {
        $.post(
            ajaxurl, {
                action: 'wpio_stop_optimize_all'
            },
            function(response) {
                response = $.parseJSON(response);

                $("#stopoptimizeall").attr("id", "dooptimizeall").addClass("button-primary").prop('value', 'Optimize all');
                initOAButtons();
                clearTimeout(checkTimeRemainTm);
                $("#wpio_processstatus").html("Stopped");
                window.location.reload();
            }
        )
    }

    initOAButtons();
    var checkTimeRemainTm;
    checkTimeRemain = function() {
        if ($("#wpio_processstatus").length == 0) {
            $("#dooptimizeall").after('<span class="spinner" id="wpio_processspinner" style="visibility:visible;"></span><span id="wpio_processstatus"></span>');
        }

        $.post(
            ajaxurl, {
                action: 'wpio_queue_count'
            },
            function(response) {
                response = $.parseJSON(response);
                if (typeof(response.status) !== 'undefined' && response.status === true) {

                    remainTimeStr = "";

                    if (typeof(response.datas.remainFiles) != 'undefined') {
                        processedImages = 0;
                        curTime = Date.now();
                        processedImages = totalImagesProcessing - response.datas.remainFiles;
                        if (processedImages > 0) {
                            remainTime = (curTime - startTime) / processedImages * response.datas.remainFiles;
                            remainTimeStr = toHHMMSS(Math.floor(remainTime / 1000));
                            $("#wpio_processspinner").css("visibility", "hidden");
                        } else {
                            remainTimeStr = "...";
                        }
                    }

                    $('#progress_status span').html('Processing ...' + processedImages + ' / ' + totalImagesProcessing + ' images');

                    var percent = (processedImages / totalImagesProcessing) * 100;
                    var oldVal = $('#progress_status progress').val();
                    $('#progress_status progress').val(percent);
                    $('#progress_status progress').val(oldVal).animate({ value: percent }, { duration: 500 });

                    $("#progress_status .progress_wraper .timeRemain").fadeOut();
                    $("#progress_status .progress_wraper .timeRemain").html('Remaining ' + remainTimeStr + ' before finished');
                    $("#progress_status .progress_wraper .timeRemain").fadeIn();
                    if (typeof(response.datas.remainFiles) != 'undefined' && response.datas.remainFiles == 0) {
                        $("#wpio_processstatus").html("done");
                        $("#wpio_processspinner").css("visibility", "hidden");
                        setTimeout(function() { window.location.reload() }, 1200);
                        return;
                    }
                    checkTimeRemainTm = setTimeout(function() { checkTimeRemain() }, 5000);
                    return true;
                }
            }
        )


    }

    //if background process is running
    if ($("#stopoptimizeall").length > 0) {
        callOptimizeAll();
    }

    toHHMMSS = function(sec_num) {
        var hours = Math.floor(sec_num / 3600);
        var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
        var seconds = sec_num - (hours * 3600) - (minutes * 60);

        if (minutes < 10) { minutes = "0" + minutes; }
        if (seconds < 10) { seconds = "0" + seconds; }
        var time = '';
        if (hours == 0) {
            time = minutes + 'm ' + seconds + 's';
        } else {
            if (hours < 10) { hours = "0" + hours; }
            time = hours + 'h ' + minutes + 'm ' + seconds + 's';
        }

        return time;
    }

    addNoticeMsg = function(msg) {

        if (typeof wpio_dismiss_optimizeAll != 'undefined') return;
        $firstHeading = $('#wpbody-content h1:first');

        $('<div id="message" class="updated notice notice-success is-dismissible below-h2"><p>' + msg + '</p></div>').insertAfter($firstHeading);
        // Make notices dismissible
        $('.notice.is-dismissible').each(function() {
            var $this = $(this),
                $button = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>'),
                btnText = commonL10n.dismiss || '';

            // Ensure plain text
            $button.find('.screen-reader-text').text(btnText);

            $this.append($button);

            $button.on('click.wp-dismiss-notice', function(event) {
                event.preventDefault();

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    dataType: 'json',
                    data: {
                        action: 'wpio_dismiss_optimizeAll_notice'
                    }
                })

                $this.fadeTo(100, 0, function() {
                    $(this).slideUp(100, function() {
                        $(this).remove();
                    });
                });
            });
        });
    }

    if (typeof $.qtip != 'undefined') {
        $('.wpio-wrap .wpio-tooltip').qtip({
            content: {
                attr: 'alt'
            },
            position: {
                my: 'bottom left',
                at: 'top center'
            },
            style: {
                tip: {
                    corner: true
                },
                classes: 'wpio-qtip qtip-rounded'
            },
            show: 'hover',
            hide: {
                fixed: true,
                delay: 100
            }
        });
    }

    if ($("#wpio_api_optimization_status_val").length > 0) {
        $("#wpio_api_optimization_status_val").change(function(e) {

            if ($(this).is(':checked')) {
                var action = "wpio_enable_optimization";
            } else {
                var action = "wpio_disable_optimization";
            }

            $.ajax({
                url: ajaxurl,
                data: { action: action },
                type: 'post',
                dataType: 'json',
                success: function(response) {
                    //do nothing

                }
            })
        })
    }

    disable_optimization = function() {
        if (typeof wpio_disable_optimization != 'undefined' && wpio_disable_optimization) {
            $(".ioa-proceed").prop('disabled', true).addClass('disabled');
            $("#doaction").prop('disabled', true);
            $("#dooptimizeall").prop('disabled', true);

        }

        return;
    }
    disable_optimization();

    scan_images = function(datas) {
        if (typeof datas == 'undefined') {
            datas = null;
        }
        if ($("#wpio_processstatus").length == 0) {
            $("#dooptimizeall").after('<span class="spinner" id="wpio_processspinner" style="visibility:visible;"></span><span id="wpio_processstatus"></span>');
        }
        $.ajax({
            url: ajaxurl,
            data: { action: 'wpio_scan_images', datas: datas },
            type: 'post',
            dataType: 'json',
            success: function(res) {
                if (res.status === true) {
                    processedImages = parseInt(res.datas.processedImages);
                    var oldVal = $('#progress_status progress').val();
                    var percent = oldVal + (processedImages / totalImagesProcessing) * 100;
                    if (res.datas.continue === true) {
                        $('#progress_status progress').val(percent);
                        $('#progress_status progress').val(oldVal).animate({ value: percent }, { duration: 500 });
                        scan_images(res.datas);
                    } else {
                        //finish scan
                        $('#progress_status progress').val(percent);
                        $('#progress_status progress').val(oldVal).animate({ value: percent }, { duration: 500 });
                        setTimeout(function() {
                            $("#wpio_processstatus").html("Indexation finished");
                            $("#wpio_processspinner").css("visibility", "hidden");
                            window.location.reload();
                        }, 2200);
                    }
                } else {

                }
            }
        })
    };

    //Index images
    $(".wpio_scan_images").click(function() {
        msg = "Indexing images on the way…";
        totalImagesProcessing = 1000;
        //init progress bar
        irBox = $("#progress_status");
        if (irBox.length === 0) {
            $("#wpio_scan_images").after('<div id="progress_status" style="width: 300px; position:' +
                ' absolute;float:left;margin-left: 95px"><section class="progress_wraper">' + $("#progress_init").html() + '</section></div>');
            irBox = $("#progress_status");

            $('progress').each(function() {
                var max = $(this).val();
                $(this).val(0).animate({ value: max }, { duration: 2000 });
            });
        }

        $('#progress_status span').html(msg);

        $.ajax({
            url: ajaxurl,
            data: { action: 'wpio_count_images' },
            type: 'post',
            dataType: 'json',
            success: function(response) {
                totalImagesProcessing = parseInt(response.datas.total);
                scan_images(totalImagesProcessing);
            }
        })

    })

    //Reinitialize
    $("#wpio_reinitialize").click(function(e) {
        e.preventDefault();
        r = confirm("Are you sure to reinitialize all ImageRecycle database?");
        if (r == true) {
            if ($("#wpio_processstatus").length == 0) {
                $("#wpio_reinitialize").after('<span class="spinner" id="wpio_processspinner" style="visibility:visible;"></span><span id="wpio_processstatus"></span>');
            }

            $.ajax({
                url: ajaxurl,
                data: { action: 'wpio_reinitialize' },
                type: 'post',
                dataType: 'json',
                success: function(response) {
                    scan_images();
                }
            })
        }
    })

    $("#clean_metadata").change(function() {
        $selected = $(this).val();
        if ($selected == "1") {
            $("#preserve_metadata").find('input[type=checkbox]').prop('checked', true);
            $("#preserve_metadata").hide();
        } else if ($selected == "0") {
            $("#preserve_metadata").find('input[type=checkbox]').prop('checked', false);
            $("#preserve_metadata").hide();
        } else {
            $("#preserve_metadata").show();
        }
    });
});


var wpiolist;
(function($) {
    wpiolist = {

            /**
             * Register our triggers
             * 
             * We want to capture clicks on specific links, but also value change in
             * the pagination input field. The links contain all the information we
             * need concerning the wanted page number or ordering, so we'll just
             * parse the URL to extract these variables.
             * 
             * The page number input is trickier: it has no URL so we have to find a
             * way around. We'll use the hidden inputs added in TT_Example_List_Table::display()
             * to recover the ordering variables, and the default paged input added
             * automatically by WordPress.
             */
            init: function() {

                // This will have its utility when dealing with the page number input
                var timer;
                var delay = 500;

                // Pagination links, sortable link
                $('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function(e) {
                    // We don't want to actually follow these links
                    e.preventDefault();
                    // Simple way: use the URL to extract our needed variables
                    var query = this.search.substring(1);

                    var data = {
                        optimized: wpiolist.__query(query, 'optimized') || '',
                        filetype: wpiolist.__query(query, 'filetype') || '',
                        s: wpiolist.__query(query, 's') || '',
                        paged: wpiolist.__query(query, 'paged') || '1',
                        order: wpiolist.__query(query, 'order') || 'asc',
                        orderby: wpiolist.__query(query, 'orderby') || 'filename'
                    };
                    wpiolist.update(data);
                });

                // Page number input
                $('input[name=paged]').on('keyup', function(e) {

                    // If user hit enter, we don't want to submit the form
                    // We don't preventDefault() for all keys because it would
                    // also prevent to get the page number!
                    if (13 == e.which)
                        e.preventDefault();

                    // This time we fetch the variables in inputs
                    var data = {
                        optimized: $('select[name=optimized]').val() || '',
                        filetype: $('select[name=filetype]').val() || '',
                        s: $('input[name=s]').val() || '',
                        paged: parseInt($('input[name=paged]').val()) || '1',
                        order: $('input[name=order]').val() || 'asc',
                        orderby: $('input[name=orderby]').val() || 'title'
                    };

                    // Now the timer comes to use: we wait half a second after
                    // the user stopped typing to actually send the call. If
                    // we don't, the keyup event will trigger instantly and
                    // thus may cause duplicate calls before sending the intended
                    // value
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function() {
                        wpiolist.update(data);
                    }, delay);
                });

                //select all
                $('.wp-list-table input[id^="cb-select-all"]').on('click', function() {
                    if (this.checked) {
                        $('.wp-list-table .check-column input[type="checkbox"]').prop('checked', true);
                    } else {
                        $('.wp-list-table .check-column input[type="checkbox"]').prop('checked', false);
                    }
                });
            },

            /** AJAX call
             * 
             * Send the call and replace table parts with updated version!
             * 
             * @param    object    data The data to pass through AJAX
             */
            update: function(data) {

                $.ajax({
                    // /wp-admin/admin-ajax.php
                    url: ajaxurl,
                    // Add action and nonce to our collected data
                    data: $.extend({
                            _ajax_wpio_nonce: $('#_ajax_wpio_nonce').val(),
                            action: '_ajax_fetch_wpio',
                        },
                        data
                    ),
                    // Handle the successful result
                    success: function(response) {

                        // WP_List_Table::ajax_response() returns json
                        var response = $.parseJSON(response);

                        // Add the requested rows
                        if (response.rows.length)
                            $('#the-list').html(response.rows);
                        // Update column headers for sorting
                        if (response.column_headers.length)
                            $('thead tr, tfoot tr').html(response.column_headers);
                        // Update pagination for navigation
                        if (response.pagination.bottom.length)
                            $('.tablenav.top .tablenav-pages').html($(response.pagination.top).html());
                        if (response.pagination.top.length)
                            $('.tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());

                        // Init back our event handlers
                        wpiolist.init();
                        //Init back Revert & Optimize button
                        initButtons();
                        initQueuedButton();
                    }
                });
            },

            /**
             * Filter the URL Query to extract variables
             * 
             * @see http://css-tricks.com/snippets/javascript/get-url-variables/
             * 
             * @param    string    query The URL query part containing the variables
             * @param    string    variable Name of the variable we want to get
             * 
             * @return   string|boolean The variable value if available, false else.
             */
            __query: function(query, variable) {

                var vars = query.split("&");
                for (var i = 0; i < vars.length; i++) {
                    var pair = vars[i].split("=");
                    if (pair[0] == variable)
                        return pair[1];
                }
                return false;
            },
        }
        // check img indexation auto
    var imgRce_index_auto = $('#imgRce_index_auto').val();
    if (imgRce_index_auto != '1') {
        window.onload = function() {
            $("#wpio_scan_images").click();
        };
        $('#imgRce_index_auto').val('1');
    }
    $('#wpio_api_resize_auto_yes').click(function() {
        $('#wpio_api_resize_status_val').removeAttr('disabled');
    });
    $('#wpio_api_resize_auto_no').click(function() {
        $('#wpio_api_resize_status_val').attr('disabled', 'disabled');
        $('#wpio_api_resize_status_val').removeAttr('checked');
    });


})(jQuery);
const GA_ACCESS_CODE_MODAL_ID = "ga_access_code_modal";
const GA_DEBUG_MODAL_ID = "ga_debug_modal";
const GA_DEBUG_MODAL_CONTENT_ID = "ga_debug_modal_content";
const GA_DEBUG_EMAIL = "ga_debug_email";
const GA_DEBUG_DESCRIPTION = "ga_debug_description";
const GA_ACCESS_CODE_TMP_ID = "ga_access_code_tmp";
const GA_ACCESS_CODE_ID = "ga_access_code";
const GA_FORM_ID = "ga_form";
const GA_MODAL_CLOSE_ID = 'ga_close';
const GA_MODAL_BTN_CLOSE_ID = 'ga_btn_close';
const GA_GOOGLE_AUTH_BTN_ID = 'ga_authorize_with_google_button';
const GA_SAVE_ACCESS_CODE_BTN_ID = 'ga_save_access_code';
const GA_AUTHENTICATION_CODE_ERROR = 'That looks like your Google Analytics Tracking ID. Please enter the authentication token in this space. See here for <a href="https://cl.ly/1y1N1A3h0s1t" target="_blank">a walkthrough</a> of how to do it.';

(function ($) {
    ga_popup = {
        url: '',
        authorize: function (e, url) {
            e.preventDefault();
            ga_popup.url = url;
            $('#' + GA_ACCESS_CODE_MODAL_ID).appendTo("body").show();
            ga_popup.open();
        },
        open: function () {
            const p_width = Math.round(screen.width / 2);
            const p_height = Math.round(screen.height / 2);
            const p_left = Math.round(p_width / 2);
            const p_top = 300;
            window.open(ga_popup.url, 'ga_auth_popup', 'width=' + p_width + ',height='
                + p_height + ',top=' + p_top + ',left=' + p_left);
        },
        saveAccessCode: function (e) {
            e.preventDefault();
            e.target.disabled = 'disabled';
            ga_loader.show();
            const ac_tmp = $('#' + GA_ACCESS_CODE_TMP_ID).val();
            if (ga_popup.validateCode(e, ac_tmp)) {
                $('#' + GA_ACCESS_CODE_ID).val(ac_tmp);
                $('#' + GA_FORM_ID).submit();
            }
        },
        validateCode: function (e, code) {
            if (!code){
                ga_loader.hide();
                $('#' + GA_SAVE_ACCESS_CODE_BTN_ID).removeAttr('disabled');
                return false;
            }
            else if (code.substring(0, 2) == 'UA'){
                $('#ga_code_error').show().html(GA_AUTHENTICATION_CODE_ERROR);
                ga_loader.hide();
                $('#' + GA_SAVE_ACCESS_CODE_BTN_ID).removeAttr('disabled');
                return false;
            }
            return true;
        }
    };

    ga_modal = {
        hide: function () {
            $('#' + GA_ACCESS_CODE_MODAL_ID).hide();
            $('#' + GA_DEBUG_MODAL_ID).hide();
            ga_loader.hide();
            $('#' + GA_SAVE_ACCESS_CODE_BTN_ID).removeAttr('disabled');
        }
    };

    ga_events = {

      /**
       * Send Demographic data.
       *
       * @param data
       */
      sendDemoData: function(demoData) {

        // Send demographic data.
        $.ajax( {
          url: 'https://platform-api.sharethis.com/v1.0/property?id=' + ga_property_id + '&secret=' + ga_secret_id,
          method: 'PUT',
          async: false,
          contentType: 'application/json; charset=utf-8',
          data: JSON.stringify( {
            "demographics": demoData
          } ),
          success: function( results ) {
          }
        } );
      },
      /**
         * Returns gdpr onboarding config values.
         */
        setGDPRConfig: function(isGDPR) {

          /**
           * Check if ad blocker exists and notify if so.
           */
          $(document).ready(function(){
            if($("#detectadblock").height() > 0) {
            } else {
              $('#adblocker-notice').show();
            }
          });

          if (!isGDPR || undefined === gaGdprConfig) {
            return;
          }

          var config = JSON.parse(gaGdprConfig);

          $('.gdpr-platform input[name="gdpr-enable"]').prop('checked', config['enabled'] === 'true');
          $('#sharethis-publisher-name').val(config['publisher_name']);
          $(`#sharethis-user-type option[value="${config['display']}"]` ).attr('selected',true);
          $(`#sharethis-consent-type option[value="${config['scope']}"]`).attr('selected', true);
          $(`#sharethis-form-color .color[data-value="${config['color']}"]`).addClass('selected');
          $(`#st-language option[value="${config['language']}"]`).attr('selected', true);

          if (undefined !== config['publisher_purposes']) {
            $( "#publisher-purpose .purpose-item input" ).prop('checked', false);

            config['publisher_purposes'].map( ( purpVal ) => {
              var legit = 'true' === purpVal['legitimate_interest'] || true === purpVal['legitimate_interest'];
              var consent = 'false' === purpVal['legitimate_interest'] || false === purpVal['legitimate_interest'];

              $( `#publisher-purpose .purpose-item input[name="purposes[${purpVal.id}]"][value="legitimate"]` ).prop( 'checked', legit );
              $( `#publisher-purpose .purpose-item input[name="purposes[${purpVal.id}]"][value="consent"]` ).prop( 'checked', consent );
            } );
          }

          if (undefined !== config['publisher_restrictions']) {
            $( ".vendor-table-cell-wrapper input" ).prop('checked', false);

            $.map(config['publisher_restrictions'], function (id, venVal ) {
              if(id) {
                $( `input[type="checkbox"][data-id="${venVal}"]` ).prop( 'checked', true );
              }
            } );
          }
        },
        scrollToAnchor: function(aid) {
          var aTag = $("a[name='"+ aid.toLowerCase() +"']");

          $('.vendor-table-body').animate({
            scrollTop: 0
          }, 0).animate({
            scrollTop: aTag.offset().top - 740
          }, 0);
        },

        click: function (selector, callback) {
            $(selector).live('click', callback);
        },
        codeManuallyCallback: function (features_enabled) {
            var checkbox = $('#ga_enter_code_manually');
            if ( features_enabled ) {
                if ( checkbox.is(':checked') ) {
                    if (confirm('Warning: If you enter your Tracking ID manually, Analytics statistics will not be shown.')) {
                        setTimeout(function () {
                            $('#ga_authorize_with_google_button').attr('disabled','disabled').next().show();
                            $('#ga_account_selector').attr('disabled', 'disabled');
                        $('#ga_manually_wrapper').show();
                        }, 350);

                    } else {
                        setTimeout(function () {
                            checkbox.removeProp('checked');
                        }, 350);
                    }
                } else { // disable
                    setTimeout(function () {
                        $('#ga_authorize_with_google_button').removeAttr('disabled').next().hide();
                        $('#ga_account_selector').removeAttr('disabled');
                        $('#ga_manually_wrapper').hide();
                    }, 350);
                }
            }
        },
        initModalEvents: function () {
            $('body').on('click', '#close-review-us', function() {
                var dataObj = {},
                    self = this;
                dataObj['action'] = "ga_ajax_hide_review";
                dataObj[GA_NONCE_FIELD] = GA_NONCE;

                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: ajaxurl,
                    data: dataObj,
                    success: function (response) {
                        $('.ga-review-us').fadeOut();
                    }
                });
            });

            $('#' + GA_GOOGLE_AUTH_BTN_ID).on('click', function () {
                $('#' + GA_ACCESS_CODE_TMP_ID).focus();
            });

            $('#' + GA_MODAL_CLOSE_ID + ', #' + GA_MODAL_BTN_CLOSE_ID + ', #' + GA_DEBUG_MODAL_ID ).on('click', function () {
                ga_modal.hide();
            });

            $( '#copy-debug' ).on( 'click', function() {
                var copiedText = $( '#ga_debug_info' );

	            copiedText.select();
	            document.execCommand( 'copy' );
            } );

            $('#' + GA_DEBUG_MODAL_CONTENT_ID ).click(function(event){
                event.stopPropagation();
            });
        },

      getConfig: function () {
        var config,
          enabled = $('input[name="gdpr-enable"]').is(':checked'),
          publisherPurposes = [],
          display = $( '#sharethis-user-type option:selected' ).val(),
          name = $( '#sharethis-publisher-name' ).val(),
          scope = $( '#sharethis-consent-type option:selected' ).val(),
          color = $( '#sharethis-form-color .color.selected' ).attr('data-value'),
          publisherRestrictions = {},
          language = $( '#st-language' ).val();

        $('#publisher-purpose input:checked').each( function( index, value ) {
          var theId = $(value).attr('data-id'),
            legit = 'consent' !== $(value).val();

          publisherPurposes.push({ 'id': theId, 'legitimate_interest' : legit });
        });

        $('.vendor-table-cell-wrapper label input:checked').each( function( index, value ) {
          var vendorId = $(value).attr('data-id');
          if (vendorId) {
            publisherRestrictions[vendorId] = true;
          }
        });

        config = {
          enabled: enabled,
          display: display,
          publisher_name: name,
          publisher_purposes: publisherPurposes,
          publisher_restrictions: publisherRestrictions,
          language: language,
          color: color,
          scope: scope,
        };

        return config;
      },

      enableGdpr: function () {
        var timer = '';
        this.$gdprContainer = $('.gdpr-platform');

        // New color select.
        this.$gdprContainer.on('click', "#sharethis-form-color .color", function() {
          $('#sharethis-form-color .color').removeClass('selected');
          $(this).addClass('selected');
        });

        // clear or show choices.
        this.$gdprContainer.on('click', '#clear-choices', function(e) {
          e.preventDefault();
          e.stopPropagation();

          $( '.purpose-item input' ).prop( 'checked', false );
        });

        // clear or show choices.
        this.$gdprContainer.on('click', '#see-st-choices', function(e) {
          e.preventDefault();
          e.stopPropagation();
          $('.purpose-item input[name="purposes[1]"]').prop('checked', true);
          $('.purpose-item input[name="purposes[3]"][value="consent"]').prop('checked', true);
          $('.purpose-item input[name="purposes[5]"][value="consent"]').prop('checked', true);
          $('.purpose-item input[name="purposes[6]"][value="consent"]').prop('checked', true);
          $('.purpose-item input[name="purposes[9]"][value="legitimate"]').prop('checked', true);
          $('.purpose-item input[name="purposes[10]"][value="legitimate"]').prop('checked', true);
        });

        // Uncheck radio if click on selected box.
        this.$gdprContainer.on( 'click', '.lever', (e) => {
          e.preventDefault();
          e.stopPropagation();

          const theInput = $( e.currentTarget ).siblings( 'input' );

          if ( theInput.is( ':checked' ) ) {
            $( `input[name="${theInput.attr( 'name' )}"]` ).prop( 'checked', false )
          } else {
            theInput.prop( 'checked', true )
          }
        } );

        // Toggle button menus when arrows are clicked.
        $( 'body' ).on( 'click', '.accor-wrap .accor-tab', function() {
          var type = $( this ).find( 'span.accor-arrow' );

          var closestButton = $( type ).parent( '.accor-tab' ).parent( '.accor-wrap' );

          if ( '►' === type.html() ) {

            // Show the button configs.
            closestButton.find( '.accor-content' ).slideDown();

            // Change the icon next to title.
            closestButton.find( '.accor-arrow' ).html( '&#9660;' );
          } else {

            // Show the button configs.
            closestButton.find( '.accor-content' ).slideUp();

            // Change the icon next to title.
            closestButton.find( '.accor-arrow' ).html( '&#9658;' );
          }
        } );

        $('body').on('click', '.demo-enable-popup .close-demo-modal', function(e) {
          e.preventDefault();
          e.stopPropagation();
          $('.demo-enable-popup').removeClass('engage');
        });

        $('body').on('click', '#demographic-popup', function(e) {
          e.preventDefault();
          e.stopPropagation();
          $('.demo-enable-popup').addClass('engage');
        });

        $('body').on('click', '#enable-demographic, #Enable-demographic', function(e) {
          e.preventDefault();
          e.stopPropagation();
          ga_events.enableDemographic('enable');
        });


        $('body').on('click', '#Disable-demographic', function(e) {
          e.preventDefault();
          e.stopPropagation();
          ga_events.enableDemographic('disable');
        });

        // Enable GDPR tool.
        $('body').on('click', '.gdpr-submit', function(e) {
          e.preventDefault();
          e.stopPropagation();

          var dataObj = {},
            self = this,
            config = ga_events.getConfig();

          theData = JSON.stringify( {
            'secret': ga_secret_id,
            'id': ga_property_id,
            'product': 'gdpr-compliance-tool-v2',
            'config': config
          } );

          // Send new button status value.
          $.ajax( {
            url: 'https://platform-api.sharethis.com/v1.0/property/product',
            method: 'POST',
            async: false,
            contentType: 'application/json; charset=utf-8',
            data: theData,
            success: function( results ) {
            }
          } );

          dataObj['action'] = "ga_ajax_enable_gdpr";
          dataObj['nonce'] = 'true';
          dataObj['config'] = config;

          $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: dataObj,
            success: function (response) {
              window.location.reload();
            }
          });
        });


        // Enable GDPR tool.
        $('body').on('click', '.gdpr-enable', function(e) {
          e.preventDefault();
          e.stopPropagation();

          var dataObj = {},
            self = this,
            config = ga_events.getConfig();

          if ($('body').hasClass('google-analytics_page_googleanalytics-settings')) {
            config = {
              enabled: true,
              display: 'eu',
              publisher_name: '',
              publisher_purposes: [],
              language: 'en',
              color: '',
              scope: 'global',
            };
          }

          theData = JSON.stringify( {
            'secret': ga_secret_id,
            'id': ga_property_id,
            'product': 'gdpr-compliance-tool-v2',
            'config': config
          } );

          // Send new button status value.
          $.ajax( {
            url: 'https://platform-api.sharethis.com/v1.0/property/product',
            method: 'POST',
            async: false,
            contentType: 'application/json; charset=utf-8',
            data: theData,
            success: function( results ) {
            }
          } );

          dataObj['action'] = "ga_ajax_enable_gdpr";
          dataObj['nonce'] = 'true';
          dataObj['config'] = config;

          $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: dataObj,
            success: function (response) {
              window.location.href = siteAdminUrl + 'admin.php?page=googleanalytics%2Fgdpr';
            }
          });
        });

        // Scroll to anchor in vendor list.
        // Send user input to category search AFTER they stop typing.
        $('body').on( 'keyup', '.vendor-search input', function( e ) {
          clearTimeout( timer );

          timer = setTimeout( function() {
            ga_events.scrollToAnchor($(this).val());
          }.bind( this, ga_events ), 500 );
        } );
      },

      enableDemographic: function(disable) {
        var dataObj = {};

        dataObj['action'] = "ga_ajax_enable_demographic";
        dataObj['nonce'] = ga_demo_nonce;
        dataObj['enabled'] = 'disable' === disable ? 'false' : 'true';

        $.ajax({
          type: "post",
          dataType: "json",
          url: ajaxurl,
          data: dataObj,
          success: function (response) {
            window.location.reload();
          }
        });
      }
    };

    /**
     * Handles "disable all features" switch button
     * @type {{init: ga_switcher.init}}
     */
    ga_switcher = {
        init: function (state) {
            var checkbox = $("#ga-disable");

            if (state) {
                checkbox.prop('checked', 'checked');
            } else {
                checkbox.removeProp('checked');
            }

            $(".ga-slider-disable").on("click", function (e) {
                var manually_enter_not_checked = $('#ga_enter_code_manually').not(':checked');
                if (checkbox.not(':checked').length > 0) {
                    if (confirm('This will disable Dashboards and Google API')) {
                        setTimeout(function () {
                            window.location.href = GA_DISABLE_FEATURE_URL;
                        }, 350);
                    } else {
                        setTimeout(function () {
                            checkbox.removeProp('checked');
                        }, 350);
                    }
                } else { // disable
                    setTimeout(function () {
                        window.location.href = GA_ENABLE_FEATURE_URL;
                    }, 350);
                }
            });
        }
    };

    $(document).ready(function () {
        ga_events.initModalEvents();
        ga_events.enableGdpr();
        ga_events.setGDPRConfig($('body').hasClass('google-analytics_page_googleanalytics-gdpr'));
    });

    const offset = 50;
    const minWidth = 350;
    const wrapperSelector = '#ga-stats-container';
    const chartContainer = 'chart_div';
    const demoChartGenderContainer = 'demo_chart_gender_div';
    const demoChartAgeContainer = 'demo_chart_age_div';

    ga_charts = {

        init: function (callback) {
            $(document).ready(function () {
                google.charts.load('current', {
                    'packages': ['corechart']
                });
                ga_loader.show();
                google.charts.setOnLoadCallback(callback);
            });
        },
        createTooltip: function (day, pageviews) {
            return '<div style="padding:10px;width:100px;">' + '<strong>' + day
                + '</strong><br>' + 'Pageviews:<strong> ' + pageviews
                + '</strong>' + '</div>';
        },
        events: function (data) {
            $(window).on('resize', function () {
                ga_charts.drawChart(data, ga_tools.recomputeChartWidth(minWidth, offset, wrapperSelector));
            });
        },
        drawChart: function (data, chartWidth) {

            if (typeof chartWidth == 'undefined') {
                chartWidth = ga_tools.recomputeChartWidth(minWidth, offset, wrapperSelector);
            }

            const options = {
                /*title : 'Page Views',*/
                lineWidth: 5,
                pointSize: 10,
                tooltip: {
                    isHtml: true
                },
                legend: {
                    position: (ga_tools.getCurrentWidth(wrapperSelector) <= minWidth ? 'top'
                        : 'top'),
                    maxLines: 5,
                    alignment: 'start',
                    textStyle: {color: '#000', fontSize: 12}
                },
                colors: ['#4285f4'],
                hAxis: {
                    title: 'Day',
                    titleTextStyle: {
                        color: '#333'
                    }
                },
                vAxis: {
                    minValue: 0
                },
                width: chartWidth,
                height: 500,
                chartArea: {
                    top: 50,
                    left: 50,
                    right: 30,
                    bottom: 100
                },
            };
            var chart = new google.visualization.AreaChart(document
                .getElementById(chartContainer));
            chart.draw(data, options);
        },
      drawDemoGenderChart: function (data, chartWidth) {

        if (typeof chartWidth == 'undefined') {
          chartWidth = ga_tools.recomputeChartWidth(minWidth, offset, wrapperSelector);
        }

        var data = google.visualization.arrayToDataTable(data);

        var options = {
          title: 'Gender'
        };

        var chart = new google.visualization.PieChart(document.getElementById(demoChartGenderContainer));

        chart.draw(data, options);
      },

      drawDemoAgeChart: function (data, chartWidth) {

        if (typeof chartWidth == 'undefined') {
          chartWidth = ga_tools.recomputeChartWidth(minWidth, offset, wrapperSelector);
        }

        var data = google.visualization.arrayToDataTable(data);

        var options = {
          title: 'Age',
          chartArea: {width: '50%'},
          hAxis: {
            minValue: 0
          },
        };

        var chart = new google.visualization.BarChart(document.getElementById(demoChartAgeContainer));

        chart.draw(data, options);
      }
    };
    ga_debug = {
        url: '',
        open_modal: function (e) {
            e.preventDefault();
            $('#' + GA_DEBUG_MODAL_ID).appendTo("body").show();
            $('#ga-send-debug-email').removeAttr('disabled');
            $('#ga_debug_error').hide();
            $('#ga_debug_success').hide();
        },
        send_email: function (e) {
            e.preventDefault();
            ga_loader.show();
            var dataObj = {};
            dataObj['action'] = "googleanalytics_send_debug_email";
            dataObj['email'] = $('#' + GA_DEBUG_EMAIL).val();
            dataObj['description'] = $('#' + GA_DEBUG_DESCRIPTION).val();
            $.ajax({
                type: "post",
                dataType: "json",
                url: ajaxurl,
                data: dataObj,
                success: function (response) {
                    ga_loader.hide();
                    if (typeof response.error !== "undefined") {
                        $('#ga_debug_error').show().html(response.error);
                    } else if (typeof response.success !== "undefined"){
                        $('#ga_debug_error').hide();
                        $('#ga-send-debug-email').attr('disabled','disabled');
                        $('#ga_debug_success').show().html(response.success);
                    }
                }
            });
        }
    };
})(jQuery);

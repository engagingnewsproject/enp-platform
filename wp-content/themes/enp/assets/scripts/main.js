/* ========================================================================
 * DOM-based Routing
 * Based on http://goo.gl/EUTi53 by Paul Irish
 *
 * Only fires on body classes that match. If a body class contains a dash,
 * replace the dash with an underscore when adding it to the object below.
 *
 * .noConflict()
 * The routing is enclosed within an anonymous function so that you can
 * always reference jQuery with $, even when in .noConflict() mode.
 * ======================================================================== */

(function($) {
  // Use this variable to set up the common and page specific functions. If you
  // rename this variable, you will also need to rename the namespace below.
  var Sage = {
    // All pages
    'common': {
      init: function() {
        // JavaScript to be fired on all pages
      },
      finalize: function() {
        // JavaScript to be fired on all pages, after page specific JS is fired
      }
    },
    // Home page
    'home': {
      init: function() {
        // JavaScript to be fired on the home page
      },
      finalize: function() {
        // JavaScript to be fired on the home page, after the init JS
      }
    },
    // About us page, note the change from about-us to about_us.
    'about_us': {
      init: function() {
        // JavaScript to be fired on the about us page
      }
    }
  };

  // The routing fires all common scripts, followed by the page specific scripts.
  // Add additional events for more control over timing e.g. a finalize event
  var UTIL = {
    fire: function(func, funcname, args) {
      var fire;
      var namespace = Sage;
      funcname = (funcname === undefined) ? 'init' : funcname;
      fire = func !== '';
      fire = fire && namespace[func];
      fire = fire && typeof namespace[func][funcname] === 'function';

      if (fire) {
        namespace[func][funcname](args);
      }
    },
    loadEvents: function() {
      // Fire common init JS
      UTIL.fire('common');

      // Fire page-specific init JS, and then finalize JS
      $.each(document.body.className.replace(/-/g, '_').split(/\s+/), function(i, classnm) {
        UTIL.fire(classnm);
        UTIL.fire(classnm, 'finalize');
      });

      // Fire common finalize JS
      UTIL.fire('common', 'finalize');
    }
  };

  // Load Events
  $(document).ready(UTIL.loadEvents);

})(jQuery); // Fully reference jQuery after this point.


(function($) {

  // Add additional events for more control over timing e.g. a finalize event
  var Collapse = {
    init: function() {
      // look for all collapse buttons and set click listener on them
      $(document).on('click', '[data-toggle="collapse"]', function() {
          Collapse.click(this);
      });

    },
    click: function(button) {
      console.log('click!', button);
      // get the items
      var target = $(button).data('target');
      // call toggle on each item
      $(target).each(function() {
          Collapse.toggle(this);
      });
    },
    toggle: function(el) {
        console.log('toggle', el);
        if($(el).hasClass('show')) {
            Collapse.hide(el);
        } else {
            Collapse.show(el);
        }
    },
    show: function(el) {
        console.log('show', el);
        $(el).addClass('show');
        $(el).removeClass('hide');
        $(el).removeClass('hiding');

        Collapse.ariaShow(el);
        $(el).addClass('showing');
        setTimeout(function() {
            $(el).removeClass('showing');
        }, 500);
    },
    hide: function(el) {
        console.log('hide', el);
        $(el).removeClass('show');
        $(el).removeClass('showing');
        
        $(el).addClass('hide');
        Collapse.ariaHidden(el);
        $(el).addClass('hiding');
        setTimeout(function() {
            $(el).removeClass('hiding');
        }, 900);

    },
    ariaShow: function(el) {
        console.log('ariaShow', el);
        $(el).attr('aria-hidden', false);
    },
    ariaHidden: function(el) {
        console.log('ariaHidden', el);
        $(el).attr('aria-hidden', true);
    },
  };

  // Load Events
  $(document).ready(Collapse.init);

})(jQuery); // Fully reference jQuery after this point.

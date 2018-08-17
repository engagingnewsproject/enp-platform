webpackJsonp([0],{

/***/ 6:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Collapse = function () {
  function Collapse(button, el) {
    _classCallCheck(this, Collapse);

    this.button = button;
    this.el = el;
    this.init();
  }

  _createClass(Collapse, [{
    key: 'init',
    value: function init() {
      // look for all collapse buttons and close them and set click listener on them
      this.button.addEventListener('click', this.click.bind(this));

      /* $(document).on('click', '[data-toggle="collapse"]', function() {
           Collapse.click(this)
       })*/
    }
  }, {
    key: 'click',
    value: function click() {
      console.log('click');
      // toggle the button state
      this.toggleButton();
      this.toggle();
    }
  }, {
    key: 'toggleButton',
    value: function toggleButton() {
      var _this = this;

      if (this.button.classList.contains('is-open')) {
        this.button.classList.remove(['is-opening', 'is-open']);
        this.button.classList.add(['is-closing', 'is-closed']);
        setTimeout(function () {
          _this.button.classList.remove('is-closing');
        }, 600);
      } else {
        this.button.classList.remove(['is-closing', 'is-closed']);
        this.button.classList.add(['is-opening', 'is-open']);
        setTimeout(function () {
          _this.button.classList.remove('is-opening');
        }, 600);
      }
    }
  }, {
    key: 'toggle',
    value: function toggle() {
      console.log('toggle', this.el);
      if (this.el.classList.contains('is-open')) {
        this.hide();
      } else {
        this.show();
      }
    }
  }, {
    key: 'show',
    value: function show() {
      var _this2 = this;

      this.el.classList.add('is-open');
      this.el.classList.remove('is-hidden');
      this.el.classList.remove('is-hiding');

      this.ariaShow();
      this.el.classList.add('is-opening');
      setTimeout(function () {
        _this2.el.classList.remove('is-opening');
      }, 600);
    }
  }, {
    key: 'hide',
    value: function hide() {
      var _this3 = this;

      this.el.classList.remove(['is-open', 'is-opening']);
      this.el.classList.add(['is-hidden', 'is-hiding']);
      this.ariaHidden();
      setTimeout(function () {
        _this3.el.classList.remove('is-hiding');
      }, 600);
    }
  }, {
    key: 'ariaShow',
    value: function ariaShow() {
      console.log('ariaShow', el);

      this.el.setAttribute("aria-hidden", false);
    }
  }, {
    key: 'ariaHidden',
    value: function ariaHidden() {
      console.log('ariaHidden', el);
      this.el.setAttribute("aria-hidden", true);
    }
  }]);

  return Collapse;
}();

/* harmony default export */ __webpack_exports__["default"] = (Collapse);

/***/ })

});
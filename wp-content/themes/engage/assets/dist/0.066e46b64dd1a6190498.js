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
        var _button$classList, _button$classList2;

        (_button$classList = this.button.classList).remove.apply(_button$classList, ['is-opening', 'is-open']);
        (_button$classList2 = this.button.classList).add.apply(_button$classList2, ['is-closing', 'is-closed']);
        setTimeout(function () {
          _this.button.classList.remove('is-closing');
        }, 600);
      } else {
        var _button$classList3, _button$classList4;

        (_button$classList3 = this.button.classList).remove.apply(_button$classList3, ['is-closing', 'is-closed']);
        (_button$classList4 = this.button.classList).add.apply(_button$classList4, ['is-opening', 'is-open']);
        setTimeout(function () {
          _this.button.classList.remove('is-opening');
        }, 600);
      }
    }
  }, {
    key: 'toggle',
    value: function toggle() {
      if (this.el.classList.contains('is-open')) {
        this.hide();
      } else {
        this.show();
      }
    }
  }, {
    key: 'show',
    value: function show() {
      var _el$classList,
          _this2 = this;

      this.el.classList.add('is-open');
      (_el$classList = this.el.classList).remove.apply(_el$classList, ['is-hidden', 'is-hiding']);

      this.ariaShow();
      this.el.classList.add('is-opening');
      setTimeout(function () {
        _this2.el.classList.remove('is-opening');
      }, 600);
    }
  }, {
    key: 'hide',
    value: function hide() {
      var _el$classList2,
          _el$classList3,
          _this3 = this;

      (_el$classList2 = this.el.classList).remove.apply(_el$classList2, ['is-open', 'is-opening']);
      (_el$classList3 = this.el.classList).add.apply(_el$classList3, ['is-hidden', 'is-hiding']);
      this.ariaHidden();
      setTimeout(function () {
        _this3.el.classList.remove('is-hiding');
      }, 600);
    }
  }, {
    key: 'ariaShow',
    value: function ariaShow() {
      this.el.setAttribute("aria-hidden", false);
    }
  }, {
    key: 'ariaHidden',
    value: function ariaHidden() {
      this.el.setAttribute("aria-hidden", true);
    }
  }]);

  return Collapse;
}();

/* harmony default export */ __webpack_exports__["default"] = (Collapse);

/***/ })

});
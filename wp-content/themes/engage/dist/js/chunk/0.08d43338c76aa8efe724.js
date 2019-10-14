webpackJsonp([0],[
/* 0 */,
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Collapse = function () {
  function Collapse(button, els) {
    _classCallCheck(this, Collapse);

    this.button = button;
    this.els = els;
    this.onClick = this.click.bind(this);

    // We need collapse.js for the navbar burger toggle thing
    this.init();
  }

  _createClass(Collapse, [{
    key: 'init',
    value: function init() {
      // look for all collapse buttons and close them and set click listener on them
      this.button.addEventListener('click', this.onClick);
      // setup initial classes
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = this.els[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var el = _step.value;

          el.classList.add('is-hidden');
          this.ariaHidden(el);
        }
      } catch (err) {
        _didIteratorError = true;
        _iteratorError = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion && _iterator.return) {
            _iterator.return();
          }
        } finally {
          if (_didIteratorError) {
            throw _iteratorError;
          }
        }
      }

      this.button.classList.add('is-closed');
      /* $(document).on('click', '[data-toggle="collapse"]', function() {
           Collapse.click(this)
       })*/
    }
  }, {
    key: 'destroy',
    value: function destroy() {
      var _iteratorNormalCompletion2 = true;
      var _didIteratorError2 = false;
      var _iteratorError2 = undefined;

      try {
        for (var _iterator2 = this.els[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
          var el = _step2.value;

          el.classList.remove('is-hidden');
          el.classList.remove('is-open');
          el.removeAttribute("aria-hidden");
        }
      } catch (err) {
        _didIteratorError2 = true;
        _iteratorError2 = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion2 && _iterator2.return) {
            _iterator2.return();
          }
        } finally {
          if (_didIteratorError2) {
            throw _iteratorError2;
          }
        }
      }

      this.button.classList.remove('is-closed');
      this.button.classList.remove('is-open');
      this.button.removeEventListener('click', this.onClick);
    }
  }, {
    key: 'click',
    value: function click(e) {
      // TODO: we need to do a prev default ONLY if it is larger than our MQ
      if (window.innerWidth < 800) {
        e.preventDefault();
      }

      if (this.button.tagName === 'A') {
        if (this.button.classList.contains('is-open')) {
          // send them on their way
          window.location = this.button.getAttribute('href');
        }
      }
      // toggle the button state
      this.toggleButton();

      var _iteratorNormalCompletion3 = true;
      var _didIteratorError3 = false;
      var _iteratorError3 = undefined;

      try {
        for (var _iterator3 = this.els[Symbol.iterator](), _step3; !(_iteratorNormalCompletion3 = (_step3 = _iterator3.next()).done); _iteratorNormalCompletion3 = true) {
          var el = _step3.value;

          this.toggle(el);
        }
      } catch (err) {
        _didIteratorError3 = true;
        _iteratorError3 = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion3 && _iterator3.return) {
            _iterator3.return();
          }
        } finally {
          if (_didIteratorError3) {
            throw _iteratorError3;
          }
        }
      }
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
    value: function toggle(el) {
      if (el.classList.contains('is-open')) {
        this.hide(el);
      } else {
        this.show(el);
      }
    }
  }, {
    key: 'show',
    value: function show(el) {
      var _el$classList;

      el.classList.add('is-open');
      (_el$classList = el.classList).remove.apply(_el$classList, ['is-hidden', 'is-hiding']);

      this.ariaShow(el);
      el.classList.add('is-opening');
      setTimeout(function () {
        el.classList.remove('is-opening');
      }, 600);
    }
  }, {
    key: 'hide',
    value: function hide(el) {
      var _el$classList2, _el$classList3;

      (_el$classList2 = el.classList).remove.apply(_el$classList2, ['is-open', 'is-opening']);
      (_el$classList3 = el.classList).add.apply(_el$classList3, ['is-hidden', 'is-hiding']);
      this.ariaHidden(el);
      setTimeout(function () {
        el.classList.remove('is-hiding');
      }, 600);
    }
  }, {
    key: 'ariaShow',
    value: function ariaShow(el) {
      el.setAttribute("aria-hidden", false);
    }
  }, {
    key: 'ariaHidden',
    value: function ariaHidden(el) {
      el.setAttribute("aria-hidden", true);
    }
  }]);

  return Collapse;
}();

/* harmony default export */ __webpack_exports__["default"] = (Collapse);

/***/ })
]);
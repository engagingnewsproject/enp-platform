(self["webpackChunkengage"] = self["webpackChunkengage"] || []).push([["assets_js_collapse_js"],{

/***/ "./assets/js/collapse.js":
/*!*******************************!*\
  !*** ./assets/js/collapse.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var Collapse = /*#__PURE__*/function () {
  function Collapse(button, els) {
    _classCallCheck(this, Collapse);

    this.button = button;
    this.els = els;
    this.onClick = this.click.bind(this); // We need collapse.js for the navbar burger toggle thing

    this.init();
  }

  _createClass(Collapse, [{
    key: "init",
    value: function init() {
      // look for all collapse buttons and close them and set click listener on them
      this.button.addEventListener('click', this.onClick); // setup initial classes

      var _iterator = _createForOfIteratorHelper(this.els),
          _step;

      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var el = _step.value;
          el.classList.add('is-hidden');
          this.ariaHidden(el);
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }

      this.button.classList.add('is-closed');
      /* $(document).on('click', '[data-toggle="collapse"]', function() {
           Collapse.click(this)
       })*/
    }
  }, {
    key: "destroy",
    value: function destroy() {
      var _iterator2 = _createForOfIteratorHelper(this.els),
          _step2;

      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var el = _step2.value;
          el.classList.remove('is-hidden');
          el.classList.remove('is-open');
          el.removeAttribute("aria-hidden");
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }

      this.button.classList.remove('is-closed');
      this.button.classList.remove('is-open');
      this.button.removeEventListener('click', this.onClick);
    }
  }, {
    key: "click",
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
      } // toggle the button state


      this.toggleButton();

      var _iterator3 = _createForOfIteratorHelper(this.els),
          _step3;

      try {
        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
          var el = _step3.value;
          this.toggle(el);
        }
      } catch (err) {
        _iterator3.e(err);
      } finally {
        _iterator3.f();
      }
    }
  }, {
    key: "toggleButton",
    value: function toggleButton() {
      var _this = this;

      if (this.button.classList.contains('is-open')) {
        var _this$button$classLis, _this$button$classLis2;

        (_this$button$classLis = this.button.classList).remove.apply(_this$button$classLis, ['is-opening', 'is-open']);

        (_this$button$classLis2 = this.button.classList).add.apply(_this$button$classLis2, ['is-closing', 'is-closed']);

        setTimeout(function () {
          _this.button.classList.remove('is-closing');
        }, 600);
      } else {
        var _this$button$classLis3, _this$button$classLis4;

        (_this$button$classLis3 = this.button.classList).remove.apply(_this$button$classLis3, ['is-closing', 'is-closed']);

        (_this$button$classLis4 = this.button.classList).add.apply(_this$button$classLis4, ['is-opening', 'is-open']);

        setTimeout(function () {
          _this.button.classList.remove('is-opening');
        }, 600);
      }
    }
  }, {
    key: "toggle",
    value: function toggle(el) {
      if (el.classList.contains('is-open')) {
        this.hide(el);
      } else {
        this.show(el);
      }
    }
  }, {
    key: "show",
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
    key: "hide",
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
    key: "ariaShow",
    value: function ariaShow(el) {
      el.setAttribute("aria-hidden", false);
    }
  }, {
    key: "ariaHidden",
    value: function ariaHidden(el) {
      el.setAttribute("aria-hidden", true);
    }
  }]);

  return Collapse;
}();

/* harmony default export */ __webpack_exports__["default"] = (Collapse);

/***/ })

}]);
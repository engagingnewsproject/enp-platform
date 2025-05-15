"use strict";
(self["webpackChunkengage_2_x"] = self["webpackChunkengage_2_x"] || []).push([["assets_js_components_Orbit_js"],{

/***/ "./assets/js/components/Orbit.js":
/*!***************************************!*\
  !*** ./assets/js/components/Orbit.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
// Original JavaScript code by Chirp Internet: www.chirp.com.au
// Please acknowledge use of this code by including this header.
// Modified very, very heavily by Jeremy Jones: https://jeremyjon.es
var Orbit = /*#__PURE__*/function () {
  function Orbit() {
    _classCallCheck(this, Orbit);
    this.field = document.getElementById('orbit-field');
    this.ballsEl = document.getElementById('orbit-balls');
    this.gravitationalPull = 80;
    this.gravityText = document.getElementById('orbit-current-gravity');
    this.increasePullBtn = document.getElementById('orbit-increase-pull');
    this.decreasePullBtn = document.getElementById('orbit-decrease-pull');
    this.toggleAnimateBtn = document.getElementById('orbit-toggle-animate');
    this.balls = null;
    this.animate = true;
    this.ballSettings = {
      num: 80,
      minSize: 4,
      maxSize: 12
    };
    this.start = 0;
    this.init(80);
  }
  return _createClass(Orbit, [{
    key: "init",
    value: function init(ballsNum) {
      this.balls = this.createBalls(ballsNum);
      window.requestAnimationFrame(this.step.bind(this));
      this.setPullButtons(this.gravitationalPull);
      this.increasePullBtn.addEventListener('click', this.increaseGravitationalPull.bind(this));
      this.decreasePullBtn.addEventListener('click', this.decreaseGravitationalPull.bind(this));
      this.toggleAnimateBtn.addEventListener('click', this.toggleAnimate.bind(this));

      // uncomment to have planet track cursor
      // document.onmousemove = getCursorXY;
    }
  }, {
    key: "createBalls",
    value: function createBalls() {
      var size;
      for (var i = 0; i < this.ballSettings.num; i++) {
        // get random size between setting sizes
        size = Math.ceil(this.ballSettings.minSize + Math.random() * (this.ballSettings.maxSize - this.ballSettings.minSize));
        this.createBall(size);
      }

      // return all the balls
      return document.querySelectorAll('.orbit-ball');
    }
  }, {
    key: "createBall",
    value: function createBall(size) {
      var newBall, stretchDir;
      newBall = document.createElement("div");
      stretchDir = Math.round(Math.random() * 1) ? 'x' : 'y';
      newBall.classList.add('orbit-ball');
      newBall.style.width = size + 'px';
      newBall.style.height = size + 'px';
      newBall.style.background = this.getRandomColor();
      newBall.setAttribute('data-stretch-dir', stretchDir); // either x or y

      // TODO: Decrease the 'data-stretch-val' attribute to decrease the spread of the balls
      newBall.setAttribute('data-stretch-val', 1 + Math.random() * 5);
      newBall.setAttribute('data-grid', this.field.offsetWidth + Math.round(Math.random() * 100)); // min orbit = 30px, max 130
      newBall.setAttribute('data-duration', 3.5 + Math.round(Math.random() * 8)); // min duration = 3.5s, max 8s
      newBall.setAttribute('data-start', 0);
      this.ballsEl.appendChild(newBall);
    }
  }, {
    key: "callStep",
    value: function callStep(timestamp) {
      return this.step(timestamp);
    }
  }, {
    key: "step",
    value: function step(timestamp) {
      var progress, x, y, stretch, gridSize, duration, start, xPos, yPos;
      for (var i = 0; i < this.balls.length; i++) {
        start = this.balls[i].getAttribute('data-start');
        if (start == 0) {
          start = timestamp;
          this.balls[i].setAttribute('data-start', start);
        }
        gridSize = this.balls[i].getAttribute('data-grid');
        duration = this.balls[i].getAttribute('data-duration');
        progress = (timestamp - start) / duration / 1000; // percent
        stretch = this.balls[i].getAttribute('data-stretch-val');
        if (this.balls[i].getAttribute('data-stretch-dir') === 'x') {
          x = stretch * Math.sin(progress * 2 * Math.PI) * (1.05 - this.gravitationalPull / 100); // x = ƒ(t)
          y = Math.cos(progress * 2 * Math.PI); // y = ƒ(t)
        } else {
          x = Math.sin(progress * 2 * Math.PI); // x = ƒ(t)
          y = stretch * Math.cos(progress * 2 * Math.PI) * (1.05 - this.gravitationalPull / 100); // y = ƒ(t)
        }
        xPos = this.field.clientWidth / 2 + gridSize * x;
        yPos = this.field.clientHeight / 2 + gridSize * y;
        this.balls[i].style.transform = 'translate3d(' + xPos + 'px, ' + yPos + 'px, 0)';

        // if these are true, then it's behind the planet
        if (this.balls[i].getAttribute('data-stretch-dir') === 'x' && (this.field.offsetWidth / 2 - this.balls[i].offsetWidth) * -1 < xPos && xPos < this.field.offsetWidth / 2 + this.balls[i].offsetWidth || this.balls[i].getAttribute('data-stretch-dir') === 'y' && (this.field.offsetWidth / 2 - this.balls[i].offsetWidth) * -1 < yPos && yPos < this.field.offsetWidth / 2 + this.balls[i].offsetWidth) {
          // backside of the moon
          this.balls[i].style.zIndex = '-1';
        } else {
          // ...front side of the moon
          this.balls[i].style.zIndex = '9';
        }
        if (progress >= 1) {
          this.balls[i].setAttribute('data-start', 0); // reset to start position
        }
      }
      if (this.animate == true) {
        window.requestAnimationFrame(this.step.bind(this));
      }
    }
  }, {
    key: "toggleAnimate",
    value: function toggleAnimate() {
      this.animate = !this.animate;
      if (this.animate) {
        this.toggleAnimateBtn.innerHTML = '<i class="fas fa-pause"></i>';
        // resume the animation
        window.requestAnimationFrame(this.step.bind(this));
      } else {
        this.toggleAnimateBtn.innerHTML = '<i class="fas fa-play"></i>';
      }
    }

    // since I don't know physics, this is an approriximation
  }, {
    key: "setGravitationalPull",
    value: function setGravitationalPull(percent) {
      var _this = this;
      var step, steps, time, direction;
      this.disablePullButtons();
      if (percent < 0) {
        return;
      }
      if (100 < percent) {
        return;
      }
      if (percent === this.gravitationalPull) {
        return;
      }
      steps = 20;
      step = Math.abs(percent - this.gravitationalPull) / steps;
      direction = percent < this.gravitationalPull ? '-' : '+';

      // get the current pull and step it down over 20 steps so it's smoother than jumping straight there
      for (var i = 0; i < steps; i++) {
        // set the time this will fire
        time = i * (i / Math.PI);
        // minimum time span
        if (time < 4) {
          time = 4;
        }
        // set the function
        setTimeout(function () {
          if (direction === '-') {
            _this.gravitationalPull -= step;
          } else {
            _this.gravitationalPull += step;
          }
        }, time);

        // on our last one, set the gravitationalPull to its final, nicely rounded number
        if (i === steps - 1) {
          setTimeout(function () {
            _this.gravitationalPull = Math.round(_this.gravitationalPull);
            _this.setPullButtons();
          }, time + 20);
        }
      }
    }
  }, {
    key: "setPullButtons",
    value: function setPullButtons() {
      if (this.gravitationalPull <= 0) {
        this.decreasePullBtn.disabled = true;
        this.increasePullBtn.disabled = false;
      } else if (100 <= this.gravitationalPull) {
        this.decreasePullBtn.disabled = false;
        this.increasePullBtn.disabled = true;
      } else {
        this.decreasePullBtn.disabled = false;
        this.increasePullBtn.disabled = false;
      }
      this.gravityText.innerHTML = this.gravitationalPull;
    }
  }, {
    key: "disablePullButtons",
    value: function disablePullButtons() {
      this.decreasePullBtn.disabled = true;
      this.increasePullBtn.disabled = true;
    }
  }, {
    key: "increaseGravitationalPull",
    value: function increaseGravitationalPull() {
      this.setGravitationalPull(this.gravitationalPull + 10);
    }
  }, {
    key: "decreaseGravitationalPull",
    value: function decreaseGravitationalPull() {
      this.setGravitationalPull(this.gravitationalPull - 10);
    }

    // if you want the planet to track the cursor
  }, {
    key: "getCursorXY",
    value: function getCursorXY(e) {
      var cursorPos = {
        x: window.Event ? e.pageX : event.clientX + (document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft),
        y: window.Event ? e.pageY : event.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop)
      };
      this.field.style.left = cursorPos.x - this.field.offsetWidth / 2 + "px";
      this.field.style.top = cursorPos.y - this.field.offsetHeight / 2 + "px";
    }
  }, {
    key: "getRandomColor",
    value: function getRandomColor() {
      var colors = ['#00a9b7', '#005f86', '#d6d2c4', '#f8971f', '#BF5700', '#d9534f'];
      return colors[Math.floor(Math.random() * colors.length)];
    }
  }]);
}();
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Orbit);

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QUFPLENBQUMsQ0FBQztJQUM1RDtFQUFDO0FBQUE7QUFHSCxpRUFBZWhFLEtBQUsiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9lbmdhZ2UtMi14Ly4vYXNzZXRzL2pzL2NvbXBvbmVudHMvT3JiaXQuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLy8gT3JpZ2luYWwgSmF2YVNjcmlwdCBjb2RlIGJ5IENoaXJwIEludGVybmV0OiB3d3cuY2hpcnAuY29tLmF1XG4vLyBQbGVhc2UgYWNrbm93bGVkZ2UgdXNlIG9mIHRoaXMgY29kZSBieSBpbmNsdWRpbmcgdGhpcyBoZWFkZXIuXG4vLyBNb2RpZmllZCB2ZXJ5LCB2ZXJ5IGhlYXZpbHkgYnkgSmVyZW15IEpvbmVzOiBodHRwczovL2plcmVteWpvbi5lc1xuXG5cbmNsYXNzIE9yYml0IHtcbiAgY29uc3RydWN0b3IoKSB7XG4gICAgdGhpcy5maWVsZCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmJpdC1maWVsZCcpXG4gICAgdGhpcy5iYWxsc0VsID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LWJhbGxzJylcbiAgICB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsID0gODBcblxuICAgIHRoaXMuZ3Jhdml0eVRleHQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtY3VycmVudC1ncmF2aXR5JylcbiAgICB0aGlzLmluY3JlYXNlUHVsbEJ0biA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmJpdC1pbmNyZWFzZS1wdWxsJylcbiAgICB0aGlzLmRlY3JlYXNlUHVsbEJ0biA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmJpdC1kZWNyZWFzZS1wdWxsJylcbiAgICB0aGlzLnRvZ2dsZUFuaW1hdGVCdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtdG9nZ2xlLWFuaW1hdGUnKVxuICAgIHRoaXMuYmFsbHMgPSBudWxsXG4gICAgdGhpcy5hbmltYXRlID0gdHJ1ZVxuXG4gICAgdGhpcy5iYWxsU2V0dGluZ3MgPSB7XG4gICAgICBudW06IDgwLFxuICAgICAgbWluU2l6ZTogNCxcbiAgICAgIG1heFNpemU6IDEyLFxuICAgIH1cblxuICAgIHRoaXMuc3RhcnQgPSAwXG5cbiAgICB0aGlzLmluaXQoODApXG4gIH1cblxuICBpbml0KGJhbGxzTnVtKSB7XG4gICAgdGhpcy5iYWxscyA9IHRoaXMuY3JlYXRlQmFsbHMoYmFsbHNOdW0pXG4gICAgd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSh0aGlzLnN0ZXAuYmluZCh0aGlzKSlcbiAgICB0aGlzLnNldFB1bGxCdXR0b25zKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwpXG4gICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCB0aGlzLmluY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwuYmluZCh0aGlzKSlcbiAgICB0aGlzLmRlY3JlYXNlUHVsbEJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMuZGVjcmVhc2VHcmF2aXRhdGlvbmFsUHVsbC5iaW5kKHRoaXMpKVxuICAgIHRoaXMudG9nZ2xlQW5pbWF0ZUJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMudG9nZ2xlQW5pbWF0ZS5iaW5kKHRoaXMpKVxuXG5cbiAgICAvLyB1bmNvbW1lbnQgdG8gaGF2ZSBwbGFuZXQgdHJhY2sgY3Vyc29yXG4gICAgLy8gZG9jdW1lbnQub25tb3VzZW1vdmUgPSBnZXRDdXJzb3JYWTtcblxuICB9XG5cbiAgY3JlYXRlQmFsbHMoKSB7XG4gICAgbGV0IHNpemU7XG4gICAgZm9yKGxldCBpID0gMDsgaSA8IHRoaXMuYmFsbFNldHRpbmdzLm51bTsgaSsrKSB7XG4gICAgICAvLyBnZXQgcmFuZG9tIHNpemUgYmV0d2VlbiBzZXR0aW5nIHNpemVzXG4gICAgICBzaXplID0gTWF0aC5jZWlsKHRoaXMuYmFsbFNldHRpbmdzLm1pblNpemUgKyAoTWF0aC5yYW5kb20oKSAqICh0aGlzLmJhbGxTZXR0aW5ncy5tYXhTaXplIC0gdGhpcy5iYWxsU2V0dGluZ3MubWluU2l6ZSkpKVxuICAgICAgdGhpcy5jcmVhdGVCYWxsKHNpemUpXG4gICAgfVxuXG4gICAgLy8gcmV0dXJuIGFsbCB0aGUgYmFsbHNcbiAgICByZXR1cm4gZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnLm9yYml0LWJhbGwnKTtcbiAgfVxuXG4gIGNyZWF0ZUJhbGwoc2l6ZSkge1xuICAgIGxldCBuZXdCYWxsLCBzdHJldGNoRGlyXG5cbiAgICBuZXdCYWxsID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcImRpdlwiKVxuICAgIHN0cmV0Y2hEaXIgPSAoTWF0aC5yb3VuZCgoTWF0aC5yYW5kb20oKSAqIDEpKSA/ICd4JyA6ICd5JylcbiAgICBuZXdCYWxsLmNsYXNzTGlzdC5hZGQoJ29yYml0LWJhbGwnKVxuICAgIG5ld0JhbGwuc3R5bGUud2lkdGggPSBzaXplICsgJ3B4J1xuICAgIG5ld0JhbGwuc3R5bGUuaGVpZ2h0ID0gc2l6ZSArICdweCdcbiAgICBuZXdCYWxsLnN0eWxlLmJhY2tncm91bmQgPSB0aGlzLmdldFJhbmRvbUNvbG9yKCk7XG4gICAgbmV3QmFsbC5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RyZXRjaC1kaXInLCBzdHJldGNoRGlyKSAvLyBlaXRoZXIgeCBvciB5XG5cbiAgICAvLyBUT0RPOiBEZWNyZWFzZSB0aGUgJ2RhdGEtc3RyZXRjaC12YWwnIGF0dHJpYnV0ZSB0byBkZWNyZWFzZSB0aGUgc3ByZWFkIG9mIHRoZSBiYWxsc1xuICAgIG5ld0JhbGwuc2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtdmFsJywgIDEgKyAoTWF0aC5yYW5kb20oKSAqIDUpKVxuICAgIG5ld0JhbGwuc2V0QXR0cmlidXRlKCdkYXRhLWdyaWQnLCB0aGlzLmZpZWxkLm9mZnNldFdpZHRoICsgTWF0aC5yb3VuZCgoTWF0aC5yYW5kb20oKSAqIDEwMCkpKSAvLyBtaW4gb3JiaXQgPSAzMHB4LCBtYXggMTMwXG4gICAgbmV3QmFsbC5zZXRBdHRyaWJ1dGUoJ2RhdGEtZHVyYXRpb24nLCAzLjUgKyBNYXRoLnJvdW5kKChNYXRoLnJhbmRvbSgpICogOCkpKSAvLyBtaW4gZHVyYXRpb24gPSAzLjVzLCBtYXggOHNcbiAgICBuZXdCYWxsLnNldEF0dHJpYnV0ZSgnZGF0YS1zdGFydCcsIDApXG4gICAgdGhpcy5iYWxsc0VsLmFwcGVuZENoaWxkKG5ld0JhbGwpXG4gIH1cblxuICBjYWxsU3RlcCh0aW1lc3RhbXApIHtcbiAgICByZXR1cm4gdGhpcy5zdGVwKHRpbWVzdGFtcClcbiAgfVxuXG4gIHN0ZXAodGltZXN0YW1wKSB7XG4gICAgbGV0IHByb2dyZXNzLCB4LCB5LCBzdHJldGNoLCBncmlkU2l6ZSwgZHVyYXRpb24sIHN0YXJ0LCB4UG9zLCB5UG9zXG4gICAgZm9yKGxldCBpID0gMDsgaSA8IHRoaXMuYmFsbHMubGVuZ3RoOyBpKyspIHtcblxuICAgICAgc3RhcnQgPSB0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1zdGFydCcpXG4gICAgICBpZihzdGFydCA9PSAwKSB7XG4gICAgICAgIHN0YXJ0ID0gdGltZXN0YW1wXG4gICAgICAgIHRoaXMuYmFsbHNbaV0uc2V0QXR0cmlidXRlKCdkYXRhLXN0YXJ0Jywgc3RhcnQpXG4gICAgICB9XG5cbiAgICAgIGdyaWRTaXplID0gdGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtZ3JpZCcpXG4gICAgICBkdXJhdGlvbiA9IHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLWR1cmF0aW9uJylcbiAgICAgIHByb2dyZXNzID0gKHRpbWVzdGFtcCAtIHN0YXJ0KSAvIGR1cmF0aW9uIC8gMTAwMCAvLyBwZXJjZW50XG4gICAgICBzdHJldGNoID0gdGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RyZXRjaC12YWwnKVxuXG4gICAgICBpZih0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLWRpcicpID09PSAneCcpIHtcbiAgICAgICAgeCA9ICgoc3RyZXRjaCAqIE1hdGguc2luKHByb2dyZXNzICogMiAqIE1hdGguUEkpKSAqICgxLjA1IC0gKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwvMTAwKSkpLy8geCA9IMaSKHQpXG4gICAgICAgIHkgPSBNYXRoLmNvcyhwcm9ncmVzcyAqIDIgKiBNYXRoLlBJKSAvLyB5ID0gxpIodClcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHggPSBNYXRoLnNpbihwcm9ncmVzcyAqIDIgKiBNYXRoLlBJKSAvLyB4ID0gxpIodClcbiAgICAgICAgeSA9ICgoc3RyZXRjaCAqIE1hdGguY29zKHByb2dyZXNzICogMiAqIE1hdGguUEkpKSAqICgxLjA1IC0gKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwvMTAwKSkpIC8vIHkgPSDGkih0KVxuICAgICAgfVxuXG4gICAgICB4UG9zID0gdGhpcy5maWVsZC5jbGllbnRXaWR0aC8yICsgKGdyaWRTaXplICogeClcbiAgICAgIHlQb3MgPSB0aGlzLmZpZWxkLmNsaWVudEhlaWdodC8yICsgKGdyaWRTaXplICogeSlcbiAgICAgIHRoaXMuYmFsbHNbaV0uc3R5bGUudHJhbnNmb3JtID0gJ3RyYW5zbGF0ZTNkKCcreFBvcyArICdweCwgJyt5UG9zICsgJ3B4LCAwKSdcblxuICAgICAgLy8gaWYgdGhlc2UgYXJlIHRydWUsIHRoZW4gaXQncyBiZWhpbmQgdGhlIHBsYW5ldFxuICAgICAgaWYoKCh0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLWRpcicpID09PSAneCcpICYmICgoKHRoaXMuZmllbGQub2Zmc2V0V2lkdGgvMikgLSB0aGlzLmJhbGxzW2ldLm9mZnNldFdpZHRoKSAqIC0xKSA8IHhQb3MgJiYgeFBvcyA8ICgodGhpcy5maWVsZC5vZmZzZXRXaWR0aC8yKSArIHRoaXMuYmFsbHNbaV0ub2Zmc2V0V2lkdGgpKSB8fCAoKHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtZGlyJykgPT09ICd5JykgJiYgKCgodGhpcy5maWVsZC5vZmZzZXRXaWR0aC8yKSAtIHRoaXMuYmFsbHNbaV0ub2Zmc2V0V2lkdGgpICogLTEpIDwgeVBvcyAmJiB5UG9zIDwgKCh0aGlzLmZpZWxkLm9mZnNldFdpZHRoLzIpICsgdGhpcy5iYWxsc1tpXS5vZmZzZXRXaWR0aCkpKSB7XG4gICAgICAgIC8vIGJhY2tzaWRlIG9mIHRoZSBtb29uXG4gICAgICAgIHRoaXMuYmFsbHNbaV0uc3R5bGUuekluZGV4ID0gJy0xJ1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgLy8gLi4uZnJvbnQgc2lkZSBvZiB0aGUgbW9vblxuICAgICAgICB0aGlzLmJhbGxzW2ldLnN0eWxlLnpJbmRleCA9ICc5J1xuICAgICAgfVxuXG4gICAgICBpZihwcm9ncmVzcyA+PSAxKSB7XG4gICAgICAgIHRoaXMuYmFsbHNbaV0uc2V0QXR0cmlidXRlKCdkYXRhLXN0YXJ0JywgMCkgLy8gcmVzZXQgdG8gc3RhcnQgcG9zaXRpb25cbiAgICAgIH1cbiAgICB9XG4gICAgaWYodGhpcy5hbmltYXRlID09IHRydWUpIHtcbiAgICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUodGhpcy5zdGVwLmJpbmQodGhpcykpXG4gICAgfVxuXG4gIH1cblxuICB0b2dnbGVBbmltYXRlKCkge1xuXG4gICAgdGhpcy5hbmltYXRlID0gIXRoaXMuYW5pbWF0ZVxuICAgIGlmKHRoaXMuYW5pbWF0ZSkge1xuICAgICAgdGhpcy50b2dnbGVBbmltYXRlQnRuLmlubmVySFRNTCA9ICc8aSBjbGFzcz1cImZhcyBmYS1wYXVzZVwiPjwvaT4nXG4gICAgICAvLyByZXN1bWUgdGhlIGFuaW1hdGlvblxuICAgICAgd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSh0aGlzLnN0ZXAuYmluZCh0aGlzKSlcbiAgICB9IGVsc2Uge1xuICAgICAgdGhpcy50b2dnbGVBbmltYXRlQnRuLmlubmVySFRNTCA9ICc8aSBjbGFzcz1cImZhcyBmYS1wbGF5XCI+PC9pPidcbiAgICB9XG4gIH1cblxuICAvLyBzaW5jZSBJIGRvbid0IGtub3cgcGh5c2ljcywgdGhpcyBpcyBhbiBhcHByb3JpeGltYXRpb25cbiAgc2V0R3Jhdml0YXRpb25hbFB1bGwocGVyY2VudCkge1xuICAgIGxldCBzdGVwLCBzdGVwcywgdGltZSwgZGlyZWN0aW9uXG5cbiAgICB0aGlzLmRpc2FibGVQdWxsQnV0dG9ucygpXG5cbiAgICBpZihwZXJjZW50IDwgMCkge1xuICAgICAgcmV0dXJuXG4gICAgfVxuXG4gICAgaWYoMTAwIDwgcGVyY2VudCkge1xuICAgICAgcmV0dXJuXG4gICAgfVxuXG4gICAgaWYocGVyY2VudCA9PT0gdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCkge1xuICAgICAgcmV0dXJuXG4gICAgfVxuXG4gICAgc3RlcHMgPSAyMFxuICAgIHN0ZXAgPSBNYXRoLmFicyhwZXJjZW50IC0gdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCkvc3RlcHNcbiAgICBkaXJlY3Rpb24gPSAocGVyY2VudCA8IHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgPyAnLScgOiAnKycpXG5cbiAgICAvLyBnZXQgdGhlIGN1cnJlbnQgcHVsbCBhbmQgc3RlcCBpdCBkb3duIG92ZXIgMjAgc3RlcHMgc28gaXQncyBzbW9vdGhlciB0aGFuIGp1bXBpbmcgc3RyYWlnaHQgdGhlcmVcbiAgICBmb3IobGV0IGkgPSAwOyBpIDwgc3RlcHM7IGkrKykge1xuICAgICAgLy8gc2V0IHRoZSB0aW1lIHRoaXMgd2lsbCBmaXJlXG4gICAgICB0aW1lID0gaSAqIChpL01hdGguUEkpXG4gICAgICAvLyBtaW5pbXVtIHRpbWUgc3BhblxuICAgICAgaWYodGltZSA8IDQpIHtcbiAgICAgICAgdGltZSA9IDRcbiAgICAgIH1cbiAgICAgIC8vIHNldCB0aGUgZnVuY3Rpb25cbiAgICAgIHNldFRpbWVvdXQoKCk9PntcbiAgICAgICAgaWYoZGlyZWN0aW9uID09PSAnLScpIHtcbiAgICAgICAgICB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsIC09IHN0ZXBcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsICs9IHN0ZXBcbiAgICAgICAgfVxuXG4gICAgICB9LCB0aW1lKTtcblxuICAgICAgLy8gb24gb3VyIGxhc3Qgb25lLCBzZXQgdGhlIGdyYXZpdGF0aW9uYWxQdWxsIHRvIGl0cyBmaW5hbCwgbmljZWx5IHJvdW5kZWQgbnVtYmVyXG4gICAgICBpZihpID09PSBzdGVwcyAtIDEpIHtcbiAgICAgICAgc2V0VGltZW91dCgoKT0+e1xuICAgICAgICAgIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgPSBNYXRoLnJvdW5kKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwpXG4gICAgICAgICAgdGhpcy5zZXRQdWxsQnV0dG9ucygpXG4gICAgICAgIH0sIHRpbWUgKyAyMClcbiAgICAgIH1cbiAgICB9XG5cbiAgfVxuXG5cbiAgc2V0UHVsbEJ1dHRvbnMoKSB7XG4gICAgaWYodGhpcy5ncmF2aXRhdGlvbmFsUHVsbCA8PSAwKSB7XG4gICAgICB0aGlzLmRlY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IHRydWVcbiAgICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gZmFsc2VcbiAgICB9XG5cbiAgICBlbHNlIGlmKDEwMCA8PSB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsKSB7XG4gICAgICB0aGlzLmRlY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IGZhbHNlXG4gICAgICB0aGlzLmluY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IHRydWVcbiAgICB9XG5cbiAgICBlbHNlIHtcbiAgICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gZmFsc2VcbiAgICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gZmFsc2VcbiAgICB9XG5cbiAgICB0aGlzLmdyYXZpdHlUZXh0LmlubmVySFRNTCA9IHRoaXMuZ3Jhdml0YXRpb25hbFB1bGxcbiAgfVxuXG4gIGRpc2FibGVQdWxsQnV0dG9ucygpIHtcbiAgICB0aGlzLmRlY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IHRydWVcbiAgICB0aGlzLmluY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IHRydWVcbiAgfVxuXG4gIGluY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwoKSB7XG4gICAgdGhpcy5zZXRHcmF2aXRhdGlvbmFsUHVsbCh0aGlzLmdyYXZpdGF0aW9uYWxQdWxsICsgMTApXG4gIH1cblxuICBkZWNyZWFzZUdyYXZpdGF0aW9uYWxQdWxsKCkge1xuICAgIHRoaXMuc2V0R3Jhdml0YXRpb25hbFB1bGwodGhpcy5ncmF2aXRhdGlvbmFsUHVsbCAtIDEwKVxuICB9XG5cbiAgLy8gaWYgeW91IHdhbnQgdGhlIHBsYW5ldCB0byB0cmFjayB0aGUgY3Vyc29yXG4gIGdldEN1cnNvclhZKGUpIHtcbiAgICBsZXQgY3Vyc29yUG9zID0ge1xuICAgICAgeDogKHdpbmRvdy5FdmVudCkgPyBlLnBhZ2VYIDogZXZlbnQuY2xpZW50WCArIChkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc2Nyb2xsTGVmdCA/IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zY3JvbGxMZWZ0IDogZG9jdW1lbnQuYm9keS5zY3JvbGxMZWZ0KSxcbiAgICAgIHk6ICh3aW5kb3cuRXZlbnQpID8gZS5wYWdlWSA6IGV2ZW50LmNsaWVudFkgKyAoZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnNjcm9sbFRvcCA/IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zY3JvbGxUb3AgOiBkb2N1bWVudC5ib2R5LnNjcm9sbFRvcClcbiAgICB9XG5cbiAgICB0aGlzLmZpZWxkLnN0eWxlLmxlZnQgPSBjdXJzb3JQb3MueCAtICh0aGlzLmZpZWxkLm9mZnNldFdpZHRoLzIpICsgXCJweFwiXG4gICAgdGhpcy5maWVsZC5zdHlsZS50b3AgPSBjdXJzb3JQb3MueSAtICh0aGlzLmZpZWxkLm9mZnNldEhlaWdodC8yKSArIFwicHhcIlxuICB9XG5cbiAgZ2V0UmFuZG9tQ29sb3IoKSB7XG4gICAgbGV0IGNvbG9ycyA9IFtcbiAgICAgICcjMDBhOWI3JyxcbiAgICAgICcjMDA1Zjg2JyxcbiAgICAgICcjZDZkMmM0JyxcbiAgICAgICcjZjg5NzFmJyxcbiAgICAgICcjQkY1NzAwJyxcbiAgICAgICcjZDk1MzRmJ1xuICAgIF1cblxuICAgIHJldHVybiBjb2xvcnNbTWF0aC5mbG9vcigoTWF0aC5yYW5kb20oKSAqIGNvbG9ycy5sZW5ndGgpKV1cbiAgfVxufVxuXG5leHBvcnQgZGVmYXVsdCBPcmJpdDtcbiJdLCJuYW1lcyI6WyJPcmJpdCIsIl9jbGFzc0NhbGxDaGVjayIsImZpZWxkIiwiZG9jdW1lbnQiLCJnZXRFbGVtZW50QnlJZCIsImJhbGxzRWwiLCJncmF2aXRhdGlvbmFsUHVsbCIsImdyYXZpdHlUZXh0IiwiaW5jcmVhc2VQdWxsQnRuIiwiZGVjcmVhc2VQdWxsQnRuIiwidG9nZ2xlQW5pbWF0ZUJ0biIsImJhbGxzIiwiYW5pbWF0ZSIsImJhbGxTZXR0aW5ncyIsIm51bSIsIm1pblNpemUiLCJtYXhTaXplIiwic3RhcnQiLCJpbml0IiwiX2NyZWF0ZUNsYXNzIiwia2V5IiwidmFsdWUiLCJiYWxsc051bSIsImNyZWF0ZUJhbGxzIiwid2luZG93IiwicmVxdWVzdEFuaW1hdGlvbkZyYW1lIiwic3RlcCIsImJpbmQiLCJzZXRQdWxsQnV0dG9ucyIsImFkZEV2ZW50TGlzdGVuZXIiLCJpbmNyZWFzZUdyYXZpdGF0aW9uYWxQdWxsIiwiZGVjcmVhc2VHcmF2aXRhdGlvbmFsUHVsbCIsInRvZ2dsZUFuaW1hdGUiLCJzaXplIiwiaSIsIk1hdGgiLCJjZWlsIiwicmFuZG9tIiwiY3JlYXRlQmFsbCIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJuZXdCYWxsIiwic3RyZXRjaERpciIsImNyZWF0ZUVsZW1lbnQiLCJyb3VuZCIsImNsYXNzTGlzdCIsImFkZCIsInN0eWxlIiwid2lkdGgiLCJoZWlnaHQiLCJiYWNrZ3JvdW5kIiwiZ2V0UmFuZG9tQ29sb3IiLCJzZXRBdHRyaWJ1dGUiLCJvZmZzZXRXaWR0aCIsImFwcGVuZENoaWxkIiwiY2FsbFN0ZXAiLCJ0aW1lc3RhbXAiLCJwcm9ncmVzcyIsIngiLCJ5Iiwic3RyZXRjaCIsImdyaWRTaXplIiwiZHVyYXRpb24iLCJ4UG9zIiwieVBvcyIsImxlbmd0aCIsImdldEF0dHJpYnV0ZSIsInNpbiIsIlBJIiwiY29zIiwiY2xpZW50V2lkdGgiLCJjbGllbnRIZWlnaHQiLCJ0cmFuc2Zvcm0iLCJ6SW5kZXgiLCJpbm5lckhUTUwiLCJzZXRHcmF2aXRhdGlvbmFsUHVsbCIsInBlcmNlbnQiLCJfdGhpcyIsInN0ZXBzIiwidGltZSIsImRpcmVjdGlvbiIsImRpc2FibGVQdWxsQnV0dG9ucyIsImFicyIsInNldFRpbWVvdXQiLCJkaXNhYmxlZCIsImdldEN1cnNvclhZIiwiZSIsImN1cnNvclBvcyIsIkV2ZW50IiwicGFnZVgiLCJldmVudCIsImNsaWVudFgiLCJkb2N1bWVudEVsZW1lbnQiLCJzY3JvbGxMZWZ0IiwiYm9keSIsInBhZ2VZIiwiY2xpZW50WSIsInNjcm9sbFRvcCIsImxlZnQiLCJ0b3AiLCJvZmZzZXRIZWlnaHQiLCJjb2xvcnMiLCJmbG9vciJdLCJzb3VyY2VSb290IjoiIn0=
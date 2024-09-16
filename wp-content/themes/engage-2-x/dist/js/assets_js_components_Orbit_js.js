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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZGlzdC9qcy9hc3NldHNfanNfY29tcG9uZW50c19PcmJpdF9qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBQUE7QUFDQTtBQUNBO0FBQUEsSUFHTUEsS0FBSztFQUNULFNBQUFBLE1BQUEsRUFBYztJQUFBQyxlQUFBLE9BQUFELEtBQUE7SUFDWixJQUFJLENBQUNFLEtBQUssR0FBR0MsUUFBUSxDQUFDQyxjQUFjLENBQUMsYUFBYSxDQUFDO0lBQ25ELElBQUksQ0FBQ0MsT0FBTyxHQUFHRixRQUFRLENBQUNDLGNBQWMsQ0FBQyxhQUFhLENBQUM7SUFDckQsSUFBSSxDQUFDRSxpQkFBaUIsR0FBRyxFQUFFO0lBRTNCLElBQUksQ0FBQ0MsV0FBVyxHQUFHSixRQUFRLENBQUNDLGNBQWMsQ0FBQyx1QkFBdUIsQ0FBQztJQUNuRSxJQUFJLENBQUNJLGVBQWUsR0FBR0wsUUFBUSxDQUFDQyxjQUFjLENBQUMscUJBQXFCLENBQUM7SUFDckUsSUFBSSxDQUFDSyxlQUFlLEdBQUdOLFFBQVEsQ0FBQ0MsY0FBYyxDQUFDLHFCQUFxQixDQUFDO0lBQ3JFLElBQUksQ0FBQ00sZ0JBQWdCLEdBQUdQLFFBQVEsQ0FBQ0MsY0FBYyxDQUFDLHNCQUFzQixDQUFDO0lBQ3ZFLElBQUksQ0FBQ08sS0FBSyxHQUFHLElBQUk7SUFDakIsSUFBSSxDQUFDQyxPQUFPLEdBQUcsSUFBSTtJQUVuQixJQUFJLENBQUNDLFlBQVksR0FBRztNQUNsQkMsR0FBRyxFQUFFLEVBQUU7TUFDUEMsT0FBTyxFQUFFLENBQUM7TUFDVkMsT0FBTyxFQUFFO0lBQ1gsQ0FBQztJQUVELElBQUksQ0FBQ0MsS0FBSyxHQUFHLENBQUM7SUFFZCxJQUFJLENBQUNDLElBQUksQ0FBQyxFQUFFLENBQUM7RUFDZjtFQUFDLE9BQUFDLFlBQUEsQ0FBQW5CLEtBQUE7SUFBQW9CLEdBQUE7SUFBQUMsS0FBQSxFQUVELFNBQUFILElBQUlBLENBQUNJLFFBQVEsRUFBRTtNQUNiLElBQUksQ0FBQ1gsS0FB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Q3BDLE1BQU8sQ0FBQyxDQUFDO0lBQzVEO0VBQUM7QUFBQTtBQUdILGlFQUFlaEUsS0FBSyIsInNvdXJjZXMiOlsid2VicGFjazovL2VuZ2FnZS0yLXgvLi9hc3NldHMvanMvY29tcG9uZW50cy9PcmJpdC5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvLyBPcmlnaW5hbCBKYXZhU2NyaXB0IGNvZGUgYnkgQ2hpcnAgSW50ZXJuZXQ6IHd3dy5jaGlycC5jb20uYXVcclxuLy8gUGxlYXNlIGFja25vd2xlZGdlIHVzZSBvZiB0aGlzIGNvZGUgYnkgaW5jbHVkaW5nIHRoaXMgaGVhZGVyLlxyXG4vLyBNb2RpZmllZCB2ZXJ5LCB2ZXJ5IGhlYXZpbHkgYnkgSmVyZW15IEpvbmVzOiBodHRwczovL2plcmVteWpvbi5lc1xyXG5cclxuXHJcbmNsYXNzIE9yYml0IHtcclxuICBjb25zdHJ1Y3RvcigpIHtcclxuICAgIHRoaXMuZmllbGQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtZmllbGQnKVxyXG4gICAgdGhpcy5iYWxsc0VsID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LWJhbGxzJylcclxuICAgIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgPSA4MFxyXG5cclxuICAgIHRoaXMuZ3Jhdml0eVRleHQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtY3VycmVudC1ncmF2aXR5JylcclxuICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LWluY3JlYXNlLXB1bGwnKVxyXG4gICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtZGVjcmVhc2UtcHVsbCcpXHJcbiAgICB0aGlzLnRvZ2dsZUFuaW1hdGVCdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtdG9nZ2xlLWFuaW1hdGUnKVxyXG4gICAgdGhpcy5iYWxscyA9IG51bGxcclxuICAgIHRoaXMuYW5pbWF0ZSA9IHRydWVcclxuXHJcbiAgICB0aGlzLmJhbGxTZXR0aW5ncyA9IHtcclxuICAgICAgbnVtOiA4MCxcclxuICAgICAgbWluU2l6ZTogNCxcclxuICAgICAgbWF4U2l6ZTogMTIsXHJcbiAgICB9XHJcblxyXG4gICAgdGhpcy5zdGFydCA9IDBcclxuXHJcbiAgICB0aGlzLmluaXQoODApXHJcbiAgfVxyXG5cclxuICBpbml0KGJhbGxzTnVtKSB7XHJcbiAgICB0aGlzLmJhbGxzID0gdGhpcy5jcmVhdGVCYWxscyhiYWxsc051bSlcclxuICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUodGhpcy5zdGVwLmJpbmQodGhpcykpXHJcbiAgICB0aGlzLnNldFB1bGxCdXR0b25zKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwpXHJcbiAgICB0aGlzLmluY3JlYXNlUHVsbEJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMuaW5jcmVhc2VHcmF2aXRhdGlvbmFsUHVsbC5iaW5kKHRoaXMpKVxyXG4gICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCB0aGlzLmRlY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwuYmluZCh0aGlzKSlcclxuICAgIHRoaXMudG9nZ2xlQW5pbWF0ZUJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMudG9nZ2xlQW5pbWF0ZS5iaW5kKHRoaXMpKVxyXG5cclxuXHJcbiAgICAvLyB1bmNvbW1lbnQgdG8gaGF2ZSBwbGFuZXQgdHJhY2sgY3Vyc29yXHJcbiAgICAvLyBkb2N1bWVudC5vbm1vdXNlbW92ZSA9IGdldEN1cnNvclhZO1xyXG5cclxuICB9XHJcblxyXG4gIGNyZWF0ZUJhbGxzKCkge1xyXG4gICAgbGV0IHNpemU7XHJcbiAgICBmb3IobGV0IGkgPSAwOyBpIDwgdGhpcy5iYWxsU2V0dGluZ3MubnVtOyBpKyspIHtcclxuICAgICAgLy8gZ2V0IHJhbmRvbSBzaXplIGJldHdlZW4gc2V0dGluZyBzaXplc1xyXG4gICAgICBzaXplID0gTWF0aC5jZWlsKHRoaXMuYmFsbFNldHRpbmdzLm1pblNpemUgKyAoTWF0aC5yYW5kb20oKSAqICh0aGlzLmJhbGxTZXR0aW5ncy5tYXhTaXplIC0gdGhpcy5iYWxsU2V0dGluZ3MubWluU2l6ZSkpKVxyXG4gICAgICB0aGlzLmNyZWF0ZUJhbGwoc2l6ZSlcclxuICAgIH1cclxuXHJcbiAgICAvLyByZXR1cm4gYWxsIHRoZSBiYWxsc1xyXG4gICAgcmV0dXJuIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5vcmJpdC1iYWxsJyk7XHJcbiAgfVxyXG5cclxuICBjcmVhdGVCYWxsKHNpemUpIHtcclxuICAgIGxldCBuZXdCYWxsLCBzdHJldGNoRGlyXHJcblxyXG4gICAgbmV3QmFsbCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIilcclxuICAgIHN0cmV0Y2hEaXIgPSAoTWF0aC5yb3VuZCgoTWF0aC5yYW5kb20oKSAqIDEpKSA/ICd4JyA6ICd5JylcclxuICAgIG5ld0JhbGwuY2xhc3NMaXN0LmFkZCgnb3JiaXQtYmFsbCcpXHJcbiAgICBuZXdCYWxsLnN0eWxlLndpZHRoID0gc2l6ZSArICdweCdcclxuICAgIG5ld0JhbGwuc3R5bGUuaGVpZ2h0ID0gc2l6ZSArICdweCdcclxuICAgIG5ld0JhbGwuc3R5bGUuYmFja2dyb3VuZCA9IHRoaXMuZ2V0UmFuZG9tQ29sb3IoKTtcclxuICAgIG5ld0JhbGwuc2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtZGlyJywgc3RyZXRjaERpcikgLy8gZWl0aGVyIHggb3IgeVxyXG5cclxuICAgIC8vIFRPRE86IERlY3JlYXNlIHRoZSAnZGF0YS1zdHJldGNoLXZhbCcgYXR0cmlidXRlIHRvIGRlY3JlYXNlIHRoZSBzcHJlYWQgb2YgdGhlIGJhbGxzXHJcbiAgICBuZXdCYWxsLnNldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLXZhbCcsICAxICsgKE1hdGgucmFuZG9tKCkgKiA1KSlcclxuICAgIG5ld0JhbGwuc2V0QXR0cmlidXRlKCdkYXRhLWdyaWQnLCB0aGlzLmZpZWxkLm9mZnNldFdpZHRoICsgTWF0aC5yb3VuZCgoTWF0aC5yYW5kb20oKSAqIDEwMCkpKSAvLyBtaW4gb3JiaXQgPSAzMHB4LCBtYXggMTMwXHJcbiAgICBuZXdCYWxsLnNldEF0dHJpYnV0ZSgnZGF0YS1kdXJhdGlvbicsIDMuNSArIE1hdGgucm91bmQoKE1hdGgucmFuZG9tKCkgKiA4KSkpIC8vIG1pbiBkdXJhdGlvbiA9IDMuNXMsIG1heCA4c1xyXG4gICAgbmV3QmFsbC5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RhcnQnLCAwKVxyXG4gICAgdGhpcy5iYWxsc0VsLmFwcGVuZENoaWxkKG5ld0JhbGwpXHJcbiAgfVxyXG5cclxuICBjYWxsU3RlcCh0aW1lc3RhbXApIHtcclxuICAgIHJldHVybiB0aGlzLnN0ZXAodGltZXN0YW1wKVxyXG4gIH1cclxuXHJcbiAgc3RlcCh0aW1lc3RhbXApIHtcclxuICAgIGxldCBwcm9ncmVzcywgeCwgeSwgc3RyZXRjaCwgZ3JpZFNpemUsIGR1cmF0aW9uLCBzdGFydCwgeFBvcywgeVBvc1xyXG4gICAgZm9yKGxldCBpID0gMDsgaSA8IHRoaXMuYmFsbHMubGVuZ3RoOyBpKyspIHtcclxuXHJcbiAgICAgIHN0YXJ0ID0gdGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RhcnQnKVxyXG4gICAgICBpZihzdGFydCA9PSAwKSB7XHJcbiAgICAgICAgc3RhcnQgPSB0aW1lc3RhbXBcclxuICAgICAgICB0aGlzLmJhbGxzW2ldLnNldEF0dHJpYnV0ZSgnZGF0YS1zdGFydCcsIHN0YXJ0KVxyXG4gICAgICB9XHJcblxyXG4gICAgICBncmlkU2l6ZSA9IHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLWdyaWQnKVxyXG4gICAgICBkdXJhdGlvbiA9IHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLWR1cmF0aW9uJylcclxuICAgICAgcHJvZ3Jlc3MgPSAodGltZXN0YW1wIC0gc3RhcnQpIC8gZHVyYXRpb24gLyAxMDAwIC8vIHBlcmNlbnRcclxuICAgICAgc3RyZXRjaCA9IHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtdmFsJylcclxuXHJcbiAgICAgIGlmKHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtZGlyJykgPT09ICd4Jykge1xyXG4gICAgICAgIHggPSAoKHN0cmV0Y2ggKiBNYXRoLnNpbihwcm9ncmVzcyAqIDIgKiBNYXRoLlBJKSkgKiAoMS4wNSAtICh0aGlzLmdyYXZpdGF0aW9uYWxQdWxsLzEwMCkpKS8vIHggPSDGkih0KVxyXG4gICAgICAgIHkgPSBNYXRoLmNvcyhwcm9ncmVzcyAqIDIgKiBNYXRoLlBJKSAvLyB5ID0gxpIodClcclxuICAgICAgfSBlbHNlIHtcclxuICAgICAgICB4ID0gTWF0aC5zaW4ocHJvZ3Jlc3MgKiAyICogTWF0aC5QSSkgLy8geCA9IMaSKHQpXHJcbiAgICAgICAgeSA9ICgoc3RyZXRjaCAqIE1hdGguY29zKHByb2dyZXNzICogMiAqIE1hdGguUEkpKSAqICgxLjA1IC0gKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwvMTAwKSkpIC8vIHkgPSDGkih0KVxyXG4gICAgICB9XHJcblxyXG4gICAgICB4UG9zID0gdGhpcy5maWVsZC5jbGllbnRXaWR0aC8yICsgKGdyaWRTaXplICogeClcclxuICAgICAgeVBvcyA9IHRoaXMuZmllbGQuY2xpZW50SGVpZ2h0LzIgKyAoZ3JpZFNpemUgKiB5KVxyXG4gICAgICB0aGlzLmJhbGxzW2ldLnN0eWxlLnRyYW5zZm9ybSA9ICd0cmFuc2xhdGUzZCgnK3hQb3MgKyAncHgsICcreVBvcyArICdweCwgMCknXHJcblxyXG4gICAgICAvLyBpZiB0aGVzZSBhcmUgdHJ1ZSwgdGhlbiBpdCdzIGJlaGluZCB0aGUgcGxhbmV0XHJcbiAgICAgIGlmKCgodGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RyZXRjaC1kaXInKSA9PT0gJ3gnKSAmJiAoKCh0aGlzLmZpZWxkLm9mZnNldFdpZHRoLzIpIC0gdGhpcy5iYWxsc1tpXS5vZmZzZXRXaWR0aCkgKiAtMSkgPCB4UG9zICYmIHhQb3MgPCAoKHRoaXMuZmllbGQub2Zmc2V0V2lkdGgvMikgKyB0aGlzLmJhbGxzW2ldLm9mZnNldFdpZHRoKSkgfHwgKCh0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLWRpcicpID09PSAneScpICYmICgoKHRoaXMuZmllbGQub2Zmc2V0V2lkdGgvMikgLSB0aGlzLmJhbGxzW2ldLm9mZnNldFdpZHRoKSAqIC0xKSA8IHlQb3MgJiYgeVBvcyA8ICgodGhpcy5maWVsZC5vZmZzZXRXaWR0aC8yKSArIHRoaXMuYmFsbHNbaV0ub2Zmc2V0V2lkdGgpKSkge1xyXG4gICAgICAgIC8vIGJhY2tzaWRlIG9mIHRoZSBtb29uXHJcbiAgICAgICAgdGhpcy5iYWxsc1tpXS5zdHlsZS56SW5kZXggPSAnLTEnXHJcbiAgICAgIH0gZWxzZSB7XHJcbiAgICAgICAgLy8gLi4uZnJvbnQgc2lkZSBvZiB0aGUgbW9vblxyXG4gICAgICAgIHRoaXMuYmFsbHNbaV0uc3R5bGUuekluZGV4ID0gJzknXHJcbiAgICAgIH1cclxuXHJcbiAgICAgIGlmKHByb2dyZXNzID49IDEpIHtcclxuICAgICAgICB0aGlzLmJhbGxzW2ldLnNldEF0dHJpYnV0ZSgnZGF0YS1zdGFydCcsIDApIC8vIHJlc2V0IHRvIHN0YXJ0IHBvc2l0aW9uXHJcbiAgICAgIH1cclxuICAgIH1cclxuICAgIGlmKHRoaXMuYW5pbWF0ZSA9PSB0cnVlKSB7XHJcbiAgICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUodGhpcy5zdGVwLmJpbmQodGhpcykpXHJcbiAgICB9XHJcblxyXG4gIH1cclxuXHJcbiAgdG9nZ2xlQW5pbWF0ZSgpIHtcclxuXHJcbiAgICB0aGlzLmFuaW1hdGUgPSAhdGhpcy5hbmltYXRlXHJcbiAgICBpZih0aGlzLmFuaW1hdGUpIHtcclxuICAgICAgdGhpcy50b2dnbGVBbmltYXRlQnRuLmlubmVySFRNTCA9ICc8aSBjbGFzcz1cImZhcyBmYS1wYXVzZVwiPjwvaT4nXHJcbiAgICAgIC8vIHJlc3VtZSB0aGUgYW5pbWF0aW9uXHJcbiAgICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUodGhpcy5zdGVwLmJpbmQodGhpcykpXHJcbiAgICB9IGVsc2Uge1xyXG4gICAgICB0aGlzLnRvZ2dsZUFuaW1hdGVCdG4uaW5uZXJIVE1MID0gJzxpIGNsYXNzPVwiZmFzIGZhLXBsYXlcIj48L2k+J1xyXG4gICAgfVxyXG4gIH1cclxuXHJcbiAgLy8gc2luY2UgSSBkb24ndCBrbm93IHBoeXNpY3MsIHRoaXMgaXMgYW4gYXBwcm9yaXhpbWF0aW9uXHJcbiAgc2V0R3Jhdml0YXRpb25hbFB1bGwocGVyY2VudCkge1xyXG4gICAgbGV0IHN0ZXAsIHN0ZXBzLCB0aW1lLCBkaXJlY3Rpb25cclxuXHJcbiAgICB0aGlzLmRpc2FibGVQdWxsQnV0dG9ucygpXHJcblxyXG4gICAgaWYocGVyY2VudCA8IDApIHtcclxuICAgICAgcmV0dXJuXHJcbiAgICB9XHJcblxyXG4gICAgaWYoMTAwIDwgcGVyY2VudCkge1xyXG4gICAgICByZXR1cm5cclxuICAgIH1cclxuXHJcbiAgICBpZihwZXJjZW50ID09PSB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsKSB7XHJcbiAgICAgIHJldHVyblxyXG4gICAgfVxyXG5cclxuICAgIHN0ZXBzID0gMjBcclxuICAgIHN0ZXAgPSBNYXRoLmFicyhwZXJjZW50IC0gdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCkvc3RlcHNcclxuICAgIGRpcmVjdGlvbiA9IChwZXJjZW50IDwgdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCA/ICctJyA6ICcrJylcclxuXHJcbiAgICAvLyBnZXQgdGhlIGN1cnJlbnQgcHVsbCBhbmQgc3RlcCBpdCBkb3duIG92ZXIgMjAgc3RlcHMgc28gaXQncyBzbW9vdGhlciB0aGFuIGp1bXBpbmcgc3RyYWlnaHQgdGhlcmVcclxuICAgIGZvcihsZXQgaSA9IDA7IGkgPCBzdGVwczsgaSsrKSB7XHJcbiAgICAgIC8vIHNldCB0aGUgdGltZSB0aGlzIHdpbGwgZmlyZVxyXG4gICAgICB0aW1lID0gaSAqIChpL01hdGguUEkpXHJcbiAgICAgIC8vIG1pbmltdW0gdGltZSBzcGFuXHJcbiAgICAgIGlmKHRpbWUgPCA0KSB7XHJcbiAgICAgICAgdGltZSA9IDRcclxuICAgICAgfVxyXG4gICAgICAvLyBzZXQgdGhlIGZ1bmN0aW9uXHJcbiAgICAgIHNldFRpbWVvdXQoKCk9PntcclxuICAgICAgICBpZihkaXJlY3Rpb24gPT09ICctJykge1xyXG4gICAgICAgICAgdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCAtPSBzdGVwXHJcbiAgICAgICAgfSBlbHNlIHtcclxuICAgICAgICAgIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgKz0gc3RlcFxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgIH0sIHRpbWUpO1xyXG5cclxuICAgICAgLy8gb24gb3VyIGxhc3Qgb25lLCBzZXQgdGhlIGdyYXZpdGF0aW9uYWxQdWxsIHRvIGl0cyBmaW5hbCwgbmljZWx5IHJvdW5kZWQgbnVtYmVyXHJcbiAgICAgIGlmKGkgPT09IHN0ZXBzIC0gMSkge1xyXG4gICAgICAgIHNldFRpbWVvdXQoKCk9PntcclxuICAgICAgICAgIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgPSBNYXRoLnJvdW5kKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwpXHJcbiAgICAgICAgICB0aGlzLnNldFB1bGxCdXR0b25zKClcclxuICAgICAgICB9LCB0aW1lICsgMjApXHJcbiAgICAgIH1cclxuICAgIH1cclxuXHJcbiAgfVxyXG5cclxuXHJcbiAgc2V0UHVsbEJ1dHRvbnMoKSB7XHJcbiAgICBpZih0aGlzLmdyYXZpdGF0aW9uYWxQdWxsIDw9IDApIHtcclxuICAgICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSB0cnVlXHJcbiAgICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gZmFsc2VcclxuICAgIH1cclxuXHJcbiAgICBlbHNlIGlmKDEwMCA8PSB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsKSB7XHJcbiAgICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gZmFsc2VcclxuICAgICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSB0cnVlXHJcbiAgICB9XHJcblxyXG4gICAgZWxzZSB7XHJcbiAgICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gZmFsc2VcclxuICAgICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSBmYWxzZVxyXG4gICAgfVxyXG5cclxuICAgIHRoaXMuZ3Jhdml0eVRleHQuaW5uZXJIVE1MID0gdGhpcy5ncmF2aXRhdGlvbmFsUHVsbFxyXG4gIH1cclxuXHJcbiAgZGlzYWJsZVB1bGxCdXR0b25zKCkge1xyXG4gICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSB0cnVlXHJcbiAgICB0aGlzLmluY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IHRydWVcclxuICB9XHJcblxyXG4gIGluY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwoKSB7XHJcbiAgICB0aGlzLnNldEdyYXZpdGF0aW9uYWxQdWxsKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgKyAxMClcclxuICB9XHJcblxyXG4gIGRlY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwoKSB7XHJcbiAgICB0aGlzLnNldEdyYXZpdGF0aW9uYWxQdWxsKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgLSAxMClcclxuICB9XHJcblxyXG4gIC8vIGlmIHlvdSB3YW50IHRoZSBwbGFuZXQgdG8gdHJhY2sgdGhlIGN1cnNvclxyXG4gIGdldEN1cnNvclhZKGUpIHtcclxuICAgIGxldCBjdXJzb3JQb3MgPSB7XHJcbiAgICAgIHg6ICh3aW5kb3cuRXZlbnQpID8gZS5wYWdlWCA6IGV2ZW50LmNsaWVudFggKyAoZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnNjcm9sbExlZnQgPyBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc2Nyb2xsTGVmdCA6IGRvY3VtZW50LmJvZHkuc2Nyb2xsTGVmdCksXHJcbiAgICAgIHk6ICh3aW5kb3cuRXZlbnQpID8gZS5wYWdlWSA6IGV2ZW50LmNsaWVudFkgKyAoZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnNjcm9sbFRvcCA/IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zY3JvbGxUb3AgOiBkb2N1bWVudC5ib2R5LnNjcm9sbFRvcClcclxuICAgIH1cclxuXHJcbiAgICB0aGlzLmZpZWxkLnN0eWxlLmxlZnQgPSBjdXJzb3JQb3MueCAtICh0aGlzLmZpZWxkLm9mZnNldFdpZHRoLzIpICsgXCJweFwiXHJcbiAgICB0aGlzLmZpZWxkLnN0eWxlLnRvcCA9IGN1cnNvclBvcy55IC0gKHRoaXMuZmllbGQub2Zmc2V0SGVpZ2h0LzIpICsgXCJweFwiXHJcbiAgfVxyXG5cclxuICBnZXRSYW5kb21Db2xvcigpIHtcclxuICAgIGxldCBjb2xvcnMgPSBbXHJcbiAgICAgICcjMDBhOWI3JyxcclxuICAgICAgJyMwMDVmODYnLFxyXG4gICAgICAnI2Q2ZDJjNCcsXHJcbiAgICAgICcjZjg5NzFmJyxcclxuICAgICAgJyNCRjU3MDAnLFxyXG4gICAgICAnI2Q5NTM0ZidcclxuICAgIF1cclxuXHJcbiAgICByZXR1cm4gY29sb3JzW01hdGguZmxvb3IoKE1hdGgucmFuZG9tKCkgKiBjb2xvcnMubGVuZ3RoKSldXHJcbiAgfVxyXG59XHJcblxyXG5leHBvcnQgZGVmYXVsdCBPcmJpdDtcclxuIl0sIm5hbWVzIjpbIk9yYml0IiwiX2NsYXNzQ2FsbENoZWNrIiwiZmllbGQiLCJkb2N1bWVudCIsImdldEVsZW1lbnRCeUlkIiwiYmFsbHNFbCIsImdyYXZpdGF0aW9uYWxQdWxsIiwiZ3Jhdml0eVRleHQiLCJpbmNyZWFzZVB1bGxCdG4iLCJkZWNyZWFzZVB1bGxCdG4iLCJ0b2dnbGVBbmltYXRlQnRuIiwiYmFsbHMiLCJhbmltYXRlIiwiYmFsbFNldHRpbmdzIiwibnVtIiwibWluU2l6ZSIsIm1heFNpemUiLCJzdGFydCIsImluaXQiLCJfY3JlYXRlQ2xhc3MiLCJrZXkiLCJ2YWx1ZSIsImJhbGxzTnVtIiwiY3JlYXRlQmFsbHMiLCJ3aW5kb3ciLCJyZXF1ZXN0QW5pbWF0aW9uRnJhbWUiLCJzdGVwIiwiYmluZCIsInNldFB1bGxCdXR0b25zIiwiYWRkRXZlbnRMaXN0ZW5lciIsImluY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwiLCJkZWNyZWFzZUdyYXZpdGF0aW9uYWxQdWxsIiwidG9nZ2xlQW5pbWF0ZSIsInNpemUiLCJpIiwiTWF0aCIsImNlaWwiLCJyYW5kb20iLCJjcmVhdGVCYWxsIiwicXVlcnlTZWxlY3RvckFsbCIsIm5ld0JhbGwiLCJzdHJldGNoRGlyIiwiY3JlYXRlRWxlbWVudCIsInJvdW5kIiwiY2xhc3NMaXN0IiwiYWRkIiwic3R5bGUiLCJ3aWR0aCIsImhlaWdodCIsImJhY2tncm91bmQiLCJnZXRSYW5kb21Db2xvciIsInNldEF0dHJpYnV0ZSIsIm9mZnNldFdpZHRoIiwiYXBwZW5kQ2hpbGQiLCJjYWxsU3RlcCIsInRpbWVzdGFtcCIsInByb2dyZXNzIiwieCIsInkiLCJzdHJldGNoIiwiZ3JpZFNpemUiLCJkdXJhdGlvbiIsInhQb3MiLCJ5UG9zIiwibGVuZ3RoIiwiZ2V0QXR0cmlidXRlIiwic2luIiwiUEkiLCJjb3MiLCJjbGllbnRXaWR0aCIsImNsaWVudEhlaWdodCIsInRyYW5zZm9ybSIsInpJbmRleCIsImlubmVySFRNTCIsInNldEdyYXZpdGF0aW9uYWxQdWxsIiwicGVyY2VudCIsIl90aGlzIiwic3RlcHMiLCJ0aW1lIiwiZGlyZWN0aW9uIiwiZGlzYWJsZVB1bGxCdXR0b25zIiwiYWJzIiwic2V0VGltZW91dCIsImRpc2FibGVkIiwiZ2V0Q3Vyc29yWFkiLCJlIiwiY3Vyc29yUG9zIiwiRXZlbnQiLCJwYWdlWCIsImV2ZW50IiwiY2xpZW50WCIsImRvY3VtZW50RWxlbWVudCIsInNjcm9sbExlZnQiLCJib2R5IiwicGFnZVkiLCJjbGllbnRZIiwic2Nyb2xsVG9wIiwibGVmdCIsInRvcCIsIm9mZnNldEhlaWdodCIsImNvbG9ycyIsImZsb29yIl0sInNvdXJjZVJvb3QiOiIifQ==
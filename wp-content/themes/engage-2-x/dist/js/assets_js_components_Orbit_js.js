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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZGlzdC9qcy9hc3NldHNfanNfY29tcG9uZW50c19PcmJpdF9qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBQUE7QUFDQTtBQUNBO0FBQUEsSUFHTUEsS0FBSztFQUNULFNBQUFBLE1BQUEsRUFBYztJQUFBQyxlQUFBLE9BQUFELEtBQUE7SUFDWixJQUFJLENBQUNFLEtBQUssR0FBR0MsUUFBUSxDQUFDQyxjQUFjLENBQUMsYUFBYSxDQUFDO0lBQ25ELElBQUksQ0FBQ0MsT0FBTyxHQUFHRixRQUFRLENBQUNDLGNBQWMsQ0FBQyxhQUFhLENBQUM7SUFDckQsSUFBSSxDQUFDRSxpQkFBaUIsR0FBRyxFQUFFO0lBRTNCLElBQUksQ0FBQ0MsV0FBVyxHQUFHSixRQUFRLENBQUNDLGNBQWMsQ0FBQyx1QkFBdUIsQ0FBQztJQUNuRSxJQUFJLENBQUNJLGVBQWUsR0FBR0wsUUFBUSxDQUFDQyxjQUFjLENBQUMscUJBQXFCLENBQUM7SUFDckUsSUFBSSxDQUFDSyxlQUFlLEdBQUdOLFFBQVEsQ0FBQ0MsY0FBYyxDQUFDLHFCQUFxQixDQUFDO0lBQ3JFLElBQUksQ0FBQ00sZ0JBQWdCLEdBQUdQLFFBQVEsQ0FBQ0MsY0FBYyxDQUFDLHNCQUFzQixDQUFDO0lBQ3ZFLElBQUksQ0FBQ08sS0FBSyxHQUFHLElBQUk7SUFDakIsSUFBSSxDQUFDQyxPQUFPLEdBQUcsSUFBSTtJQUVuQixJQUFJLENBQUNDLFlBQVksR0FBRztNQUNsQkMsR0FBRyxFQUFFLEVBQUU7TUFDUEMsT0FBTyxFQUFFLENBQUM7TUFDVkMsT0FBTyxFQUFFO0lBQ1gsQ0FBQztJQUVELElBQUksQ0FBQ0MsS0FBSyxHQUFHLENBQUM7SUFFZCxJQUFJLENBQUNDLElBQUksQ0FBQyxFQUFFLENBQUM7RUFDZjtFQUFDLE9BQUFDLFlBQUEsQ0FBQW5CLEtBQUE7SUFBQW9CLEdBQUE7SUFBQUMsS0FBQSxFQUVELFNBQUFILElBQUlBLENBQUNJLFFBQVEsRUFBRTtNQUNiLElBQUksQ0FBQ1gsS0FB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Q3BDLE1BQU8sQ0FBQyxDQUFDO0lBQzVEO0VBQUM7QUFBQTtBQUdILGlFQUFlaEUsS0FBSyIsInNvdXJjZXMiOlsid2VicGFjazovL2VuZ2FnZS0yLXgvLi9hc3NldHMvanMvY29tcG9uZW50cy9PcmJpdC5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvLyBPcmlnaW5hbCBKYXZhU2NyaXB0IGNvZGUgYnkgQ2hpcnAgSW50ZXJuZXQ6IHd3dy5jaGlycC5jb20uYXVcbi8vIFBsZWFzZSBhY2tub3dsZWRnZSB1c2Ugb2YgdGhpcyBjb2RlIGJ5IGluY2x1ZGluZyB0aGlzIGhlYWRlci5cbi8vIE1vZGlmaWVkIHZlcnksIHZlcnkgaGVhdmlseSBieSBKZXJlbXkgSm9uZXM6IGh0dHBzOi8vamVyZW15am9uLmVzXG5cblxuY2xhc3MgT3JiaXQge1xuICBjb25zdHJ1Y3RvcigpIHtcbiAgICB0aGlzLmZpZWxkID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LWZpZWxkJylcbiAgICB0aGlzLmJhbGxzRWwgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtYmFsbHMnKVxuICAgIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgPSA4MFxuXG4gICAgdGhpcy5ncmF2aXR5VGV4dCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmJpdC1jdXJyZW50LWdyYXZpdHknKVxuICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LWluY3JlYXNlLXB1bGwnKVxuICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LWRlY3JlYXNlLXB1bGwnKVxuICAgIHRoaXMudG9nZ2xlQW5pbWF0ZUJ0biA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmJpdC10b2dnbGUtYW5pbWF0ZScpXG4gICAgdGhpcy5iYWxscyA9IG51bGxcbiAgICB0aGlzLmFuaW1hdGUgPSB0cnVlXG5cbiAgICB0aGlzLmJhbGxTZXR0aW5ncyA9IHtcbiAgICAgIG51bTogODAsXG4gICAgICBtaW5TaXplOiA0LFxuICAgICAgbWF4U2l6ZTogMTIsXG4gICAgfVxuXG4gICAgdGhpcy5zdGFydCA9IDBcblxuICAgIHRoaXMuaW5pdCg4MClcbiAgfVxuXG4gIGluaXQoYmFsbHNOdW0pIHtcbiAgICB0aGlzLmJhbGxzID0gdGhpcy5jcmVhdGVCYWxscyhiYWxsc051bSlcbiAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lKHRoaXMuc3RlcC5iaW5kKHRoaXMpKVxuICAgIHRoaXMuc2V0UHVsbEJ1dHRvbnModGhpcy5ncmF2aXRhdGlvbmFsUHVsbClcbiAgICB0aGlzLmluY3JlYXNlUHVsbEJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMuaW5jcmVhc2VHcmF2aXRhdGlvbmFsUHVsbC5iaW5kKHRoaXMpKVxuICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgdGhpcy5kZWNyZWFzZUdyYXZpdGF0aW9uYWxQdWxsLmJpbmQodGhpcykpXG4gICAgdGhpcy50b2dnbGVBbmltYXRlQnRuLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgdGhpcy50b2dnbGVBbmltYXRlLmJpbmQodGhpcykpXG5cblxuICAgIC8vIHVuY29tbWVudCB0byBoYXZlIHBsYW5ldCB0cmFjayBjdXJzb3JcbiAgICAvLyBkb2N1bWVudC5vbm1vdXNlbW92ZSA9IGdldEN1cnNvclhZO1xuXG4gIH1cblxuICBjcmVhdGVCYWxscygpIHtcbiAgICBsZXQgc2l6ZTtcbiAgICBmb3IobGV0IGkgPSAwOyBpIDwgdGhpcy5iYWxsU2V0dGluZ3MubnVtOyBpKyspIHtcbiAgICAgIC8vIGdldCByYW5kb20gc2l6ZSBiZXR3ZWVuIHNldHRpbmcgc2l6ZXNcbiAgICAgIHNpemUgPSBNYXRoLmNlaWwodGhpcy5iYWxsU2V0dGluZ3MubWluU2l6ZSArIChNYXRoLnJhbmRvbSgpICogKHRoaXMuYmFsbFNldHRpbmdzLm1heFNpemUgLSB0aGlzLmJhbGxTZXR0aW5ncy5taW5TaXplKSkpXG4gICAgICB0aGlzLmNyZWF0ZUJhbGwoc2l6ZSlcbiAgICB9XG5cbiAgICAvLyByZXR1cm4gYWxsIHRoZSBiYWxsc1xuICAgIHJldHVybiBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcub3JiaXQtYmFsbCcpO1xuICB9XG5cbiAgY3JlYXRlQmFsbChzaXplKSB7XG4gICAgbGV0IG5ld0JhbGwsIHN0cmV0Y2hEaXJcblxuICAgIG5ld0JhbGwgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiZGl2XCIpXG4gICAgc3RyZXRjaERpciA9IChNYXRoLnJvdW5kKChNYXRoLnJhbmRvbSgpICogMSkpID8gJ3gnIDogJ3knKVxuICAgIG5ld0JhbGwuY2xhc3NMaXN0LmFkZCgnb3JiaXQtYmFsbCcpXG4gICAgbmV3QmFsbC5zdHlsZS53aWR0aCA9IHNpemUgKyAncHgnXG4gICAgbmV3QmFsbC5zdHlsZS5oZWlnaHQgPSBzaXplICsgJ3B4J1xuICAgIG5ld0JhbGwuc3R5bGUuYmFja2dyb3VuZCA9IHRoaXMuZ2V0UmFuZG9tQ29sb3IoKTtcbiAgICBuZXdCYWxsLnNldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLWRpcicsIHN0cmV0Y2hEaXIpIC8vIGVpdGhlciB4IG9yIHlcblxuICAgIC8vIFRPRE86IERlY3JlYXNlIHRoZSAnZGF0YS1zdHJldGNoLXZhbCcgYXR0cmlidXRlIHRvIGRlY3JlYXNlIHRoZSBzcHJlYWQgb2YgdGhlIGJhbGxzXG4gICAgbmV3QmFsbC5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RyZXRjaC12YWwnLCAgMSArIChNYXRoLnJhbmRvbSgpICogNSkpXG4gICAgbmV3QmFsbC5zZXRBdHRyaWJ1dGUoJ2RhdGEtZ3JpZCcsIHRoaXMuZmllbGQub2Zmc2V0V2lkdGggKyBNYXRoLnJvdW5kKChNYXRoLnJhbmRvbSgpICogMTAwKSkpIC8vIG1pbiBvcmJpdCA9IDMwcHgsIG1heCAxMzBcbiAgICBuZXdCYWxsLnNldEF0dHJpYnV0ZSgnZGF0YS1kdXJhdGlvbicsIDMuNSArIE1hdGgucm91bmQoKE1hdGgucmFuZG9tKCkgKiA4KSkpIC8vIG1pbiBkdXJhdGlvbiA9IDMuNXMsIG1heCA4c1xuICAgIG5ld0JhbGwuc2V0QXR0cmlidXRlKCdkYXRhLXN0YXJ0JywgMClcbiAgICB0aGlzLmJhbGxzRWwuYXBwZW5kQ2hpbGQobmV3QmFsbClcbiAgfVxuXG4gIGNhbGxTdGVwKHRpbWVzdGFtcCkge1xuICAgIHJldHVybiB0aGlzLnN0ZXAodGltZXN0YW1wKVxuICB9XG5cbiAgc3RlcCh0aW1lc3RhbXApIHtcbiAgICBsZXQgcHJvZ3Jlc3MsIHgsIHksIHN0cmV0Y2gsIGdyaWRTaXplLCBkdXJhdGlvbiwgc3RhcnQsIHhQb3MsIHlQb3NcbiAgICBmb3IobGV0IGkgPSAwOyBpIDwgdGhpcy5iYWxscy5sZW5ndGg7IGkrKykge1xuXG4gICAgICBzdGFydCA9IHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLXN0YXJ0JylcbiAgICAgIGlmKHN0YXJ0ID09IDApIHtcbiAgICAgICAgc3RhcnQgPSB0aW1lc3RhbXBcbiAgICAgICAgdGhpcy5iYWxsc1tpXS5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RhcnQnLCBzdGFydClcbiAgICAgIH1cblxuICAgICAgZ3JpZFNpemUgPSB0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1ncmlkJylcbiAgICAgIGR1cmF0aW9uID0gdGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtZHVyYXRpb24nKVxuICAgICAgcHJvZ3Jlc3MgPSAodGltZXN0YW1wIC0gc3RhcnQpIC8gZHVyYXRpb24gLyAxMDAwIC8vIHBlcmNlbnRcbiAgICAgIHN0cmV0Y2ggPSB0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLXZhbCcpXG5cbiAgICAgIGlmKHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtZGlyJykgPT09ICd4Jykge1xuICAgICAgICB4ID0gKChzdHJldGNoICogTWF0aC5zaW4ocHJvZ3Jlc3MgKiAyICogTWF0aC5QSSkpICogKDEuMDUgLSAodGhpcy5ncmF2aXRhdGlvbmFsUHVsbC8xMDApKSkvLyB4ID0gxpIodClcbiAgICAgICAgeSA9IE1hdGguY29zKHByb2dyZXNzICogMiAqIE1hdGguUEkpIC8vIHkgPSDGkih0KVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgeCA9IE1hdGguc2luKHByb2dyZXNzICogMiAqIE1hdGguUEkpIC8vIHggPSDGkih0KVxuICAgICAgICB5ID0gKChzdHJldGNoICogTWF0aC5jb3MocHJvZ3Jlc3MgKiAyICogTWF0aC5QSSkpICogKDEuMDUgLSAodGhpcy5ncmF2aXRhdGlvbmFsUHVsbC8xMDApKSkgLy8geSA9IMaSKHQpXG4gICAgICB9XG5cbiAgICAgIHhQb3MgPSB0aGlzLmZpZWxkLmNsaWVudFdpZHRoLzIgKyAoZ3JpZFNpemUgKiB4KVxuICAgICAgeVBvcyA9IHRoaXMuZmllbGQuY2xpZW50SGVpZ2h0LzIgKyAoZ3JpZFNpemUgKiB5KVxuICAgICAgdGhpcy5iYWxsc1tpXS5zdHlsZS50cmFuc2Zvcm0gPSAndHJhbnNsYXRlM2QoJyt4UG9zICsgJ3B4LCAnK3lQb3MgKyAncHgsIDApJ1xuXG4gICAgICAvLyBpZiB0aGVzZSBhcmUgdHJ1ZSwgdGhlbiBpdCdzIGJlaGluZCB0aGUgcGxhbmV0XG4gICAgICBpZigoKHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtZGlyJykgPT09ICd4JykgJiYgKCgodGhpcy5maWVsZC5vZmZzZXRXaWR0aC8yKSAtIHRoaXMuYmFsbHNbaV0ub2Zmc2V0V2lkdGgpICogLTEpIDwgeFBvcyAmJiB4UG9zIDwgKCh0aGlzLmZpZWxkLm9mZnNldFdpZHRoLzIpICsgdGhpcy5iYWxsc1tpXS5vZmZzZXRXaWR0aCkpIHx8ICgodGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RyZXRjaC1kaXInKSA9PT0gJ3knKSAmJiAoKCh0aGlzLmZpZWxkLm9mZnNldFdpZHRoLzIpIC0gdGhpcy5iYWxsc1tpXS5vZmZzZXRXaWR0aCkgKiAtMSkgPCB5UG9zICYmIHlQb3MgPCAoKHRoaXMuZmllbGQub2Zmc2V0V2lkdGgvMikgKyB0aGlzLmJhbGxzW2ldLm9mZnNldFdpZHRoKSkpIHtcbiAgICAgICAgLy8gYmFja3NpZGUgb2YgdGhlIG1vb25cbiAgICAgICAgdGhpcy5iYWxsc1tpXS5zdHlsZS56SW5kZXggPSAnLTEnXG4gICAgICB9IGVsc2Uge1xuICAgICAgICAvLyAuLi5mcm9udCBzaWRlIG9mIHRoZSBtb29uXG4gICAgICAgIHRoaXMuYmFsbHNbaV0uc3R5bGUuekluZGV4ID0gJzknXG4gICAgICB9XG5cbiAgICAgIGlmKHByb2dyZXNzID49IDEpIHtcbiAgICAgICAgdGhpcy5iYWxsc1tpXS5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RhcnQnLCAwKSAvLyByZXNldCB0byBzdGFydCBwb3NpdGlvblxuICAgICAgfVxuICAgIH1cbiAgICBpZih0aGlzLmFuaW1hdGUgPT0gdHJ1ZSkge1xuICAgICAgd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSh0aGlzLnN0ZXAuYmluZCh0aGlzKSlcbiAgICB9XG5cbiAgfVxuXG4gIHRvZ2dsZUFuaW1hdGUoKSB7XG5cbiAgICB0aGlzLmFuaW1hdGUgPSAhdGhpcy5hbmltYXRlXG4gICAgaWYodGhpcy5hbmltYXRlKSB7XG4gICAgICB0aGlzLnRvZ2dsZUFuaW1hdGVCdG4uaW5uZXJIVE1MID0gJzxpIGNsYXNzPVwiZmFzIGZhLXBhdXNlXCI+PC9pPidcbiAgICAgIC8vIHJlc3VtZSB0aGUgYW5pbWF0aW9uXG4gICAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lKHRoaXMuc3RlcC5iaW5kKHRoaXMpKVxuICAgIH0gZWxzZSB7XG4gICAgICB0aGlzLnRvZ2dsZUFuaW1hdGVCdG4uaW5uZXJIVE1MID0gJzxpIGNsYXNzPVwiZmFzIGZhLXBsYXlcIj48L2k+J1xuICAgIH1cbiAgfVxuXG4gIC8vIHNpbmNlIEkgZG9uJ3Qga25vdyBwaHlzaWNzLCB0aGlzIGlzIGFuIGFwcHJvcml4aW1hdGlvblxuICBzZXRHcmF2aXRhdGlvbmFsUHVsbChwZXJjZW50KSB7XG4gICAgbGV0IHN0ZXAsIHN0ZXBzLCB0aW1lLCBkaXJlY3Rpb25cblxuICAgIHRoaXMuZGlzYWJsZVB1bGxCdXR0b25zKClcblxuICAgIGlmKHBlcmNlbnQgPCAwKSB7XG4gICAgICByZXR1cm5cbiAgICB9XG5cbiAgICBpZigxMDAgPCBwZXJjZW50KSB7XG4gICAgICByZXR1cm5cbiAgICB9XG5cbiAgICBpZihwZXJjZW50ID09PSB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsKSB7XG4gICAgICByZXR1cm5cbiAgICB9XG5cbiAgICBzdGVwcyA9IDIwXG4gICAgc3RlcCA9IE1hdGguYWJzKHBlcmNlbnQgLSB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsKS9zdGVwc1xuICAgIGRpcmVjdGlvbiA9IChwZXJjZW50IDwgdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCA/ICctJyA6ICcrJylcblxuICAgIC8vIGdldCB0aGUgY3VycmVudCBwdWxsIGFuZCBzdGVwIGl0IGRvd24gb3ZlciAyMCBzdGVwcyBzbyBpdCdzIHNtb290aGVyIHRoYW4ganVtcGluZyBzdHJhaWdodCB0aGVyZVxuICAgIGZvcihsZXQgaSA9IDA7IGkgPCBzdGVwczsgaSsrKSB7XG4gICAgICAvLyBzZXQgdGhlIHRpbWUgdGhpcyB3aWxsIGZpcmVcbiAgICAgIHRpbWUgPSBpICogKGkvTWF0aC5QSSlcbiAgICAgIC8vIG1pbmltdW0gdGltZSBzcGFuXG4gICAgICBpZih0aW1lIDwgNCkge1xuICAgICAgICB0aW1lID0gNFxuICAgICAgfVxuICAgICAgLy8gc2V0IHRoZSBmdW5jdGlvblxuICAgICAgc2V0VGltZW91dCgoKT0+e1xuICAgICAgICBpZihkaXJlY3Rpb24gPT09ICctJykge1xuICAgICAgICAgIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgLT0gc3RlcFxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgKz0gc3RlcFxuICAgICAgICB9XG5cbiAgICAgIH0sIHRpbWUpO1xuXG4gICAgICAvLyBvbiBvdXIgbGFzdCBvbmUsIHNldCB0aGUgZ3Jhdml0YXRpb25hbFB1bGwgdG8gaXRzIGZpbmFsLCBuaWNlbHkgcm91bmRlZCBudW1iZXJcbiAgICAgIGlmKGkgPT09IHN0ZXBzIC0gMSkge1xuICAgICAgICBzZXRUaW1lb3V0KCgpPT57XG4gICAgICAgICAgdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCA9IE1hdGgucm91bmQodGhpcy5ncmF2aXRhdGlvbmFsUHVsbClcbiAgICAgICAgICB0aGlzLnNldFB1bGxCdXR0b25zKClcbiAgICAgICAgfSwgdGltZSArIDIwKVxuICAgICAgfVxuICAgIH1cblxuICB9XG5cblxuICBzZXRQdWxsQnV0dG9ucygpIHtcbiAgICBpZih0aGlzLmdyYXZpdGF0aW9uYWxQdWxsIDw9IDApIHtcbiAgICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gdHJ1ZVxuICAgICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSBmYWxzZVxuICAgIH1cblxuICAgIGVsc2UgaWYoMTAwIDw9IHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwpIHtcbiAgICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gZmFsc2VcbiAgICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gdHJ1ZVxuICAgIH1cblxuICAgIGVsc2Uge1xuICAgICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSBmYWxzZVxuICAgICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSBmYWxzZVxuICAgIH1cblxuICAgIHRoaXMuZ3Jhdml0eVRleHQuaW5uZXJIVE1MID0gdGhpcy5ncmF2aXRhdGlvbmFsUHVsbFxuICB9XG5cbiAgZGlzYWJsZVB1bGxCdXR0b25zKCkge1xuICAgIHRoaXMuZGVjcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gdHJ1ZVxuICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuLmRpc2FibGVkID0gdHJ1ZVxuICB9XG5cbiAgaW5jcmVhc2VHcmF2aXRhdGlvbmFsUHVsbCgpIHtcbiAgICB0aGlzLnNldEdyYXZpdGF0aW9uYWxQdWxsKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgKyAxMClcbiAgfVxuXG4gIGRlY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwoKSB7XG4gICAgdGhpcy5zZXRHcmF2aXRhdGlvbmFsUHVsbCh0aGlzLmdyYXZpdGF0aW9uYWxQdWxsIC0gMTApXG4gIH1cblxuICAvLyBpZiB5b3Ugd2FudCB0aGUgcGxhbmV0IHRvIHRyYWNrIHRoZSBjdXJzb3JcbiAgZ2V0Q3Vyc29yWFkoZSkge1xuICAgIGxldCBjdXJzb3JQb3MgPSB7XG4gICAgICB4OiAod2luZG93LkV2ZW50KSA/IGUucGFnZVggOiBldmVudC5jbGllbnRYICsgKGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zY3JvbGxMZWZ0ID8gZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnNjcm9sbExlZnQgOiBkb2N1bWVudC5ib2R5LnNjcm9sbExlZnQpLFxuICAgICAgeTogKHdpbmRvdy5FdmVudCkgPyBlLnBhZ2VZIDogZXZlbnQuY2xpZW50WSArIChkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc2Nyb2xsVG9wID8gZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnNjcm9sbFRvcCA6IGRvY3VtZW50LmJvZHkuc2Nyb2xsVG9wKVxuICAgIH1cblxuICAgIHRoaXMuZmllbGQuc3R5bGUubGVmdCA9IGN1cnNvclBvcy54IC0gKHRoaXMuZmllbGQub2Zmc2V0V2lkdGgvMikgKyBcInB4XCJcbiAgICB0aGlzLmZpZWxkLnN0eWxlLnRvcCA9IGN1cnNvclBvcy55IC0gKHRoaXMuZmllbGQub2Zmc2V0SGVpZ2h0LzIpICsgXCJweFwiXG4gIH1cblxuICBnZXRSYW5kb21Db2xvcigpIHtcbiAgICBsZXQgY29sb3JzID0gW1xuICAgICAgJyMwMGE5YjcnLFxuICAgICAgJyMwMDVmODYnLFxuICAgICAgJyNkNmQyYzQnLFxuICAgICAgJyNmODk3MWYnLFxuICAgICAgJyNCRjU3MDAnLFxuICAgICAgJyNkOTUzNGYnXG4gICAgXVxuXG4gICAgcmV0dXJuIGNvbG9yc1tNYXRoLmZsb29yKChNYXRoLnJhbmRvbSgpICogY29sb3JzLmxlbmd0aCkpXVxuICB9XG59XG5cbmV4cG9ydCBkZWZhdWx0IE9yYml0O1xuIl0sIm5hbWVzIjpbIk9yYml0IiwiX2NsYXNzQ2FsbENoZWNrIiwiZmllbGQiLCJkb2N1bWVudCIsImdldEVsZW1lbnRCeUlkIiwiYmFsbHNFbCIsImdyYXZpdGF0aW9uYWxQdWxsIiwiZ3Jhdml0eVRleHQiLCJpbmNyZWFzZVB1bGxCdG4iLCJkZWNyZWFzZVB1bGxCdG4iLCJ0b2dnbGVBbmltYXRlQnRuIiwiYmFsbHMiLCJhbmltYXRlIiwiYmFsbFNldHRpbmdzIiwibnVtIiwibWluU2l6ZSIsIm1heFNpemUiLCJzdGFydCIsImluaXQiLCJfY3JlYXRlQ2xhc3MiLCJrZXkiLCJ2YWx1ZSIsImJhbGxzTnVtIiwiY3JlYXRlQmFsbHMiLCJ3aW5kb3ciLCJyZXF1ZXN0QW5pbWF0aW9uRnJhbWUiLCJzdGVwIiwiYmluZCIsInNldFB1bGxCdXR0b25zIiwiYWRkRXZlbnRMaXN0ZW5lciIsImluY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwiLCJkZWNyZWFzZUdyYXZpdGF0aW9uYWxQdWxsIiwidG9nZ2xlQW5pbWF0ZSIsInNpemUiLCJpIiwiTWF0aCIsImNlaWwiLCJyYW5kb20iLCJjcmVhdGVCYWxsIiwicXVlcnlTZWxlY3RvckFsbCIsIm5ld0JhbGwiLCJzdHJldGNoRGlyIiwiY3JlYXRlRWxlbWVudCIsInJvdW5kIiwiY2xhc3NMaXN0IiwiYWRkIiwic3R5bGUiLCJ3aWR0aCIsImhlaWdodCIsImJhY2tncm91bmQiLCJnZXRSYW5kb21Db2xvciIsInNldEF0dHJpYnV0ZSIsIm9mZnNldFdpZHRoIiwiYXBwZW5kQ2hpbGQiLCJjYWxsU3RlcCIsInRpbWVzdGFtcCIsInByb2dyZXNzIiwieCIsInkiLCJzdHJldGNoIiwiZ3JpZFNpemUiLCJkdXJhdGlvbiIsInhQb3MiLCJ5UG9zIiwibGVuZ3RoIiwiZ2V0QXR0cmlidXRlIiwic2luIiwiUEkiLCJjb3MiLCJjbGllbnRXaWR0aCIsImNsaWVudEhlaWdodCIsInRyYW5zZm9ybSIsInpJbmRleCIsImlubmVySFRNTCIsInNldEdyYXZpdGF0aW9uYWxQdWxsIiwicGVyY2VudCIsIl90aGlzIiwic3RlcHMiLCJ0aW1lIiwiZGlyZWN0aW9uIiwiZGlzYWJsZVB1bGxCdXR0b25zIiwiYWJzIiwic2V0VGltZW91dCIsImRpc2FibGVkIiwiZ2V0Q3Vyc29yWFkiLCJlIiwiY3Vyc29yUG9zIiwiRXZlbnQiLCJwYWdlWCIsImV2ZW50IiwiY2xpZW50WCIsImRvY3VtZW50RWxlbWVudCIsInNjcm9sbExlZnQiLCJib2R5IiwicGFnZVkiLCJjbGllbnRZIiwic2Nyb2xsVG9wIiwibGVmdCIsInRvcCIsIm9mZnNldEhlaWdodCIsImNvbG9ycyIsImZsb29yIl0sInNvdXJjZVJvb3QiOiIifQ==

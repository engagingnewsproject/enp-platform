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
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
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
  _createClass(Orbit, [{
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
  return Orbit;
}();
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Orbit);

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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WyJ3ZWJwYWNrOi8vZW5nYWdlLTIteC8uL2Fzc2V0cy9qcy9jb21wb25lbnRzL09yYml0LmpzIl0sInNvdXJjZXNDb250ZW50IjpbIi8vIE9yaWdpbmFsIEphdmFTY3JpcHQgY29kZSBieSBDaGlycCBJbnRlcm5ldDogd3d3LmNoaXJwLmNvbS5hdVxuLy8gUGxlYXNlIGFja25vd2xlZGdlIHVzZSBvZiB0aGlzIGNvZGUgYnkgaW5jbHVkaW5nIHRoaXMgaGVhZGVyLlxuLy8gTW9kaWZpZWQgdmVyeSwgdmVyeSBoZWF2aWx5IGJ5IEplcmVteSBKb25lczogaHR0cHM6Ly9qZXJlbXlqb24uZXNcblxuXG5jbGFzcyBPcmJpdCB7XG4gIGNvbnN0cnVjdG9yKCkge1xuICAgIHRoaXMuZmllbGQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtZmllbGQnKVxuICAgIHRoaXMuYmFsbHNFbCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmJpdC1iYWxscycpXG4gICAgdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCA9IDgwXG5cbiAgICB0aGlzLmdyYXZpdHlUZXh0ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LWN1cnJlbnQtZ3Jhdml0eScpXG4gICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtaW5jcmVhc2UtcHVsbCcpXG4gICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JiaXQtZGVjcmVhc2UtcHVsbCcpXG4gICAgdGhpcy50b2dnbGVBbmltYXRlQnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yYml0LXRvZ2dsZS1hbmltYXRlJylcbiAgICB0aGlzLmJhbGxzID0gbnVsbFxuICAgIHRoaXMuYW5pbWF0ZSA9IHRydWVcblxuICAgIHRoaXMuYmFsbFNldHRpbmdzID0ge1xuICAgICAgbnVtOiA4MCxcbiAgICAgIG1pblNpemU6IDQsXG4gICAgICBtYXhTaXplOiAxMixcbiAgICB9XG5cbiAgICB0aGlzLnN0YXJ0ID0gMFxuXG4gICAgdGhpcy5pbml0KDgwKVxuICB9XG5cbiAgaW5pdChiYWxsc051bSkge1xuICAgIHRoaXMuYmFsbHMgPSB0aGlzLmNyZWF0ZUJhbGxzKGJhbGxzTnVtKVxuICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUodGhpcy5zdGVwLmJpbmQodGhpcykpXG4gICAgdGhpcy5zZXRQdWxsQnV0dG9ucyh0aGlzLmdyYXZpdGF0aW9uYWxQdWxsKVxuICAgIHRoaXMuaW5jcmVhc2VQdWxsQnRuLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgdGhpcy5pbmNyZWFzZUdyYXZpdGF0aW9uYWxQdWxsLmJpbmQodGhpcykpXG4gICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCB0aGlzLmRlY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwuYmluZCh0aGlzKSlcbiAgICB0aGlzLnRvZ2dsZUFuaW1hdGVCdG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCB0aGlzLnRvZ2dsZUFuaW1hdGUuYmluZCh0aGlzKSlcblxuXG4gICAgLy8gdW5jb21tZW50IHRvIGhhdmUgcGxhbmV0IHRyYWNrIGN1cnNvclxuICAgIC8vIGRvY3VtZW50Lm9ubW91c2Vtb3ZlID0gZ2V0Q3Vyc29yWFk7XG5cbiAgfVxuXG4gIGNyZWF0ZUJhbGxzKCkge1xuICAgIGxldCBzaXplO1xuICAgIGZvcihsZXQgaSA9IDA7IGkgPCB0aGlzLmJhbGxTZXR0aW5ncy5udW07IGkrKykge1xuICAgICAgLy8gZ2V0IHJhbmRvbSBzaXplIGJldHdlZW4gc2V0dGluZyBzaXplc1xuICAgICAgc2l6ZSA9IE1hdGguY2VpbCh0aGlzLmJhbGxTZXR0aW5ncy5taW5TaXplICsgKE1hdGgucmFuZG9tKCkgKiAodGhpcy5iYWxsU2V0dGluZ3MubWF4U2l6ZSAtIHRoaXMuYmFsbFNldHRpbmdzLm1pblNpemUpKSlcbiAgICAgIHRoaXMuY3JlYXRlQmFsbChzaXplKVxuICAgIH1cblxuICAgIC8vIHJldHVybiBhbGwgdGhlIGJhbGxzXG4gICAgcmV0dXJuIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5vcmJpdC1iYWxsJyk7XG4gIH1cblxuICBjcmVhdGVCYWxsKHNpemUpIHtcbiAgICBsZXQgbmV3QmFsbCwgc3RyZXRjaERpclxuXG4gICAgbmV3QmFsbCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIilcbiAgICBzdHJldGNoRGlyID0gKE1hdGgucm91bmQoKE1hdGgucmFuZG9tKCkgKiAxKSkgPyAneCcgOiAneScpXG4gICAgbmV3QmFsbC5jbGFzc0xpc3QuYWRkKCdvcmJpdC1iYWxsJylcbiAgICBuZXdCYWxsLnN0eWxlLndpZHRoID0gc2l6ZSArICdweCdcbiAgICBuZXdCYWxsLnN0eWxlLmhlaWdodCA9IHNpemUgKyAncHgnXG4gICAgbmV3QmFsbC5zdHlsZS5iYWNrZ3JvdW5kID0gdGhpcy5nZXRSYW5kb21Db2xvcigpO1xuICAgIG5ld0JhbGwuc2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtZGlyJywgc3RyZXRjaERpcikgLy8gZWl0aGVyIHggb3IgeVxuXG4gICAgLy8gVE9ETzogRGVjcmVhc2UgdGhlICdkYXRhLXN0cmV0Y2gtdmFsJyBhdHRyaWJ1dGUgdG8gZGVjcmVhc2UgdGhlIHNwcmVhZCBvZiB0aGUgYmFsbHNcbiAgICBuZXdCYWxsLnNldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLXZhbCcsICAxICsgKE1hdGgucmFuZG9tKCkgKiA1KSlcbiAgICBuZXdCYWxsLnNldEF0dHJpYnV0ZSgnZGF0YS1ncmlkJywgdGhpcy5maWVsZC5vZmZzZXRXaWR0aCArIE1hdGgucm91bmQoKE1hdGgucmFuZG9tKCkgKiAxMDApKSkgLy8gbWluIG9yYml0ID0gMzBweCwgbWF4IDEzMFxuICAgIG5ld0JhbGwuc2V0QXR0cmlidXRlKCdkYXRhLWR1cmF0aW9uJywgMy41ICsgTWF0aC5yb3VuZCgoTWF0aC5yYW5kb20oKSAqIDgpKSkgLy8gbWluIGR1cmF0aW9uID0gMy41cywgbWF4IDhzXG4gICAgbmV3QmFsbC5zZXRBdHRyaWJ1dGUoJ2RhdGEtc3RhcnQnLCAwKVxuICAgIHRoaXMuYmFsbHNFbC5hcHBlbmRDaGlsZChuZXdCYWxsKVxuICB9XG5cbiAgY2FsbFN0ZXAodGltZXN0YW1wKSB7XG4gICAgcmV0dXJuIHRoaXMuc3RlcCh0aW1lc3RhbXApXG4gIH1cblxuICBzdGVwKHRpbWVzdGFtcCkge1xuICAgIGxldCBwcm9ncmVzcywgeCwgeSwgc3RyZXRjaCwgZ3JpZFNpemUsIGR1cmF0aW9uLCBzdGFydCwgeFBvcywgeVBvc1xuICAgIGZvcihsZXQgaSA9IDA7IGkgPCB0aGlzLmJhbGxzLmxlbmd0aDsgaSsrKSB7XG5cbiAgICAgIHN0YXJ0ID0gdGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RhcnQnKVxuICAgICAgaWYoc3RhcnQgPT0gMCkge1xuICAgICAgICBzdGFydCA9IHRpbWVzdGFtcFxuICAgICAgICB0aGlzLmJhbGxzW2ldLnNldEF0dHJpYnV0ZSgnZGF0YS1zdGFydCcsIHN0YXJ0KVxuICAgICAgfVxuXG4gICAgICBncmlkU2l6ZSA9IHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLWdyaWQnKVxuICAgICAgZHVyYXRpb24gPSB0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1kdXJhdGlvbicpXG4gICAgICBwcm9ncmVzcyA9ICh0aW1lc3RhbXAgLSBzdGFydCkgLyBkdXJhdGlvbiAvIDEwMDAgLy8gcGVyY2VudFxuICAgICAgc3RyZXRjaCA9IHRoaXMuYmFsbHNbaV0uZ2V0QXR0cmlidXRlKCdkYXRhLXN0cmV0Y2gtdmFsJylcblxuICAgICAgaWYodGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RyZXRjaC1kaXInKSA9PT0gJ3gnKSB7XG4gICAgICAgIHggPSAoKHN0cmV0Y2ggKiBNYXRoLnNpbihwcm9ncmVzcyAqIDIgKiBNYXRoLlBJKSkgKiAoMS4wNSAtICh0aGlzLmdyYXZpdGF0aW9uYWxQdWxsLzEwMCkpKS8vIHggPSDGkih0KVxuICAgICAgICB5ID0gTWF0aC5jb3MocHJvZ3Jlc3MgKiAyICogTWF0aC5QSSkgLy8geSA9IMaSKHQpXG4gICAgICB9IGVsc2Uge1xuICAgICAgICB4ID0gTWF0aC5zaW4ocHJvZ3Jlc3MgKiAyICogTWF0aC5QSSkgLy8geCA9IMaSKHQpXG4gICAgICAgIHkgPSAoKHN0cmV0Y2ggKiBNYXRoLmNvcyhwcm9ncmVzcyAqIDIgKiBNYXRoLlBJKSkgKiAoMS4wNSAtICh0aGlzLmdyYXZpdGF0aW9uYWxQdWxsLzEwMCkpKSAvLyB5ID0gxpIodClcbiAgICAgIH1cblxuICAgICAgeFBvcyA9IHRoaXMuZmllbGQuY2xpZW50V2lkdGgvMiArIChncmlkU2l6ZSAqIHgpXG4gICAgICB5UG9zID0gdGhpcy5maWVsZC5jbGllbnRIZWlnaHQvMiArIChncmlkU2l6ZSAqIHkpXG4gICAgICB0aGlzLmJhbGxzW2ldLnN0eWxlLnRyYW5zZm9ybSA9ICd0cmFuc2xhdGUzZCgnK3hQb3MgKyAncHgsICcreVBvcyArICdweCwgMCknXG5cbiAgICAgIC8vIGlmIHRoZXNlIGFyZSB0cnVlLCB0aGVuIGl0J3MgYmVoaW5kIHRoZSBwbGFuZXRcbiAgICAgIGlmKCgodGhpcy5iYWxsc1tpXS5nZXRBdHRyaWJ1dGUoJ2RhdGEtc3RyZXRjaC1kaXInKSA9PT0gJ3gnKSAmJiAoKCh0aGlzLmZpZWxkLm9mZnNldFdpZHRoLzIpIC0gdGhpcy5iYWxsc1tpXS5vZmZzZXRXaWR0aCkgKiAtMSkgPCB4UG9zICYmIHhQb3MgPCAoKHRoaXMuZmllbGQub2Zmc2V0V2lkdGgvMikgKyB0aGlzLmJhbGxzW2ldLm9mZnNldFdpZHRoKSkgfHwgKCh0aGlzLmJhbGxzW2ldLmdldEF0dHJpYnV0ZSgnZGF0YS1zdHJldGNoLWRpcicpID09PSAneScpICYmICgoKHRoaXMuZmllbGQub2Zmc2V0V2lkdGgvMikgLSB0aGlzLmJhbGxzW2ldLm9mZnNldFdpZHRoKSAqIC0xKSA8IHlQb3MgJiYgeVBvcyA8ICgodGhpcy5maWVsZC5vZmZzZXRXaWR0aC8yKSArIHRoaXMuYmFsbHNbaV0ub2Zmc2V0V2lkdGgpKSkge1xuICAgICAgICAvLyBiYWNrc2lkZSBvZiB0aGUgbW9vblxuICAgICAgICB0aGlzLmJhbGxzW2ldLnN0eWxlLnpJbmRleCA9ICctMSdcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIC8vIC4uLmZyb250IHNpZGUgb2YgdGhlIG1vb25cbiAgICAgICAgdGhpcy5iYWxsc1tpXS5zdHlsZS56SW5kZXggPSAnOSdcbiAgICAgIH1cblxuICAgICAgaWYocHJvZ3Jlc3MgPj0gMSkge1xuICAgICAgICB0aGlzLmJhbGxzW2ldLnNldEF0dHJpYnV0ZSgnZGF0YS1zdGFydCcsIDApIC8vIHJlc2V0IHRvIHN0YXJ0IHBvc2l0aW9uXG4gICAgICB9XG4gICAgfVxuICAgIGlmKHRoaXMuYW5pbWF0ZSA9PSB0cnVlKSB7XG4gICAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lKHRoaXMuc3RlcC5iaW5kKHRoaXMpKVxuICAgIH1cblxuICB9XG5cbiAgdG9nZ2xlQW5pbWF0ZSgpIHtcblxuICAgIHRoaXMuYW5pbWF0ZSA9ICF0aGlzLmFuaW1hdGVcbiAgICBpZih0aGlzLmFuaW1hdGUpIHtcbiAgICAgIHRoaXMudG9nZ2xlQW5pbWF0ZUJ0bi5pbm5lckhUTUwgPSAnPGkgY2xhc3M9XCJmYXMgZmEtcGF1c2VcIj48L2k+J1xuICAgICAgLy8gcmVzdW1lIHRoZSBhbmltYXRpb25cbiAgICAgIHdpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUodGhpcy5zdGVwLmJpbmQodGhpcykpXG4gICAgfSBlbHNlIHtcbiAgICAgIHRoaXMudG9nZ2xlQW5pbWF0ZUJ0bi5pbm5lckhUTUwgPSAnPGkgY2xhc3M9XCJmYXMgZmEtcGxheVwiPjwvaT4nXG4gICAgfVxuICB9XG5cbiAgLy8gc2luY2UgSSBkb24ndCBrbm93IHBoeXNpY3MsIHRoaXMgaXMgYW4gYXBwcm9yaXhpbWF0aW9uXG4gIHNldEdyYXZpdGF0aW9uYWxQdWxsKHBlcmNlbnQpIHtcbiAgICBsZXQgc3RlcCwgc3RlcHMsIHRpbWUsIGRpcmVjdGlvblxuXG4gICAgdGhpcy5kaXNhYmxlUHVsbEJ1dHRvbnMoKVxuXG4gICAgaWYocGVyY2VudCA8IDApIHtcbiAgICAgIHJldHVyblxuICAgIH1cblxuICAgIGlmKDEwMCA8IHBlcmNlbnQpIHtcbiAgICAgIHJldHVyblxuICAgIH1cblxuICAgIGlmKHBlcmNlbnQgPT09IHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwpIHtcbiAgICAgIHJldHVyblxuICAgIH1cblxuICAgIHN0ZXBzID0gMjBcbiAgICBzdGVwID0gTWF0aC5hYnMocGVyY2VudCAtIHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwpL3N0ZXBzXG4gICAgZGlyZWN0aW9uID0gKHBlcmNlbnQgPCB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsID8gJy0nIDogJysnKVxuXG4gICAgLy8gZ2V0IHRoZSBjdXJyZW50IHB1bGwgYW5kIHN0ZXAgaXQgZG93biBvdmVyIDIwIHN0ZXBzIHNvIGl0J3Mgc21vb3RoZXIgdGhhbiBqdW1waW5nIHN0cmFpZ2h0IHRoZXJlXG4gICAgZm9yKGxldCBpID0gMDsgaSA8IHN0ZXBzOyBpKyspIHtcbiAgICAgIC8vIHNldCB0aGUgdGltZSB0aGlzIHdpbGwgZmlyZVxuICAgICAgdGltZSA9IGkgKiAoaS9NYXRoLlBJKVxuICAgICAgLy8gbWluaW11bSB0aW1lIHNwYW5cbiAgICAgIGlmKHRpbWUgPCA0KSB7XG4gICAgICAgIHRpbWUgPSA0XG4gICAgICB9XG4gICAgICAvLyBzZXQgdGhlIGZ1bmN0aW9uXG4gICAgICBzZXRUaW1lb3V0KCgpPT57XG4gICAgICAgIGlmKGRpcmVjdGlvbiA9PT0gJy0nKSB7XG4gICAgICAgICAgdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCAtPSBzdGVwXG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCArPSBzdGVwXG4gICAgICAgIH1cblxuICAgICAgfSwgdGltZSk7XG5cbiAgICAgIC8vIG9uIG91ciBsYXN0IG9uZSwgc2V0IHRoZSBncmF2aXRhdGlvbmFsUHVsbCB0byBpdHMgZmluYWwsIG5pY2VseSByb3VuZGVkIG51bWJlclxuICAgICAgaWYoaSA9PT0gc3RlcHMgLSAxKSB7XG4gICAgICAgIHNldFRpbWVvdXQoKCk9PntcbiAgICAgICAgICB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsID0gTWF0aC5yb3VuZCh0aGlzLmdyYXZpdGF0aW9uYWxQdWxsKVxuICAgICAgICAgIHRoaXMuc2V0UHVsbEJ1dHRvbnMoKVxuICAgICAgICB9LCB0aW1lICsgMjApXG4gICAgICB9XG4gICAgfVxuXG4gIH1cblxuXG4gIHNldFB1bGxCdXR0b25zKCkge1xuICAgIGlmKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgPD0gMCkge1xuICAgICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSB0cnVlXG4gICAgICB0aGlzLmluY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IGZhbHNlXG4gICAgfVxuXG4gICAgZWxzZSBpZigxMDAgPD0gdGhpcy5ncmF2aXRhdGlvbmFsUHVsbCkge1xuICAgICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSBmYWxzZVxuICAgICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSB0cnVlXG4gICAgfVxuXG4gICAgZWxzZSB7XG4gICAgICB0aGlzLmRlY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IGZhbHNlXG4gICAgICB0aGlzLmluY3JlYXNlUHVsbEJ0bi5kaXNhYmxlZCA9IGZhbHNlXG4gICAgfVxuXG4gICAgdGhpcy5ncmF2aXR5VGV4dC5pbm5lckhUTUwgPSB0aGlzLmdyYXZpdGF0aW9uYWxQdWxsXG4gIH1cblxuICBkaXNhYmxlUHVsbEJ1dHRvbnMoKSB7XG4gICAgdGhpcy5kZWNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSB0cnVlXG4gICAgdGhpcy5pbmNyZWFzZVB1bGxCdG4uZGlzYWJsZWQgPSB0cnVlXG4gIH1cblxuICBpbmNyZWFzZUdyYXZpdGF0aW9uYWxQdWxsKCkge1xuICAgIHRoaXMuc2V0R3Jhdml0YXRpb25hbFB1bGwodGhpcy5ncmF2aXRhdGlvbmFsUHVsbCArIDEwKVxuICB9XG5cbiAgZGVjcmVhc2VHcmF2aXRhdGlvbmFsUHVsbCgpIHtcbiAgICB0aGlzLnNldEdyYXZpdGF0aW9uYWxQdWxsKHRoaXMuZ3Jhdml0YXRpb25hbFB1bGwgLSAxMClcbiAgfVxuXG4gIC8vIGlmIHlvdSB3YW50IHRoZSBwbGFuZXQgdG8gdHJhY2sgdGhlIGN1cnNvclxuICBnZXRDdXJzb3JYWShlKSB7XG4gICAgbGV0IGN1cnNvclBvcyA9IHtcbiAgICAgIHg6ICh3aW5kb3cuRXZlbnQpID8gZS5wYWdlWCA6IGV2ZW50LmNsaWVudFggKyAoZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnNjcm9sbExlZnQgPyBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc2Nyb2xsTGVmdCA6IGRvY3VtZW50LmJvZHkuc2Nyb2xsTGVmdCksXG4gICAgICB5OiAod2luZG93LkV2ZW50KSA/IGUucGFnZVkgOiBldmVudC5jbGllbnRZICsgKGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zY3JvbGxUb3AgPyBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc2Nyb2xsVG9wIDogZG9jdW1lbnQuYm9keS5zY3JvbGxUb3ApXG4gICAgfVxuXG4gICAgdGhpcy5maWVsZC5zdHlsZS5sZWZ0ID0gY3Vyc29yUG9zLnggLSAodGhpcy5maWVsZC5vZmZzZXRXaWR0aC8yKSArIFwicHhcIlxuICAgIHRoaXMuZmllbGQuc3R5bGUudG9wID0gY3Vyc29yUG9zLnkgLSAodGhpcy5maWVsZC5vZmZzZXRIZWlnaHQvMikgKyBcInB4XCJcbiAgfVxuXG4gIGdldFJhbmRvbUNvbG9yKCkge1xuICAgIGxldCBjb2xvcnMgPSBbXG4gICAgICAnIzAwYTliNycsXG4gICAgICAnIzAwNWY4NicsXG4gICAgICAnI2Q2ZDJjNCcsXG4gICAgICAnI2Y4OTcxZicsXG4gICAgICAnI0JGNTcwMCcsXG4gICAgICAnI2Q5NTM0ZidcbiAgICBdXG5cbiAgICByZXR1cm4gY29sb3JzW01hdGguZmxvb3IoKE1hdGgucmFuZG9tKCkgKiBjb2xvcnMubGVuZ3RoKSldXG4gIH1cbn1cblxuZXhwb3J0IGRlZmF1bHQgT3JiaXQ7XG4iXSwibmFtZXMiOlsiT3JiaXQiLCJfY2xhc3NDYWxsQ2hlY2siLCJmaWVsZCIsImRvY3VtZW50IiwiZ2V0RWxlbWVudEJ5SWQiLCJiYWxsc0VsIiwiZ3Jhdml0YXRpb25hbFB1bGwiLCJncmF2aXR5VGV4dCIsImluY3JlYXNlUHVsbEJ0biIsImRlY3JlYXNlUHVsbEJ0biIsInRvZ2dsZUFuaW1hdGVCdG4iLCJiYWxscyIsImFuaW1hdGUiLCJiYWxsU2V0dGluZ3MiLCJudW0iLCJtaW5TaXplIiwibWF4U2l6ZSIsInN0YXJ0IiwiaW5pdCIsIl9jcmVhdGVDbGFzcyIsImtleSIsInZhbHVlIiwiYmFsbHNOdW0iLCJjcmVhdGVCYWxscyIsIndpbmRvdyIsInJlcXVlc3RBbmltYXRpb25GcmFtZSIsInN0ZXAiLCJiaW5kIiwic2V0UHVsbEJ1dHRvbnMiLCJhZGRFdmVudExpc3RlbmVyIiwiaW5jcmVhc2VHcmF2aXRhdGlvbmFsUHVsbCIsImRlY3JlYXNlR3Jhdml0YXRpb25hbFB1bGwiLCJ0b2dnbGVBbmltYXRlIiwic2l6ZSIsImkiLCJNYXRoIiwiY2VpbCIsInJhbmRvbSIsImNyZWF0ZUJhbGwiLCJxdWVyeVNlbGVjdG9yQWxsIiwibmV3QmFsbCIsInN0cmV0Y2hEaXIiLCJjcmVhdGVFbGVtZW50Iiwicm91bmQiLCJjbGFzc0xpc3QiLCJhZGQiLCJzdHlsZSIsIndpZHRoIiwiaGVpZ2h0IiwiYmFja2dyb3VuZCIsImdldFJhbmRvbUNvbG9yIiwic2V0QXR0cmlidXRlIiwib2Zmc2V0V2lkdGgiLCJhcHBlbmRDaGlsZCIsImNhbGxTdGVwIiwidGltZXN0YW1wIiwicHJvZ3Jlc3MiLCJ4IiwieSIsInN0cmV0Y2giLCJncmlkU2l6ZSIsImR1cmF0aW9uIiwieFBvcyIsInlQb3MiLCJsZW5ndGgiLCJnZXRBdHRyaWJ1dGUiLCJzaW4iLCJQSSIsImNvcyIsImNsaWVudFdpZHRoIiwiY2xpZW50SGVpZ2h0IiwidHJhbnNmb3JtIiwiekluZGV4IiwiaW5uZXJIVE1MIiwic2V0R3Jhdml0YXRpb25hbFB1bGwiLCJwZXJjZW50IiwiX3RoaXMiLCJzdGVwcyIsInRpbWUiLCJkaXJlY3Rpb24iLCJkaXNhYmxlUHVsbEJ1dHRvbnMiLCJhYnMiLCJzZXRUaW1lb3V0IiwiZGlzYWJsZWQiLCJnZXRDdXJzb3JYWSIsImUiLCJjdXJzb3JQb3MiLCJFdmVudCIsInBhZ2VYIiwiZXZlbnQiLCJjbGllbnRYIiwiZG9jdW1lbnRFbGVtZW50Iiwic2Nyb2xsTGVmdCIsImJvZHkiLCJwYWdlWSIsImNsaWVudFkiLCJzY3JvbGxUb3AiLCJsZWZ0IiwidG9wIiwib2Zmc2V0SGVpZ2h0IiwiY29sb3JzIiwiZmxvb3IiXSwic291cmNlUm9vdCI6IiJ9
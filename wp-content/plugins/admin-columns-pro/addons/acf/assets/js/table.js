/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./js/editable/acf-link.ts":
/*!*********************************!*\
  !*** ./js/editable/acf-link.ts ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
AC_SERVICES.addListener('Editing.Editables.Ready', (EditablesStack) => {
    const Abstract = EditablesStack.get('_abstract');
    class LinkEditable extends Abstract {
        getEditableType() {
            return "acf_link";
        }
        focus() {
            this.getElement().querySelector('[name=url]').focus();
        }
        valueToInput(value) {
            if (!value) {
                return;
            }
            this.getElement().querySelector('[name=url]').value = value.url;
            this.getElement().querySelector('[name=title]').value = value.title;
            if (value.target === '_blank') {
                this.getElement().querySelector('[name=target]').checked = true;
            }
        }
        getValue() {
            return {
                url: this.getElement().querySelector('[name=url]').value,
                title: this.getElement().querySelector('[name=title]').value,
                target: this.getElement().querySelector('[name=target]').checked ? '_blank' : ''
            };
        }
        getTemplate() {
            const url = this.getEditableTemplate().getFormHelper().input('url', null, { placeholder: 'http://' }).outerHTML;
            const title = this.getEditableTemplate().getFormHelper().input('title', null).outerHTML;
            return `
				<div class="input__group">
					<label>Url</label>${url}
				</div>
				<div class="input__group">
					<label>Title</label>${title}
				</div>
				<div class="input__group -checkbox">
					<label class="input__checkbox"><input type="checkbox" name="target"> Open link in a new tab</label>
				</div>
			`;
        }
    }
    EditablesStack.registerEditable('acf_link', LinkEditable);
});
// Register MiddleWare
AC_SERVICES.addListener('Editing.Middleware.Ready', (Middleware) => {
    const Abstract = Middleware.getClass('_abstract');
    class LinkMiddleware extends Abstract {
        getEditable() {
            return this.Editables.get('acf_link');
        }
    }
    Middleware.register('acf_link', LinkMiddleware);
});



/***/ }),

/***/ "./js/editable/acf-range.ts":
/*!**********************************!*\
  !*** ./js/editable/acf-range.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
AC_SERVICES.addListener('Editing.Editables.Ready', (EditablesStack) => {
    const Abstract = EditablesStack.get('_abstract');
    class RangeEditable extends Abstract {
        focus() {
            this.getElement().querySelector('[name=range]').focus();
        }
        getEditableType() {
            return "acf_range";
        }
        render() {
            let slider = this.getElement().querySelector('[name=range]');
            this.reflect();
            slider.addEventListener('change', () => this.reflect());
            slider.addEventListener('input', () => this.reflect());
        }
        reflect() {
            this.getElement().querySelector('[name=reflection]').value = this.getElement().querySelector('[name=range]').value;
        }
        valueToInput(value) {
            this.getElement().querySelector('[name=range]').value = value;
            this.reflect();
        }
        getValue() {
            return this.getElement().querySelector('[name=range]').value;
        }
        getTemplate() {
            const range = this.getEditableTemplate().getFormHelper().input('range', null, this.settings.html_attributes).outerHTML;
            return `
				<div class="input__group">
					${range} <input name="reflection">
				</div>
			`;
        }
        getDefaults() {
            let defaults = super.getDefaults();
            defaults.html_attributes = {
                type: 'range',
                step: 5,
                min: 0,
                max: 100
            };
            return defaults;
        }
    }
    EditablesStack.registerEditable('acf_range', RangeEditable);
});
// Register MiddleWare
AC_SERVICES.addListener('Editing.Middleware.Ready', (Middleware) => {
    const Abstract = Middleware.getClass('default');
    class RangeMiddleware extends Abstract {
        getEditable() {
            return this.Editables.get('acf_range');
        }
    }
    Middleware.register('acf_range', RangeMiddleware);
});



/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*********************!*\
  !*** ./js/table.ts ***!
  \*********************/
__webpack_require__(/*! ./editable/acf-range */ "./js/editable/acf-range.ts");
__webpack_require__(/*! ./editable/acf-link */ "./js/editable/acf-link.ts");

})();

/******/ })()
;
//# sourceMappingURL=table.js.map
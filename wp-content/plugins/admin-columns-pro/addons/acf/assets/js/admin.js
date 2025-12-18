/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
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
/*!*********************!*\
  !*** ./js/admin.ts ***!
  \*********************/
__webpack_require__.r(__webpack_exports__);
const isAcfColumn = (result) => {
    if (result.element) {
        let group = result.element.parentElement;
        if (group && 'Advanced Custom Fields' === group.label) {
            return true;
        }
    }
    let id = result.id;
    return id.indexOf('field_') === 0 || id.indexOf('acfgroup__field') === 0 || id.indexOf('acfclone') === 0;
};
document.addEventListener('DOMContentLoaded', () => {
    let acfIcon = aca_acf_admin.assets + 'images/acf.png';
    AC_SERVICES.filters.addFilter('column_type_templates', (value, pl) => {
        if (pl.result.hasOwnProperty('id') && isAcfColumn(pl.result)) {
            value += `<img src="${acfIcon}" alt="Advanced Custom Fields" class="ac-column-type-icon"/>`;
        }
        return value;
    }, 10);
});


/******/ })()
;
//# sourceMappingURL=admin.js.map
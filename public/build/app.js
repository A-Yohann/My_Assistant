"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["app"],{

/***/ "./assets/app.js"
/*!***********************!*\
  !*** ./assets/app.js ***!
  \***********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.number.to-fixed.js */ "./node_modules/core-js/modules/es.number.to-fixed.js");
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_parse_float_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.parse-float.js */ "./node_modules/core-js/modules/es.parse-float.js");
/* harmony import */ var core_js_modules_es_parse_float_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_parse_float_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/esnext.iterator.for-each.js */ "./node_modules/core-js/modules/esnext.iterator.for-each.js");
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _styles_app_css__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./styles/app.css */ "./assets/styles/app.css");







// Script de calcul des totaux pour la génération de devis
document.addEventListener('DOMContentLoaded', function () {
  function updateTotals() {
    var totalHT = 0;
    document.querySelectorAll('#articlesTable tbody tr').forEach(function (row) {
      var qty = parseFloat(row.querySelector('.article-qty').value) || 0;
      var price = parseFloat(row.querySelector('.article-price').value) || 0;
      var lineTotal = qty * price;
      row.querySelector('.article-total').textContent = lineTotal.toFixed(2) + ' €';
      totalHT += lineTotal;
    });
    var totalHTSpan = document.getElementById('totalHT');
    var totalTVASpan = document.getElementById('totalTVA');
    var totalTTCSpan = document.getElementById('totalTTC');
    if (totalHTSpan && totalTVASpan && totalTTCSpan) {
      totalHTSpan.textContent = totalHT.toFixed(2) + ' €';
      var tva = totalHT * 0.2;
      totalTVASpan.textContent = tva.toFixed(2) + ' €';
      totalTTCSpan.textContent = (totalHT + tva).toFixed(2) + ' €';
    }
  }
  document.querySelectorAll('.article-qty, .article-price').forEach(function (input) {
    input.addEventListener('input', updateTotals);
  });
  updateTotals();
});
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)


/***/ },

/***/ "./assets/styles/app.css"
/*!*******************************!*\
  !*** ./assets/styles/app.css ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_modules_es_array_for-each_js-node_modules_core-js_modules_es_num-602714"], () => (__webpack_exec__("./assets/app.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYXBwLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBQUE7QUFDQUEsUUFBUSxDQUFDQyxnQkFBZ0IsQ0FBQyxrQkFBa0IsRUFBRSxZQUFXO0VBQ3hELFNBQVNDLFlBQVlBLENBQUEsRUFBRztJQUN2QixJQUFJQyxPQUFPLEdBQUcsQ0FBQztJQUNmSCxRQUFRLENBQUNJLGdCQUFnQixDQUFDLHlCQUF5QixDQUFDLENBQUNDLE9BQU8sQ0FBQyxVQUFTQyxHQUFHLEVBQUU7TUFDMUUsSUFBTUMsR0FBRyxHQUFHQyxVQUFVLENBQUNGLEdBQUcsQ0FBQ0csYUFBYSxDQUFDLGNBQWMsQ0FBQyxDQUFDQyxLQUFLLENBQUMsSUFBSSxDQUFDO01BQ3BFLElBQU1DLEtBQUssR0FBR0gsVUFBVSxDQUFDRixHQUFHLENBQUNHLGFBQWEsQ0FBQyxnQkFBZ0IsQ0FBQyxDQUFDQyxLQUFLLENBQUMsSUFBSSxDQUFDO01BQ3hFLElBQU1FLFNBQVMsR0FBR0wsR0FBRyxHQUFHSSxLQUFLO01BQzdCTCxHQUFHLENBQUNHLGFBQWEsQ0FBQyxnQkFBZ0IsQ0FBQyxDQUFDSSxXQUFXLEdBQUdELFNBQVMsQ0FBQ0UsT0FBTyxDQUFDLENBQUMsQ0FBQyxHQUFHLElBQUk7TUFDN0VYLE9BQU8sSUFBSVMsU0FBUztJQUNyQixDQUFDLENBQUM7SUFDRixJQUFNRyxXQUFXLEdBQUdmLFFBQVEsQ0FBQ2dCLGNBQWMsQ0FBQyxTQUFTLENBQUM7SUFDdEQsSUFBTUMsWUFBWSxHQUFHakIsUUFBUSxDQUFDZ0IsY0FBYyxDQUFDLFVBQVUsQ0FBQztJQUN4RCxJQUFNRSxZQUFZLEdBQUdsQixRQUFRLENBQUNnQixjQUFjLENBQUMsVUFBVSxDQUFDO0lBQ3hELElBQUlELFdBQVcsSUFBSUUsWUFBWSxJQUFJQyxZQUFZLEVBQUU7TUFDaERILFdBQVcsQ0FBQ0YsV0FBVyxHQUFHVixPQUFPLENBQUNXLE9BQU8sQ0FBQyxDQUFDLENBQUMsR0FBRyxJQUFJO01BQ25ELElBQU1LLEdBQUcsR0FBR2hCLE9BQU8sR0FBRyxHQUFHO01BQ3pCYyxZQUFZLENBQUNKLFdBQVcsR0FBR00sR0FBRyxDQUFDTCxPQUFPLENBQUMsQ0FBQyxDQUFDLEdBQUcsSUFBSTtNQUNoREksWUFBWSxDQUFDTCxXQUFXLEdBQUcsQ0FBQ1YsT0FBTyxHQUFHZ0IsR0FBRyxFQUFFTCxPQUFPLENBQUMsQ0FBQyxDQUFDLEdBQUcsSUFBSTtJQUM3RDtFQUNEO0VBRUFkLFFBQVEsQ0FBQ0ksZ0JBQWdCLENBQUMsOEJBQThCLENBQUMsQ0FBQ0MsT0FBTyxDQUFDLFVBQVNlLEtBQUssRUFBRTtJQUNqRkEsS0FBSyxDQUFDbkIsZ0JBQWdCLENBQUMsT0FBTyxFQUFFQyxZQUFZLENBQUM7RUFDOUMsQ0FBQyxDQUFDO0VBRUZBLFlBQVksQ0FBQyxDQUFDO0FBQ2YsQ0FBQyxDQUFDO0FBQ0Y7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBOzs7Ozs7Ozs7Ozs7QUNuQ0EiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9hc3NldHMvYXBwLmpzIiwid2VicGFjazovLy8uL2Fzc2V0cy9zdHlsZXMvYXBwLmNzcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvLyBTY3JpcHQgZGUgY2FsY3VsIGRlcyB0b3RhdXggcG91ciBsYSBnw6luw6lyYXRpb24gZGUgZGV2aXNcclxuZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcignRE9NQ29udGVudExvYWRlZCcsIGZ1bmN0aW9uKCkge1xyXG5cdGZ1bmN0aW9uIHVwZGF0ZVRvdGFscygpIHtcclxuXHRcdGxldCB0b3RhbEhUID0gMDtcclxuXHRcdGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJyNhcnRpY2xlc1RhYmxlIHRib2R5IHRyJykuZm9yRWFjaChmdW5jdGlvbihyb3cpIHtcclxuXHRcdFx0Y29uc3QgcXR5ID0gcGFyc2VGbG9hdChyb3cucXVlcnlTZWxlY3RvcignLmFydGljbGUtcXR5JykudmFsdWUpIHx8IDA7XHJcblx0XHRcdGNvbnN0IHByaWNlID0gcGFyc2VGbG9hdChyb3cucXVlcnlTZWxlY3RvcignLmFydGljbGUtcHJpY2UnKS52YWx1ZSkgfHwgMDtcclxuXHRcdFx0Y29uc3QgbGluZVRvdGFsID0gcXR5ICogcHJpY2U7XHJcblx0XHRcdHJvdy5xdWVyeVNlbGVjdG9yKCcuYXJ0aWNsZS10b3RhbCcpLnRleHRDb250ZW50ID0gbGluZVRvdGFsLnRvRml4ZWQoMikgKyAnIOKCrCc7XHJcblx0XHRcdHRvdGFsSFQgKz0gbGluZVRvdGFsO1xyXG5cdFx0fSk7XHJcblx0XHRjb25zdCB0b3RhbEhUU3BhbiA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCd0b3RhbEhUJyk7XHJcblx0XHRjb25zdCB0b3RhbFRWQVNwYW4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgndG90YWxUVkEnKTtcclxuXHRcdGNvbnN0IHRvdGFsVFRDU3BhbiA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCd0b3RhbFRUQycpO1xyXG5cdFx0aWYgKHRvdGFsSFRTcGFuICYmIHRvdGFsVFZBU3BhbiAmJiB0b3RhbFRUQ1NwYW4pIHtcclxuXHRcdFx0dG90YWxIVFNwYW4udGV4dENvbnRlbnQgPSB0b3RhbEhULnRvRml4ZWQoMikgKyAnIOKCrCc7XHJcblx0XHRcdGNvbnN0IHR2YSA9IHRvdGFsSFQgKiAwLjI7XHJcblx0XHRcdHRvdGFsVFZBU3Bhbi50ZXh0Q29udGVudCA9IHR2YS50b0ZpeGVkKDIpICsgJyDigqwnO1xyXG5cdFx0XHR0b3RhbFRUQ1NwYW4udGV4dENvbnRlbnQgPSAodG90YWxIVCArIHR2YSkudG9GaXhlZCgyKSArICcg4oKsJztcclxuXHRcdH1cclxuXHR9XHJcblxyXG5cdGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5hcnRpY2xlLXF0eSwgLmFydGljbGUtcHJpY2UnKS5mb3JFYWNoKGZ1bmN0aW9uKGlucHV0KSB7XHJcblx0XHRpbnB1dC5hZGRFdmVudExpc3RlbmVyKCdpbnB1dCcsIHVwZGF0ZVRvdGFscyk7XHJcblx0fSk7XHJcblxyXG5cdHVwZGF0ZVRvdGFscygpO1xyXG59KTtcclxuLypcclxuICogV2VsY29tZSB0byB5b3VyIGFwcCdzIG1haW4gSmF2YVNjcmlwdCBmaWxlIVxyXG4gKlxyXG4gKiBXZSByZWNvbW1lbmQgaW5jbHVkaW5nIHRoZSBidWlsdCB2ZXJzaW9uIG9mIHRoaXMgSmF2YVNjcmlwdCBmaWxlXHJcbiAqIChhbmQgaXRzIENTUyBmaWxlKSBpbiB5b3VyIGJhc2UgbGF5b3V0IChiYXNlLmh0bWwudHdpZykuXHJcbiAqL1xyXG5cclxuLy8gYW55IENTUyB5b3UgaW1wb3J0IHdpbGwgb3V0cHV0IGludG8gYSBzaW5nbGUgY3NzIGZpbGUgKGFwcC5jc3MgaW4gdGhpcyBjYXNlKVxyXG5pbXBvcnQgJy4vc3R5bGVzL2FwcC5jc3MnO1xyXG5cclxuXHJcbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyJdLCJuYW1lcyI6WyJkb2N1bWVudCIsImFkZEV2ZW50TGlzdGVuZXIiLCJ1cGRhdGVUb3RhbHMiLCJ0b3RhbEhUIiwicXVlcnlTZWxlY3RvckFsbCIsImZvckVhY2giLCJyb3ciLCJxdHkiLCJwYXJzZUZsb2F0IiwicXVlcnlTZWxlY3RvciIsInZhbHVlIiwicHJpY2UiLCJsaW5lVG90YWwiLCJ0ZXh0Q29udGVudCIsInRvRml4ZWQiLCJ0b3RhbEhUU3BhbiIsImdldEVsZW1lbnRCeUlkIiwidG90YWxUVkFTcGFuIiwidG90YWxUVENTcGFuIiwidHZhIiwiaW5wdXQiXSwic291cmNlUm9vdCI6IiJ9
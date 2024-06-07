"use strict";
self["webpackHotUpdategk_gravityview_blocks"]("view",{

/***/ "./shared/js/sort-selector.js":
/*!************************************!*\
  !*** ./shared/js/sort-selector.js ***!
  \************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ SortFieldSelector; }
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/regenerator */ "@babel/runtime/regenerator");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var react_select__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react-select */ "./node_modules/react-select/dist/react-select.esm.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__);
/* provided dependency */ var __react_refresh_utils__ = __webpack_require__(/*! ./node_modules/@pmmmwh/react-refresh-webpack-plugin/lib/runtime/RefreshUtils.js */ "./node_modules/@pmmmwh/react-refresh-webpack-plugin/lib/runtime/RefreshUtils.js");
/* provided dependency */ var __react_refresh_error_overlay__ = __webpack_require__(/*! ./node_modules/@pmmmwh/react-refresh-webpack-plugin/overlay/index.js */ "./node_modules/@pmmmwh/react-refresh-webpack-plugin/overlay/index.js");
__webpack_require__.$Refresh$.runtime = __webpack_require__(/*! ./node_modules/react-refresh/runtime.js */ "react-refresh/runtime");





var _s = __webpack_require__.$Refresh$.signature();

function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }





function SortFieldSelector(_ref) {
  _s();
  var viewId = _ref.viewId,
    _onChange = _ref.onChange,
    sortField = _ref.sortField;
  var labels = {
    selectSortField: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("Select a Sort Field", "gk-gravityview"),
    sort: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("Sort", "gk-gravityview")
  };
  var defaultOption = {
    value: "",
    label: labels.selectSortField
  };
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_4__.useState)([defaultOption]),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__["default"])(_useState, 2),
    options = _useState2[0],
    setOptions = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_4__.useState)(defaultOption),
    _useState4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_3__["default"])(_useState3, 2),
    selectedSortField = _useState4[0],
    setSelectedSortField = _useState4[1];
  var fetchData = /*#__PURE__*/function () {
    var _ref2 = (0,_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__["default"])( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_5___default().mark(function _callee(viewId) {
      var response, text, parser, doc, optionElements, newOptions, selectedOption;
      return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_5___default().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            _context.prev = 0;
            _context.next = 3;
            return fetch(gkGravityViewBlocks.ajax_url, {
              method: "POST",
              headers: {
                "Content-Type": "application/x-www-form-urlencoded"
              },
              body: new URLSearchParams({
                action: "gv_sortable_fields",
                nonce: gkGravityViewBlocks.nonce,
                viewid: viewId
              })
            });
          case 3:
            response = _context.sent;
            if (!(response.status === 200)) {
              _context.next = 17;
              break;
            }
            _context.next = 7;
            return response.text();
          case 7:
            text = _context.sent;
            parser = new DOMParser();
            doc = parser.parseFromString(text, "text/html");
            optionElements = doc.querySelectorAll("option");
            newOptions = Array.from(optionElements).map(function (option) {
              return {
                value: option.value,
                label: option.textContent
              };
            });
            setOptions([defaultOption].concat((0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(newOptions)));
            selectedOption = newOptions.find(function (option) {
              return option.value === sortField;
            }) || defaultOption;
            setSelectedSortField(selectedOption);
            _context.next = 18;
            break;
          case 17:
            console.error("Error:", response);
          case 18:
            _context.next = 23;
            break;
          case 20:
            _context.prev = 20;
            _context.t0 = _context["catch"](0);
            console.error("Fetch error:", _context.t0);
          case 23:
          case "end":
            return _context.stop();
        }
      }, _callee, null, [[0, 20]]);
    }));
    return function fetchData(_x) {
      return _ref2.apply(this, arguments);
    };
  }();
  (0,react__WEBPACK_IMPORTED_MODULE_4__.useEffect)(function () {
    fetchData(viewId);
  }, [viewId]);
  return (0,react__WEBPACK_IMPORTED_MODULE_4__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.BaseControl, {
    className: "sort-field-selector",
    label: labels.sort
  }, (0,react__WEBPACK_IMPORTED_MODULE_4__.createElement)(react_select__WEBPACK_IMPORTED_MODULE_8__["default"], {
    "aria-label": labels.sort,
    placeholder: labels.selectSortField,
    menuPortalTarget: document.body,
    styles: {
      menuPortal: function menuPortal(base) {
        return _objectSpread(_objectSpread({}, base), {}, {
          zIndex: 10
        });
      }
    },
    options: options,
    value: selectedSortField,
    onChange: function onChange(e) {
      _onChange(e.value);
      setSelectedSortField(e);
    },
    noOptionsMessage: function noOptionsMessage() {
      return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("No Sorting Fields found", "gk-gravityview");
    }
  }));
}
_s(SortFieldSelector, "J6FdteHpfvM3kFIUmneYd5jYqjM=");
_c = SortFieldSelector;
var _c;
__webpack_require__.$Refresh$.register(_c, "SortFieldSelector");

var $ReactRefreshModuleId$ = __webpack_require__.$Refresh$.moduleId;
var $ReactRefreshCurrentExports$ = __react_refresh_utils__.getModuleExports(
	$ReactRefreshModuleId$
);

function $ReactRefreshModuleRuntime$(exports) {
	if (true) {
		var errorOverlay;
		if (typeof __react_refresh_error_overlay__ !== 'undefined') {
			errorOverlay = __react_refresh_error_overlay__;
		}
		var testMode;
		if (typeof __react_refresh_test__ !== 'undefined') {
			testMode = __react_refresh_test__;
		}
		return __react_refresh_utils__.executeRuntime(
			exports,
			$ReactRefreshModuleId$,
			module.hot,
			errorOverlay,
			testMode
		);
	}
}

if (typeof Promise !== 'undefined' && $ReactRefreshCurrentExports$ instanceof Promise) {
	$ReactRefreshCurrentExports$.then($ReactRefreshModuleRuntime$);
} else {
	$ReactRefreshModuleRuntime$($ReactRefreshCurrentExports$);
}

/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ /* webpack/runtime/getFullHash */
/******/ !function() {
/******/ 	__webpack_require__.h = function() { return "68caead70fcdfc7e8800"; }
/******/ }();
/******/ 
/******/ }
);
//# sourceMappingURL=view.e62a5a4bb6f908065d29.hot-update.js.map
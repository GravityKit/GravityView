"use strict";
self["webpackHotUpdategk_gravityview_blocks"]("view",{

/***/ "./blocks/view/edit.js":
/*!*****************************!*\
  !*** ./blocks/view/edit.js ***!
  \*****************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Edit; }
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! moment */ "moment");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var react_datepicker__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! react-datepicker */ "./node_modules/react-datepicker/dist/react-datepicker.min.js");
/* harmony import */ var react_datepicker__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(react_datepicker__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var shared_js_view_selector__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! shared/js/view-selector */ "./shared/js/view-selector.js");
/* harmony import */ var shared_js_sort_selector__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! shared/js/sort-selector */ "./shared/js/sort-selector.js");
/* harmony import */ var shared_js_post_selector__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! shared/js/post-selector */ "./shared/js/post-selector.js");
/* harmony import */ var shared_js_preview_control__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! shared/js/preview-control */ "./shared/js/preview-control.js");
/* harmony import */ var shared_js_preview_as_shortcode_control__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! shared/js/preview-as-shortcode-control */ "./shared/js/preview-as-shortcode-control.js");
/* harmony import */ var shared_js_server_side_render__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! shared/js/server-side-render */ "./shared/js/server-side-render.js");
/* harmony import */ var shared_js_no_views_notice__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! shared/js/no-views-notice */ "./shared/js/no-views-notice.js");
/* harmony import */ var shared_js_disabled__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! shared/js/disabled */ "./shared/js/disabled.js");
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./editor.scss */ "./blocks/view/editor.scss");
/* provided dependency */ var __react_refresh_utils__ = __webpack_require__(/*! ./node_modules/@pmmmwh/react-refresh-webpack-plugin/lib/runtime/RefreshUtils.js */ "./node_modules/@pmmmwh/react-refresh-webpack-plugin/lib/runtime/RefreshUtils.js");
/* provided dependency */ var __react_refresh_error_overlay__ = __webpack_require__(/*! ./node_modules/@pmmmwh/react-refresh-webpack-plugin/overlay/index.js */ "./node_modules/@pmmmwh/react-refresh-webpack-plugin/overlay/index.js");
__webpack_require__.$Refresh$.runtime = __webpack_require__(/*! ./node_modules/react-refresh/runtime.js */ "react-refresh/runtime");



var _s = __webpack_require__.$Refresh$.signature();

function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }















/*global gkGravityViewBlocks*/
function Edit(_ref) {
  _s();
  var _gkGravityViewBlocks$, _gkGravityViewBlocks$2, _gkGravityViewBlocks;
  var attributes = _ref.attributes,
    setAttributes = _ref.setAttributes,
    blockName = _ref.name;
  var viewId = attributes.viewId,
    postId = attributes.postId,
    startDate = attributes.startDate,
    startDateType = attributes.startDateType,
    endDate = attributes.endDate,
    endDateType = attributes.endDateType,
    pageSize = attributes.pageSize,
    sortField = attributes.sortField,
    sortDirection = attributes.sortDirection,
    searchField = attributes.searchField,
    searchValue = attributes.searchValue,
    searchOperator = attributes.searchOperator,
    classValue = attributes.classValue,
    offset = attributes.offset,
    singleTitle = attributes.singleTitle,
    backLinkLabel = attributes.backLinkLabel,
    previewBlock = attributes.previewBlock,
    previewAsShortcode = attributes.previewAsShortcode,
    showPreviewImage = attributes.showPreviewImage;
  var previewImage = ((_gkGravityViewBlocks$ = gkGravityViewBlocks[blockName]) === null || _gkGravityViewBlocks$ === void 0 ? void 0 : _gkGravityViewBlocks$.previewImage) && (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("img", {
    className: "preview-image",
    src: (_gkGravityViewBlocks$2 = gkGravityViewBlocks[blockName]) === null || _gkGravityViewBlocks$2 === void 0 ? void 0 : _gkGravityViewBlocks$2.previewImage,
    alt: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Block preview image.', 'gk-gravityview')
  });
  if (previewImage && showPreviewImage) {
    return previewImage;
  }
  if (!((_gkGravityViewBlocks = gkGravityViewBlocks) !== null && _gkGravityViewBlocks !== void 0 && (_gkGravityViewBlocks = _gkGravityViewBlocks.views) !== null && _gkGravityViewBlocks !== void 0 && _gkGravityViewBlocks.length)) {
    var _gkGravityViewBlocks2;
    return (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_no_views_notice__WEBPACK_IMPORTED_MODULE_13__["default"], {
      blockPreviewImage: previewImage,
      newViewUrl: (_gkGravityViewBlocks2 = gkGravityViewBlocks) === null || _gkGravityViewBlocks2 === void 0 ? void 0 : _gkGravityViewBlocks2.create_new_view_url
    });
  }
  var shouldPreview = previewBlock && viewId;
  var isStartDateValid = (startDate || '').indexOf('-') > 0 && moment__WEBPACK_IMPORTED_MODULE_6___default()(startDate).isValid();
  var isEndDateValid = (endDate || '').indexOf('-') > 0 && moment__WEBPACK_IMPORTED_MODULE_6___default()(endDate).isValid();
  var displayPreviewContent = function displayPreviewContent(content) {
    var contentEl = document.createElement('div');
    contentEl.innerHTML = content;
    (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(contentEl.getElementsByTagName('script')).forEach(function (el) {
      return el.remove();
    });
    if (/gv-map-container/.test(content)) {
      (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(contentEl.querySelectorAll('.gv-map-canvas')).forEach(function (el) {
        el.innerHTML = "\n\t\t\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 232597 333333\" shape-rendering=\"geometricPrecision\" text-rendering=\"geometricPrecision\" image-rendering=\"optimizeQuality\" fill-rule=\"evenodd\" clip-rule=\"evenodd\"><path d=\"M151444 5419C140355 1916 128560 0 116311 0 80573 0 48591 16155 27269 41534l54942 46222 69232-82338z\" fill=\"#1a73e8\"/><path d=\"M27244 41534C10257 61747 0 87832 0 116286c0 21876 4360 39594 11517 55472l70669-84002-54942-46222z\" fill=\"#ea4335\"/><path d=\"M116311 71828c24573 0 44483 19910 44483 44483 0 10938-3957 20969-10509 28706 0 0 35133-41786 69232-82313-14089-27093-38510-47936-68048-57286L82186 87756c8166-9753 20415-15928 34125-15928z\" fill=\"#4285f4\"/><path d=\"M116311 160769c-24573 0-44483-19910-44483-44483 0-10863 3906-20818 10358-28555l-70669 84027c12072 26791 32159 48289 52851 75381l85891-102122c-8141 9628-20339 15752-33948 15752z\" fill=\"#fbbc04\"/><path d=\"M148571 275014c38787-60663 84026-88210 84026-158728 0-19331-4738-37552-13080-53581L64393 247140c6578 8620 13206 17793 19683 27900 23590 36444 17037 58294 32260 58294 15172 0 8644-21876 32235-58320z\" fill=\"#34a853\"/></svg>\n\t\t\t\t\t<p>\n\t\t\t\t\t\t".concat((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Map is not available in the Block preview', 'gk-gravityview'), "\n\t\t\t\t\t</p>");
      });
    }
    if (/gv-datatables/.test(content)) {
      (0,_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(contentEl.querySelectorAll('table.gv-datatables')).forEach(function (el) {
        var tbody = document.createElement('tbody');
        tbody.innerHTML = "\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td colspan=\"".concat(el.querySelectorAll('th').length, "\">\n\t\t\t\t\t\t\t").concat((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Entries from the DataTables layout are not available in the Block preview', 'gk-gravityview'), "\n\t\t\t\t\t\t</td>\n\t\t\t\t\t</tr>");
        el.querySelector('thead').appendChild(tbody);
      });
    }
    return (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
      dangerouslySetInnerHTML: {
        __html: contentEl.innerHTML
      }
    });
  };

  /**
   * Sets the selected View from the ViewSelect object.
   *
   * @since 2.21.2
   *
   * @param {number} _viewId The View ID.
   */
  function selectView(_viewId) {
    var selectedView = gkGravityViewBlocks.views.find(function (option) {
      return option.value === _viewId;
    });
    setAttributes({
      viewId: _viewId,
      secret: selectedView === null || selectedView === void 0 ? void 0 : selectedView.secret,
      previewBlock: previewBlock && !_viewId ? false : previewBlock
    });
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", _objectSpread({}, (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.useBlockProps)()), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    className: "gk-gravityview-blocks"
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Panel, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Main Settings', 'gk-gravityview'),
    initialOpen: true
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_view_selector__WEBPACK_IMPORTED_MODULE_7__["default"], {
    viewId: viewId,
    isSidebar: true,
    onChange: selectView
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_preview_control__WEBPACK_IMPORTED_MODULE_10__["default"], {
    disabled: !viewId,
    preview: previewBlock,
    onChange: function onChange(previewBlock) {
      return setAttributes({
        previewBlock: previewBlock
      });
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Entries Settings', 'gk-gravityview'),
    initialOpen: false
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_disabled__WEBPACK_IMPORTED_MODULE_14__["default"], {
    isDisabled: !viewId
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.BaseControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Start Date', 'gk-gravityview')
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.ButtonGroup, {
    className: "btn-group-double"
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Button, {
    isSecondary: startDateType !== 'date',
    isPrimary: startDateType === 'date',
    onClick: function onClick() {
      return setAttributes({
        startDateType: 'date'
      });
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Calendar Date', 'gk-gravityview')), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Button, {
    isSecondary: startDateType !== 'relative',
    isPrimary: startDateType === 'relative',
    onClick: function onClick() {
      return setAttributes({
        startDateType: 'relative'
      });
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Relative Date', 'gk-gravityview'))), startDateType === 'date' && (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(react__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.BaseControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Date', 'gk-gravityview')
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)((react_datepicker__WEBPACK_IMPORTED_MODULE_16___default()), {
    dateFormat: "yyyy-MM-dd",
    selected: isStartDateValid ? moment__WEBPACK_IMPORTED_MODULE_6___default()(startDate).toDate() : '',
    onChange: function onChange(startDate) {
      return setAttributes({
        startDate: moment__WEBPACK_IMPORTED_MODULE_6___default()(startDate).format('YYYY-MM-DD')
      });
    }
  }))), startDateType === 'relative' && (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(react__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Relative Date', 'gk-gravityview'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('now, today, +1 day', 'Examples of relative dates.', 'gk-gravityview'),
    value: startDate,
    onChange: function onChange(startDate) {
      return setAttributes({
        startDate: startDate
      });
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.BaseControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('End Date', 'gk-gravityview')
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.ButtonGroup, {
    className: "btn-group-double"
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Button, {
    isSecondary: endDateType !== 'date',
    isPrimary: endDateType === 'date',
    onClick: function onClick() {
      return setAttributes({
        endDateType: 'date'
      });
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Calendar Date', 'gk-gravityview')), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Button, {
    isSecondary: endDateType !== 'relative',
    isPrimary: endDateType === 'relative',
    onClick: function onClick() {
      return setAttributes({
        endDateType: 'relative'
      });
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Relative Date', 'gk-gravityview'))), endDateType === 'date' && (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(react__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.BaseControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Date', 'gk-gravityview')
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)((react_datepicker__WEBPACK_IMPORTED_MODULE_16___default()), {
    dateFormat: "yyyy-MM-dd",
    selected: isEndDateValid ? moment__WEBPACK_IMPORTED_MODULE_6___default()(endDate).toDate() : '',
    onChange: function onChange(endDate) {
      return setAttributes({
        endDate: moment__WEBPACK_IMPORTED_MODULE_6___default()(endDate).format('YYYY-MM-DD')
      });
    }
  }))), endDateType === 'relative' && (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(react__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Relative Date', 'gk-gravityview'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('now, today, +1 day', 'Examples of relative dates.', 'gk-gravityview'),
    value: endDate,
    onChange: function onChange(endDate) {
      return setAttributes({
        endDate: endDate
      });
    }
  }))))), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Extra Settings', 'gk-gravityview'),
    initialOpen: false
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_disabled__WEBPACK_IMPORTED_MODULE_14__["default"], {
    isDisabled: !viewId
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Page Size', 'gk-gravityview'),
    value: pageSize,
    type: "number",
    min: "0",
    onChange: function onChange(pageSize) {
      return setAttributes({
        pageSize: pageSize
      });
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_sort_selector__WEBPACK_IMPORTED_MODULE_8__["default"], {
    viewId: viewId,
    isSidebar: true,
    onChange: function onChange(sortField) {
      return setAttributes({
        sortField: sortField
      });
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.SelectControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Sort Direction', 'gk-gravityview'),
    value: sortDirection,
    options: [{
      value: 'ASC',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Ascending', 'gk-gravityview')
    }, {
      value: 'DESC',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Descending', 'gk-gravityview')
    }],
    onChange: function onChange(sortDirection) {
      return setAttributes({
        sortDirection: sortDirection
      });
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Search Field', 'gk-gravityview'),
    value: searchField,
    onChange: function onChange(searchField) {
      return setAttributes({
        searchField: searchField
      });
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_disabled__WEBPACK_IMPORTED_MODULE_14__["default"], {
    isDisabled: !viewId || !searchField
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    style: {
      marginBottom: '24px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Search Value', 'gk-gravityview'),
    value: searchValue,
    onChange: function onChange(searchValue) {
      return setAttributes({
        searchValue: searchValue
      });
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_disabled__WEBPACK_IMPORTED_MODULE_14__["default"], {
    isDisabled: !viewId || !searchField || !searchValue
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    style: {
      marginBottom: '24px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.SelectControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Search Operator', 'gk-gravityview'),
    value: searchOperator,
    options: [{
      value: 'is',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Is', 'Denotes search operator "is".', 'gk-gravityview')
    }, {
      value: 'isnot',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Is Not', 'Denotes search operator "isnot".', 'gk-gravityview')
    }, {
      value: '<>',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Not Equal', 'Denotes search operator "<>".', 'gk-gravityview')
    }, {
      value: 'not in',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Not In', 'Denotes search operator "not in".', 'gk-gravityview')
    }, {
      value: 'in',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('In', 'Denotes search operator "in".', 'gk-gravityview')
    }, {
      value: '>',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Greater', 'Denotes search operator ">".', 'gk-gravityview')
    }, {
      value: '<',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Lesser', 'Denotes search operator "<".', 'gk-gravityview')
    }, {
      value: 'contains',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Contains', 'Denotes search operator "contains".', 'gk-gravityview')
    }, {
      value: 'starts_with',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Starts With', 'Denotes search operator "starts_with".', 'gk-gravityview')
    }, {
      value: 'ends_with',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Ends With', 'Denotes search operator "ends_with".', 'gk-gravityview')
    }, {
      value: 'like',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Like', 'Denotes search operator "like".', 'gk-gravityview')
    }, {
      value: '>=',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Greater Or Equal', 'Denotes search operator ">=".', 'gk-gravityview')
    }, {
      value: '<=',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Lesser Or Equal', 'Denotes search operator "<=".', 'gk-gravityview')
    }],
    onChange: function onChange(searchOperator) {
      return setAttributes({
        searchOperator: searchOperator
      });
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_disabled__WEBPACK_IMPORTED_MODULE_14__["default"], {
    isDisabled: !viewId
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__._x)('Class', 'Denotes CSS class', 'gk-gravityview'),
    value: classValue,
    onChange: function onChange(classValue) {
      return setAttributes({
        classValue: classValue
      });
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Offset', 'gk-gravityview'),
    value: offset,
    type: "number",
    min: "0",
    onChange: function onChange(val) {
      return setAttributes({
        offset: val
      });
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Single Title', 'gk-gravityview'),
    value: singleTitle,
    onChange: function onChange(singleTitle) {
      return setAttributes({
        singleTitle: singleTitle
      });
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Back Link Label', 'gk-gravityview'),
    value: backLinkLabel,
    onChange: function onChange(backLinkLabel) {
      return setAttributes({
        backLinkLabel: backLinkLabel
      });
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_post_selector__WEBPACK_IMPORTED_MODULE_9__["default"], {
    postId: postId,
    onChange: function onChange(postId) {
      return setAttributes({
        postId: postId
      });
    }
  })))))), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_preview_as_shortcode_control__WEBPACK_IMPORTED_MODULE_11__["default"], {
    previewAsShortcode: previewAsShortcode,
    disabled: !previewBlock,
    onChange: function onChange(previewAsShortcode) {
      return setAttributes({
        previewAsShortcode: previewAsShortcode
      });
    }
  }), !shouldPreview && (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(react__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    className: "block-editor"
  }, previewImage, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_view_selector__WEBPACK_IMPORTED_MODULE_7__["default"], {
    viewId: viewId,
    onChange: selectView
  }), (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_preview_control__WEBPACK_IMPORTED_MODULE_10__["default"], {
    disabled: !viewId,
    preview: previewBlock,
    onChange: function onChange(previewBlock) {
      return setAttributes({
        previewBlock: previewBlock
      });
    }
  }))), shouldPreview && (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(react__WEBPACK_IMPORTED_MODULE_2__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)("div", {
    className: "block-preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_disabled__WEBPACK_IMPORTED_MODULE_14__["default"], {
    isDisabled: true,
    toggleOpacity: false
  }, (0,react__WEBPACK_IMPORTED_MODULE_2__.createElement)(shared_js_server_side_render__WEBPACK_IMPORTED_MODULE_12__["default"], {
    block: blockName,
    attributes: attributes,
    dataType: "json",
    loadStyles: true,
    blockPreviewImage: previewImage,
    onResponse: displayPreviewContent
  })))));
}
_s(Edit, "+/BArhCg1S/0sGrstlq5AtwfUrk=", false, function () {
  return [_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__.useBlockProps];
});
_c = Edit;
var _c;
__webpack_require__.$Refresh$.register(_c, "Edit");

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
/******/ 	__webpack_require__.h = function() { return "dae5851bfe6852e5fb97"; }
/******/ }();
/******/ 
/******/ }
);
//# sourceMappingURL=view.db2cda4fc11967fef578.hot-update.js.map
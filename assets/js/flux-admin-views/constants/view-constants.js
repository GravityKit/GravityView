var keyMirror = require('keymirror');

module.exports = keyMirror({
    // Panel actions
    PANEL_OPEN: null,
    PANEL_CLOSE: null,

    // Panel IDs
    PANEL_SETTINGS: null,
    PANEL_ROW_ADD: null,
    PANEL_ROW_SETTINGS: null,


    // Settings
    UPDATE_SETTING: null,
    UPDATE_SETTINGS_ALL: null,
    UPDATE_SETTINGS_SECTIONS: null,
    UPDATE_SETTINGS_INPUTS: null,


    // Layout
    UPDATE_LAYOUT_ALL: null,
    CHANGE_TAB: null,
    LAYOUT_ADD_ROW: null,
    LAYOUT_DEL_ROW: null,
    LAYOUT_SET_ROW: null,


});
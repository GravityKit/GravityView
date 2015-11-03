var ViewDispatcher = require('../dispatcher/view-dispatcher.js');
var ViewConstants = require('../constants/view-constants.js');
var EventEmitter = require('events').EventEmitter;
var assign = require('object-assign');


/**
 * Store the builder layout configuration ( rows, columns, fields...)
 */
var LayoutStore = {

    // Actual collection of model data
    layout: [],

    // Accessor method we'll use later
    getAll: function() {
        return this.layout;
    }

};

ViewDispatcher.register( function( payload ) {

    switch( payload.eventName ) {

        case 'add_row':

            break;

        /*case 'new-item':

            // We get to mutate data!
            ListStore.items.push( payload.newItem );
            break;*/

    }

    return true; // Needed for Flux promise resolution

});
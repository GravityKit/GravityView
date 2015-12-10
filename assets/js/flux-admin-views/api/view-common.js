/**
 * List common helper functions
 * @type {{}}
 */


var ViewCommon = {

    /**
     * Find the array index where the id == row_id property
     * @param rows
     * @param id
     * @returns {number}
     */
    findRowIndex: function( rows, id ) {
        for ( var i = 0; i < rows.length; i++ ) {
            if ( rows[i]['row_id'] === id ) {
                return i;
            }
        }
        return 0;
    },

    /**
     * Create a unique ID similar to PHP uniqid
     * Thanks to https://gist.github.com/larchanka/7080820
     * @param pr
     * @param en
     * @returns {*}
     */
    uniqid: function ( pr, en ) {
        var pr = pr || '', en = en || false, result;

        this.seed = function (s, w) {
            s = parseInt(s, 10).toString(16);
            return w < s.length ? s.slice(s.length - w) : (w > s.length) ? new Array(1 + (w - s.length)).join('0') + s : s;
        };

        result = pr + this.seed(parseInt(new Date().getTime() / 1000, 10), 8) + this.seed(Math.floor(Math.random() * 0x75bcd15) + 1, 5);

        if (en) result += (Math.random() * 10).toFixed(8).toString();

        return result;
    },



};


module.exports = ViewCommon;
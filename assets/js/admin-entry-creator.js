/**
 * Entry Creator UI logic
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2020, Katz Web Services, Inc.
 *
 * @since 2.9.3
 *
 * globals jQuery, GVEntryCreator
 */

( function( $ ) {
	'use strict';

	$( document ).on( 'ready', function() {
		// Custom AJAX adapter that returns predefined results when select element is first initialized and when search input field is cleared
		// Adapted from https://github.com/select2/select2/issues/3828
		$.fn.selectWoo.amd.define( 'select2/data/extended-ajax', [ './ajax', './tags', '../utils', 'module', 'jquery' ], function( AjaxAdapter, Tags, Utils, module, $ ) {
			function ExtendedAjaxAdapter( $element, options ) {
				this.minimumInputLength = options.get( 'minimumInputLength' );
				this.defaultResults = options.get( 'defaultResults' );
				ExtendedAjaxAdapter.__super__.constructor.call( this, $element, options );
			}

			Utils.Extend( ExtendedAjaxAdapter, AjaxAdapter );

			// Override original query function to support default results
			var originalQuery = AjaxAdapter.prototype.query;

			ExtendedAjaxAdapter.prototype.query = function( params, callback ) {
				var defaultResults = ( typeof this.defaultResults == 'function' ) ? this.defaultResults.call( this ) : this.defaultResults;
				if ( defaultResults && defaultResults.length && ( ! params.term || params.term.length < this.minimumInputLength ) ) {
					var data = { results: defaultResults };
					var processedResults = this.processResults( data, params );
					callback( processedResults );
				} else if ( params.term && params.term.length >= this.minimumInputLength ) {
					originalQuery.call( this, params, callback );
				} else {
					this.trigger( 'results:message', {
						message: 'inputTooShort',
						args: {
							minimum: this.minimumInputLength,
							input: '',
							params: params,
						},
					} );
				}
			};

			return ExtendedAjaxAdapter;
		} );

		var gv_nonce = $( '#gv_entry_creator_nonce' ).val();
		var $select = $( '#change_created_by' );

		// Get options with "value" attributes that are not selected by default
		var $defaultResults = $( 'option[value]:not([selected])', $select );
		var defaultResults = [];
		$defaultResults.each( function() {
			var $option = $( this );

			defaultResults.push( {
				id: $option.attr( 'value' ),
				text: $option.text(),
				disabled: $option.attr( 'disabled' ),
			} );
		} );

		$select.selectWoo( {
			dropdownCssClass: 'gv-entry-creator-dropdown',
			minimumInputLength: 3,
			ajax: {
				type: 'POST',
				url: GVEntryCreator.ajaxurl,
				dataType: 'json',
				delay: 250,
				data: function( params ) {
					return {
						q: params.term,
						page: params.page,
						action: GVEntryCreator.action,
						gv_nonce: gv_nonce,
					};
				},
				processResults: function( data ) {
					var results = [];

					if ( ! data ) {
						return results;
					}

					if ( data.results ) {
						return data;
					}

					$.each( data, function( index, user ) {
						results.push( {
							id: user.ID,
							text: user.display_name + ' (' + user.user_nicename + ')',
						} );
					} );

					return {
						results: results,
					};
				},
				cache: true,
			},
			dataAdapter: $.fn.selectWoo.amd.require( 'select2/data/extended-ajax' ),
			defaultResults: defaultResults,
		} );

		$( '#select2-change_created_by-container' )
			.parents( '.select2-container--default' )
			.addClass( 'gv-entry-creator-container' )
			.addClass( GVEntryCreator.gf25 ? 'gf25' : '' );

		$select.on( 'select2:open', function() {
			$( '.gv-entry-creator-dropdown' ).addClass( GVEntryCreator.gf25 ? 'gf25' : '' );
			$( '.gv-entry-creator-dropdown input.select2-search__field' )
				.prop( 'placeholder', GVEntryCreator.language.search_placeholder )
				.attr( 'aria-label', GVEntryCreator.language.search_placeholder )
		} );
	} );
}( jQuery ) );

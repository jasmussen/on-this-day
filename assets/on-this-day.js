/**
 * On This Day prototype behaviour.
 *
 * Two jobs: move the corner control up into the widget header, and hide a
 * post in place with an undo that does not need a page reload.
 */
( function () {
	'use strict';

	var settings = window.otdSettings || {};

	/**
	 * Sends one of the plugin's Ajax actions.
	 *
	 * @param {string} action  Ajax action name.
	 * @param {number} postId  Post ID.
	 * @return {Promise<void>} Resolves when the request succeeded.
	 */
	function request( action, postId ) {
		var body = new URLSearchParams();

		body.append( 'action', action );
		body.append( 'nonce', settings.nonce );
		body.append( 'post_id', postId );

		return window
			.fetch( settings.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
			} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( ! result || ! result.success ) {
					throw new Error( 'Request failed' );
				}
			} );
	}

	/**
	 * Replaces a row with a confirmation and an undo.
	 *
	 * @param {HTMLElement} row    The list item.
	 * @param {string}      postId Post ID.
	 */
	function showUndo( row, postId ) {
		var original = row.innerHTML;

		row.classList.add( 'is-hidden' );
		row.textContent = settings.i18n.hidden + ' ';

		var undo = document.createElement( 'button' );
		undo.type = 'button';
		undo.className = 'otd-undo';
		undo.textContent = settings.i18n.undo;

		undo.addEventListener( 'click', function () {
			request( 'otd_restore_post', postId )
				.then( function () {
					row.classList.remove( 'is-hidden' );
					row.innerHTML = original;
					bindRow( row );
				} )
				.catch( function () {
					window.alert( settings.i18n.failed );
				} );
		} );

		row.appendChild( undo );
	}

	/**
	 * Binds the hide button inside one row.
	 *
	 * @param {HTMLElement} row The list item.
	 */
	function bindRow( row ) {
		var button = row.querySelector( '.otd-hide' );

		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function () {
			var postId = button.getAttribute( 'data-post-id' );

			button.disabled = true;

			request( 'otd_hide_post', postId )
				.then( function () {
					showUndo( row, postId );
				} )
				.catch( function () {
					button.disabled = false;
					window.alert( settings.i18n.failed );
				} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var widget = document.querySelector( '.otd' );

		if ( ! widget ) {
			return;
		}

		Array.prototype.forEach.call(
			widget.querySelectorAll( '.otd-post' ),
			bindRow
		);
	} );
} )();

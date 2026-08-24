/* Job before/after image pickers via the WordPress media modal. */
( function ( $ ) {
	'use strict';
	$( document ).on( 'click', '.harbour-pick-choose', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.harbour-pick' );
		var frame = wp.media( {
			title: 'Choose image',
			multiple: false,
			library: { type: 'image' },
			button: { text: 'Use this image' }
		} );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var url = ( att.sizes && att.sizes.medium ) ? att.sizes.medium.url : att.url;
			$wrap.find( '.harbour-pick-input' ).val( att.id );
			$wrap.find( '.harbour-pick-preview' ).html( '<img src="' + url + '" style="max-width:100%;height:auto;display:block">' );
		} );
		frame.open();
	} );
	$( document ).on( 'click', '.harbour-pick-clear', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.harbour-pick' );
		$wrap.find( '.harbour-pick-input' ).val( '' );
		$wrap.find( '.harbour-pick-preview' ).html( '<span style="color:#787c82">No image</span>' );
	} );
} )( jQuery );

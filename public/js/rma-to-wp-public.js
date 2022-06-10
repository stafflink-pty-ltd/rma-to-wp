(function( $ ) {
	'use strict';

var splider = new Splide( '.splide', {
	type   : 'loop',
	perPage: 3,
	perMove: 3,
	rewind: true,
	pagination: false,
	gap: 20,
	breakpoints: {
		768: {
			perPage: 1,
		},
		991: {
			perPage: 2,
		}
	}
} ).mount();

})( jQuery );

/**
 *------------------------------------------------------------------------------
 * @package       Seagull By iThemesLab!
 *------------------------------------------------------------------------------
 * @copyright     Copyright (C) 2013-2016 iThemesLab.com. All Rights Reserved.
 * @Link:         http://ithemeslab.com
 */

( function ( $ ) {
	"use strict";

	//Preloader Start
	$( window )
		.on( "load", function () {
			var mainDiv = $( '.t3-wrapper' ),
				preloader = $( '.preloader' );

			preloader.delay( 500 )
				.fadeOut( 400 );

			setTimeout( 1000 );


			$( '.sppb-btn' )
				.not( '.sppb-btn-link' )
				.addClass( 'btn' );
			$( '#header-wrap' )
				.addClass( 'navbar-fixed-top bg2' );
		} );

	//Preloader End
	//Fixed Navigation on Scroll Start
	$( window )
		.on( 'scroll', function () {
			if ( $( window )
				.scrollTop() > 55 ) {
				$( '#t3-mainnav' )
					.addClass( 'navbar-fixed-top bg1' );
				$( '#header-wrap' )
					.removeClass( 'navbar-fixed-top' );
				$( '#back-to-top' )
					.addClass( 'reveal' );
			} else {
				$( '#header-wrap' )
					.addClass( 'navbar-fixed-top bg2' );
				$( '#t3-mainnav' )
					.removeClass( 'navbar-fixed-top bg1' );
				$( '#back-to-top' )
					.removeClass( 'reveal' );
			}
		} );
	//Fixed Navigation on Scroll Ends

	//Tooltip
	$( '[data-toggle="tooltip"]' )
		.tooltip();

	//Popover
	$( '[data-toggle="popover"]' )
		.popover();

	$( document )
		.ready( function () {

			//Clients Carousel Start
			$( ".itl-clients" )
				.owlCarousel( {
					items: 6, //items
					responsive: true,
					pagination: false, //navigation & pagination
					slideSpeed: 100, //Basic Speeds
					paginationSpeed: 600,
					autoPlay: true
				} );
			//Clients Carousel End
			//back to top button start
			$( '#back-to-top' )
				.on( 'click', function () {
					$( "html, body" )
						.animate( {
							scrollTop: 0
						}, 1000 );
					return false;
				} );
			//back to top button end

		} );

} )( jQuery );

jQuery( document )
	.ready( function ( $ ) {
		'use strict';
		$( '.sppb-addon-accordion .sppb-panel-heading' )
			.removeClass( "active" );
		$( '.sppb-addon-accordion .sppb-panel-collapse' )
			.hide();

	} );

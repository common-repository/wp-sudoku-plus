/*
wp-sudoku-plus.js
*/

jQuery(document).ready(function(){jQuery('#wpsud-message').css({display:'none'})});

var sudAjaxUrl;
var sudMatrix = [];
for (y=0;y<9;y++) {
	sudMatrix[y] = Array();
	for (x=0;x<9;x++) {
		sudMatrix[y][x] = 0;
	}
}
var sudDone = false;

function sudScanSingle() {
	var y,x,n,v,c;

	if ( sudDone ) return;

	for ( y = 0; y < 9; y++ ) {
		for ( x = 0; x < 9; x++ ) {
			v = 0;
			c = 0;
			for ( n = 1; n <= 9; n++ ) {
				if ( jQuery('#sud-button-'+y+'-'+x+'-'+n).css( 'visibility') == 'visible' && jQuery('#sud-button-'+y+'-'+x+'-'+n).css( 'display') == 'block' ) {
					v = n;
					c++;
				}
			}
			if ( c == 0 && sudMatrix[y][x] == 0 ) {
				jQuery( '#sud-' + ( y * 9 + x ) ).html( '<span style="color:red;" >X</span>' );
				sudFail();
				sudDone = true;
				return;
			}
			if ( c == 1 ) {
				setTimeout( function() { sudButtonClick( false, y, x, v ) }, 200 );
				return;
			}
		}
	}

	// Test for ready
	for ( y = 0; y < 9; y++ ) {
		for ( x = 0; x < 9; x++ ) {
			if ( sudMatrix[y][x] == 0 ) return;
		}
	}

	sudWin();
	sudDone = true;
}

function sudButtonClick( event, y, x, v ) {

	if ( event ) event.preventDefault();

	if ( sudDone ) return;

	// Left button
	if ( ! event || event.button == 0 ) {
		var b = parseInt( y / 3 ) * 3 + parseInt( x / 3 );
		var i = y * 9 + x;
		jQuery( '.sud-button-box-y-'+y+'-v-'+v ).css( 'visibility', 'hidden' );
		jQuery( '.sud-button-box-x-'+x+'-v-'+v ).css( 'visibility', 'hidden' );
		jQuery( '.sud-button-box-b-'+b+'-v-'+v ).css( 'visibility', 'hidden' );
		for ( j=1;j<10;j++ ) {
			jQuery( '.sud-button-box-y-'+y+'-x-'+x+'-v-'+j ).css( 'visibility', 'hidden' );
		}
		jQuery( '#sud-'+i ).html(v);
		sudMatrix[y][x] = v;
	}

	// Right button
	if ( event.button == 2 ) {
		jQuery( '.sud-button-box-y-'+y+'-x-'+x+'-v-'+v ).css( 'visibility', 'hidden' );
	}

	// Test for trivial choices
	sudScanSingle();
	
	// Test for nav buttons
	sudVfyNav( v );
}


function sudItemClick() {

}


function sudButtonDestroy(y,x,v,hit) {

	var b = parseInt( y / 3 ) * 3 + parseInt( x / 3 );
	jQuery( '.sud-button-box-y-'+y+'-v-'+v ).css( 'display', 'none' );
	jQuery( '.sud-button-box-x-'+x+'-v-'+v ).css( 'display', 'none' );
	jQuery( '.sud-button-box-b-'+b+'-v-'+v ).css( 'display', 'none' );

	if ( hit ) sudMatrix[y][x] = v;
}


function sudKeyboardHandler( e ) {
	if ( e == null ) { // ie
		keycode = event.keyCode;
		escapeKey = 27;
	} else { // mozilla
		keycode = e.keyCode;
		escapeKey = 27; //e.DOM_VK_ESCAPE;
	}
	var key = parseInt( String.fromCharCode( keycode ).toLowerCase() );

	sudHiLite( key );
}

function sudHiLite( key ) {
	if ( key >= 0 && key < 10 ) {
		for ( i=1;i<10;i++ ) {
			jQuery( '.sud-button-box-v-'+i ).css( 'background-color', 'transparent' );
		}
		jQuery( '.sud-button-box-v-'+key ).css( 'background-color', '#77f' );
	}	
}

function sudVfyNav( key ) {
	var btns = jQuery( '.sud-button-box-v-'+key );
	var cnt = 0;
	for ( j=0;j<btns.length;j++ ) {
		if ( jQuery( btns[j] ).css( 'visibility' ) == 'visible' && jQuery( btns[j] ).css( 'display' ) != 'none' ) {
			cnt++;
		}
	}
	if ( cnt == 0 ) {
		jQuery( '.sud-navibox-' + key ).css( 'color', 'transparent' );
	}
}


// Install keyboard handler
jQuery( document ).on( 'keydown', sudKeyboardHandler );

// Ajax fail
function sudFail() {

	if ( sudDone ) return;

	jQuery.ajax( { 	url: 		sudAjaxUrl,
					data: 		'action=wp_sudoku' +
								'&wp-sudoku-action=sudfail' +
								'&nonce=' + jQuery( '#nonce' ).val() +
								'&puzno=' + jQuery( '#puzno' ).val() +
								'&prevpuzno=' + jQuery( '#puzno' ).val(),
					async: 		true,
					type: 		'GET',
					timeout: 	10000,
					beforeSend: function( xhr ) {
									var html = '<img class="sud-small-spin" src="' + sudSmallSpinnerUrl + '" />';
									jQuery( '#lost' ).html( html );
								},
					success: 	function( result, status, xhr ) {
									jQuery( '#lost' ).html(result);
									var c = jQuery( '#totlost' ).html();
									jQuery( '#totlost' ).html( parseInt(c)+1 );
								},
					error: 		function( xhr, status, error ) {

								},
					complete: 	function( xhr, status, newurl ) {
									var r = jQuery( '#rating' ).val();
									var c = jQuery( '#sud-'+r+'-lost' ).html();
									jQuery( '#sud-'+r+'-lost' ).html( parseInt(c)+1 );
								}
				} );
}

// Ajax win
function sudWin() {

	if ( sudDone ) return;

	jQuery.ajax( { 	url: 		sudAjaxUrl,
					data: 		'action=wp_sudoku' +
								'&wp-sudoku-action=sudwin' +
								'&nonce=' + jQuery( '#nonce' ).val() +
								'&puzno=' + jQuery( '#puzno' ).val() +
								'&prevpuzno=' + jQuery( '#puzno' ).val(),
					async: 		true,
					type: 		'GET',
					timeout: 	10000,
					beforeSend: function( xhr ) {
									var html = '<img class="sud-small-spin" src="' + sudSmallSpinnerUrl + '" />';
									jQuery( '#won' ).html( html );
								},
					success: 	function( result, status, xhr ) {
									jQuery( '#won' ).html( result );
									var c = jQuery( '#totwon' ).html();
									jQuery( '#totwon' ).html( parseInt(c)+1 );
								},
					error: 		function( xhr, status, error ) {

								},
					complete: 	function( xhr, status, newurl ) {
									var r = jQuery( '#rating' ).val();
									var c = jQuery( '#sud-'+r+'-won' ).html();
									jQuery( '#sud-'+r+'-won' ).html( parseInt(c)+1 );
								}
				} );
}

// Ajax get puzzle
function sudGetPuzzle( puzno, rating, url, size ) {

	jQuery.ajax( { 	url: 		sudAjaxUrl,
					data: 		'action=wp_sudoku' +
								'&wp-sudoku-action=sudget' +
								'&nonce=' + jQuery( '#nonce' ).val() +
								( puzno !== false ? '&puzno=' + puzno : '' ) +
								( rating ? '&rating=' + rating : '' ) +
								'&prevpuzno=' + jQuery( '#puzno' ).val() +
								'&size=' + size,
					async: 		true,
					type: 		'GET',
					timeout: 	10000,
					beforeSend: function( xhr ) {
									jQuery( '#sud-ajaxspin' ).css( 'visibility', 'visible' );
								},
					success: 	function( result, status, xhr ) {
									for (y=0;y<9;y++) {
										sudMatrix[y] = Array();
										for (x=0;x<9;x++) {
											sudMatrix[y][x] = 0;
										}
									}
									jQuery( '#sud-container' ).html(result);
									jQuery( '#sud-ajaxspin' ).css( 'visibility', 'hidden' );
								},
					error: 		function( xhr, status, error ) {
									document.location = url;
								},
					complete: 	function( xhr, status, newurl ) {
									sudDone = false;
								}
				} );
}

function sudReload( url, rating ) {

	if ( url.indexOf( '?' ) == -1 ) {
		url += '?rating=' + rating.replace( '+', 'P' );
	}
	else {
		url += '&rating=' + rating.replace( '+', 'P' );
	}

	document.location = url;
}
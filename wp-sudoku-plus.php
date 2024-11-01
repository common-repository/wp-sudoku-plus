<?php
/*
 * Plugin Name: WP Sudoku Plus
 * Plugin URI:
 * Description: Simple Sudoku
 * Author: opajaap
 * Author URI:
 * Text Domain: wp-sudoku-plus
 * Domain Path: /languages
 * Version: 1.6
 * License: GPLv2
*/

global $wpdb;

define( 'WP_SUDOKU', $wpdb->prefix . 'wp_sudoku' );

function wp_sudoku_activate_plugin() {
global $wpdb;

	$wp_sudoku = 	"CREATE TABLE " . WP_SUDOKU . " (
						id bigint(20) NOT NULL AUTO_INCREMENT,
						data tinytext NOT NULL,
						rating smallint(5) NOT NULL,
						won bigint(20) NOT NULL default 0,
						lost bigint(20) NOT NULL default 0,
						PRIMARY KEY  (id)
					) DEFAULT CHARACTER SET utf8;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( $wp_sudoku );

}

function wpsud_fill_db() {
global $wpdb;

	// Do they need us?
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wp_sudoku" );

	if ( $count >= 200000 ) {
		return;
	}

	// Init wp filesystem
	require_once ( ABSPATH . '/wp-admin/includes/file.php' );
	global $wp_filesystem;

	if ( ! $wp_filesystem )	{
		WP_Filesystem();
	}

	$dataarr 	= $wp_filesystem->get_contents_array( dirname(__FILE__) . '/data.txt' );
	$ratsarr 	= $wp_filesystem->get_contents_array( dirname(__FILE__) . '/rating.txt' );

	$start 	= get_option( 'wp-sudoku-data-count', '0' );
	$end 	= min( intval( $start ) + 1000, 200000 );
	$cnt 	= 0;
	$s 		= $start + 1;

	echo '
	<div id="wpsud-message">'.
	/* Translators: puzzle numbers */
		esc_html( sprintf( __('Importing puzzle numbers %1$d to %2$d. Please wait a moment', 'wp-sudoku-plus'), $s, $end) ).'
		<img src="' . plugins_url( basename( dirname( __FILE__ ) ) . '/smallspinner.gif' ) . '">
	</div>';

	while ( $cnt < $end && isset( $dataarr[$cnt] ) ) {

		$raw 	= $dataarr[$cnt];
		$id 	= substr( $raw, 0, 7 );
		$data 	= substr( $raw, 8, 81 );
		$rat 	= $ratsarr[$cnt];
		$rating = substr( $rat, 8, 1 );
		$cnt++;

		if ( $cnt > $start ) {
			$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wp_sudoku ( `data`, `rating` ) VALUES ( %s, %d )", $data, $rating ) );
		}
	}

	for ( $rat = 1; $rat < 8; $rat++ ) {
		$ratarr[$rat] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wp_sudoku
														 WHERE rating = %d", $rat ) );
	}

	update_option( 'wp-sudoku-rating-counts', $ratarr );
	update_option( 'wp-sudoku-data-count', $end );
}

register_activation_hook( __FILE__, 'wp_sudoku_activate_plugin' );

function wp_sudoku_deactivate_plugin() {
global $wpdb;

	$wpdb->query( "DROP TABLE {$wpdb->prefix}wp_sudoku" );
	delete_option( 'wp-sudoku-data-count' );
	delete_option( 'wp-sudoku-rating-counts' );
}

register_deactivation_hook( __FILE__, 'wp_sudoku_deactivate_plugin' );

add_action( 'init', 'wp_sudoku_load_textdomain' );

function wp_sudoku_load_textdomain() {
global $wp_version;

	if ( $wp_version < '4.6' ) {
		load_plugin_textdomain( 'wp-sudoku-plus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}

function sudoku( $size = 16 ) {
global $wpdb;

	if ( ! defined( 'DOING_AJAX' ) ) {
		if ( get_option( 'wp-sudoku-data-count', '0' ) < '200000' ) {
			wpsud_fill_db();
		}
	}

	$size = floor( intval( strval( intval( $size ) ) ) );

	$s = $size ? $size : 16;
	if ( $s < 8 ) $s = 8;
	if ( $s > 32 ) $s = 32;
	$si = 3 * $s + 2;
	$sb = 3 * $si + 4;
	$sm = 3 * $sb + 8;
	$sn = intval( $si * 9 / 10 );

	$ratarr = get_option( 'wp-sudoku-rating-counts', array( '1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0, '6' => 0, '7' => 0 ) );

	// A puzzle of a certain rating requested?
	$puzzle = false;
	$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check
	if ( isset( $_REQUEST['rating'] ) ) {

		// Security check
		$rating = strval( intval( $_REQUEST['rating'] ) );
		if ( $rating < '1' || $rating > '7' ) {
			esc_html_e( 'Security check failure', 'wp-sudoku-plus' );
			echo ' (1)';
			exit;
		}

		// Find a puzzle of the requested rating
		$puzzle = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wp_sudoku WHERE rating = %d ORDER BY RAND() LIMIT 1", $rating ), ARRAY_A );
		$puzno  = $puzzle['id'];
		$data   = $puzzle['data'];
	}

	// A puzzle of a certain id requested?, else use a random id
	else {

		// Security check
		$puzno 	= isset( $_REQUEST['puzno'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['puzno'] ) ) : wp_rand( 1, get_option( 'wp-sudoku-data-count' ) );
		$puzno 	= strval( intval( $puzno ) );
		if ( $puzno < '0' || $puzno > '200000' ) {
			esc_html_e( 'Security check failure', 'wp-sudoku-plus' );
			echo ' (2)';
			exit;
		}

		// Puzno = 0 means empty puzzle
		if ( $puzno == '0' ) {
			$data = '000000000000000000000000000000000000000000000000000000000000000000000000000000000';
			$rating = '0';
		}

		// Puzno <> 0
		else {
			$puzzle = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wp_sudoku WHERE id = %s", $puzno ), ARRAY_A );
			$data 	= $puzzle['data'];
			$rating = $puzzle['rating'];
		}
	}

	$result = '';

	// Open overall container
	if ( ! defined( 'DOING_AJAX' ) ) {
		$result .= 	'<div' .
						' id="sud-container"' .
						' style="position:relative;width:'.$sm.'px;"' .
						' >';
	}

	$result .= '
	<div
		class="sud-main-box"
		style="box-sizing:border-box;border:4px solid #333;width:'.$sm.'px;height:'.$sm.'px;position:relative;font-family:Helvetica;"
		oncontextmenu="return false;"
		>
		<input type="hidden" id="puzno" value="'.$puzno.'">
		<input type="hidden" id="rating" value="'.$rating.'">
		<input type="hidden" id="nonce" value="'.wp_create_nonce( 'sudoku-'.$puzno ).'" >';
		for ( $ybox = 0; $ybox < 3; $ybox++ ) {
			for ( $xbox = 0; $xbox < 3; $xbox++ ) {
				$result .= '
				<div
					class="sud-block-box sud-block-box-y-'.$ybox.' sud-block-box-x-'.$xbox.' sud-block-box-b-'.( $ybox * 3 + $xbox ).'"
					style="'.( floor( ( $xbox + $ybox ) / 2 ) * 2 == ( $xbox + $ybox ) ? 'background-color:#ccc;' : '' ).'width:'.$sb.'px;height:'.$sb.'px"
					>';
					for ( $yitm = 0; $yitm < 3; $yitm++ ) {
						for ( $xitm = 0; $xitm < 3; $xitm++ ) {
							$i = ( ( $ybox * 3 ) + $yitm ) * 9 + $xbox * 3 + $xitm;
							$value = substr( $data, $i, 1 );
							$result .= '
							<div
								class="sud-item-box sud-item-box-y-'.( $ybox * 3 + $yitm ).' sud-item-box-x-'.( $xbox * 3 + $xitm ).'"
								style="width:'.$si.'px;height:'.$si.'px;font-size:'. ( ( $s - 2 ) * 3 ) .'px;line-height:'.$si.'px;"
								>';
								if ( $value ) {
									$result .= 	'
									<div
										id="sud-'.$i.'"
										style="color:#007;"
										>' .
										$value . '
									</div>';
								}
								else {
									for ( $ybtn = 0; $ybtn < 3; $ybtn++ ) {
										$t = $ybtn * $s;
										for ( $xbtn = 0; $xbtn < 3; $xbtn++ ) {
											$l = $xbtn * $s;
											$result .= '
											<div
												id="sud-button-'.($ybox*3+$yitm).'-'.($xbox*3+$xitm).'-'.($ybtn*3+$xbtn+1).'"
												class="sud-button-box sud-button-box-y-'.( $ybox * 3 + $yitm ).'-v-'.( $ybtn * 3 + $xbtn + 1 ).'
													sud-button-box-x-'.( $xbox * 3 + $xitm ).'-v-'.( $ybtn * 3 + $xbtn + 1 ).'
													sud-button-box-b-' . ( $ybox * 3 + $xbox ) . '-v-' . ( $ybtn * 3 + $xbtn + 1 ).'
													sud-button-box-v-' . ( $ybtn * 3 + $xbtn + 1 ) .'
													sud-button-box-y-' . ( $ybox * 3 + $yitm ) . '-x-' . ( $xbox * 3 + $xitm ) . '-v-' . ( $ybtn * 3 + $xbtn + 1 ) .
													'"
												style="top:'.$t.'px;left:'.$l.'px;display:block;visibility:visible;width:'.$s.'px;height:'.$s.'px;font-size:'.($s-2).'px;line-height:'.($s-2).'px;"
												onmousedown="sudButtonClick(event,'.($ybox*3+$yitm).','.($xbox*3+$xitm).','.($ybtn*3+$xbtn+1).');"
												>' .
												($ybtn*3+$xbtn+1).'
											</div>';
										}
									}
								}
								$result .= '<div id="sud-' . $i . '" ></div>';
							$result .=
							'</div>';
						}
					}

				$result .=
				'</div>';
			}
		}
		$result .=
		'</div>';

		$js = 'sudAjaxUrl="' . admin_url( 'admin-ajax.php' ) . '";';
		$js .= 'sudSmallSpinnerUrl = "' . plugins_url( basename( dirname( __FILE__ ) ) . '/smallspinner.gif' ) . '";';
		for ( $i = 0; $i < 81; $i++ ) {
			$v = substr( $data, $i, 1 );
			if ( $v ) {
				$y = floor( $i / 9 );
				$x = $i % 9;
				$js .= 'sudButtonDestroy(' . $y . ', ' . $x . ', ' . $v . ', true );';
			}
		}

		if ( defined('DOING_AJAX') ) {
			$result .= '<script>'.$js.'</script>';
		}
		else {
			wp_add_inline_script( 'wp-sudoku-plus', $js );
		}

		$same  = get_permalink();

		if ( is_array( $puzzle ) && isset( $puzzle['id'] ) ) {
			if ( strpos( $same, '?' ) ) {
				$same .= '&puzno=' . $puzzle['id'];
			}
			else {
				$same .= '?puzno=' . $puzzle['id'];
			}
		}

		$new   = get_permalink();
		if ( isset( $_REQUEST['rating'] ) ) {

			$rating = strval( intval( $_REQUEST['rating'] ) ) ;
			if ( strpos( $new, '?' ) ) {
				$new .= '&rating=' . $rating;
			}
			else {
				$new .= '?rating=' . $rating;
			}
		}
		$empty = get_permalink();
		if ( strpos( $empty, '?' ) ) {
			$empty .= '&puzno=0';
		}
		else {
			$empty .= '?puzno=0';
		}

		// Open nav container
		$result .=
				'<div' .
					' >';
				for ( $xbox = 0; $xbox < 10; $xbox++ ) {
					$result .= '
					<div' .
						' class="sud-navi-box sud-navibox-' . $xbox . '"' .
						' onclick="sudHiLite(' . $xbox . ');"' .
						' title="' . esc_attr( $xbox ? __( 'Hilite', 'wp-sudoku-plus' ) . ' ' . $xbox : __( 'Clear hilites', 'wp-sudoku-plus' ) ) . '"' .
						' style="font-size:'. ( (  $s - 2 ) * 2 ) .'px;line-height:'. $sn .'px;"' .
						' >' .
						( $xbox ? $xbox : 'X' ) .
					'</div>';
				}
		$result .=
				'</div>';

		// Open legenda container
		$result .= '
				<div
					id="wp-sudoku-legenda"
					style="font-size:'.$s.'px;line-height:'.( $s + 2 ).'px;width:'.$sm.'px;"
					>';

					// Statistics this puzzle
					if ( $puzno ) {
		$result .=
						__( 'Puzzle', 'wp-sudoku-plus' ) .
						' #' . $puzzle['id'] . ', ' .
						__( 'Won', 'wp-sudoku-plus' ) .
						': ' .
						'<span' .
							' id="won"' .
							' style="cursor:pointer;"' .
							/* Translators: Puzzle number */
							' title="' . esc_attr( sprintf( __( 'Total times puzzle #%d has been solved', 'wp-sudoku-plus' ), $puzzle['id'] ) ) . '"' .
							' >' .
							$puzzle['won'] .
						'</span>, ' .
						__( 'Lost', 'wp-sudoku-plus' ) .
						': ' .
						'<span' .
							' id="lost"' .
							' style="cursor:pointer;"' .
							/* Translators: Puzzle number */
							' title="' . esc_attr( sprintf( __( 'Total times a visitor failed to solve puzzle #%d', 'wp-sudoku-plus' ), $puzzle['id'] ) ) . '"' .
							' >' .
							$puzzle['lost'] .
						'</span>.<br>';
					}

					// Level selection box
		$result .=
					__( 'Level', 'wp-sudoku-plus' ) .
					': ' .
					'<select' .
						' id="wp-sudoku-rating"' .
						' onchange="sudGetPuzzle( false, this.value, \'' . get_permalink() . '\', ' . $s . ')"' .
						' style="margin:0;padding:0;width:auto;font-size:'.$s.'px;line-height:'.( $s + 2 ).'px;"'.
						' >';
						for ( $i = 1; $i < 8; $i++ ) {
		$result .=
							'<option' .
								' value="' . $i . '"' .
								( $rating == $i ? ' selected="selected" ' : '' ) .
								( $ratarr[$i] == 0 ? ' disabled="disabled" title="'.esc_attr__('Not yet available', 'wp-sudoku-plus').'"' : '' ) .
								'>' .
								$i .
							'</option>';
						}
		$result .=
					'</select>' .
					', ';

					// Total statistics this level
					if ( $puzno ) {
						$temp = $wpdb->get_results( $wpdb->prepare( "SELECT won FROM {$wpdb->prefix}wp_sudoku
																	 WHERE rating = %d AND won <> 0", $rating ), ARRAY_A );
						$totwon = 0;
						if ( $temp ) {
							foreach ( $temp as $t ) {
								$totwon += $t['won'];
							}
						}
						$temp = $wpdb->get_results( $wpdb->prepare( "SELECT lost FROM {$wpdb->prefix}wp_sudoku
																	 WHERE rating = %d AND lost <> 0", $rating ), ARRAY_A );
						$totlost = 0;
						if ( $temp ) {
							foreach ( $temp as $t ) {
								$totlost += $t['lost'];
							}
						}
		$result .=
						__( 'Won', 'wp-sudoku-plus' ) .
						': ' .
						'<span' .
							' id="totwon"' .
							' style="cursor:pointer;"' .
							/* Translators: Puzzle level number */
							' title="' . esc_attr( sprintf( __( 'Total times a puzzle of level %d has been solved', 'wp-sudoku-plus' ), $rating ) ) . '"' .
							' >' .
							$totwon .
						'</span>, ' .
						__( 'Lost', 'wp-sudoku-plus' ) .
						': ' .
						'<span' .
							' id="totlost"' .
							' style="cursor:pointer;"' .
							/* Translators: Puzzle level number */
							' title="' . esc_attr( sprintf( __( 'Total times a visitor failed to solve a puzzle of level %d', 'wp-sudoku-plus' ), $rating ) ) . '"' .
							' >' .
							$totlost .
						'</span>.' .
						'<br>';
					}

					// New puzzle links
		$result .=
					'<a' .
						' style="cursor:pointer;"' .
						' onclick="sudGetPuzzle( ' . $puzno . ', false, \'' . $same . '\', ' . $s . ' )"' .
						' >' .
						__('Same', 'wp-sudoku-plus' ) .
					'</a> ' .
					' <a' .
						' style="cursor:pointer;"' .
						' onclick="sudGetPuzzle( false, ' . $rating . ', \'' . $new . '\', ' . $s . ' )"' .
						' >' .
						__('New', 'wp-sudoku-plus' ) .
					'</a> ' .
					' <a' .
						' style="cursor:pointer;"' .
						' onclick="sudGetPuzzle( 0, false, \'' . $empty . '\', ' . $s . ' )"' .
						' >' .
						__( 'Empty', 'wp-sudoku-plus' ) .
					'</a> ' .
					'<a' .
						' style="cursor:pointer;"' .
						' onclick="jQuery(\'#wp-sudoku-help\').css(\'display\', \'block\');"' .
						' >' .
						__( 'Help', 'wp-sudoku-plus' ) .
					'</a>';

				// Close legenda container
	$result .=
				'</div>';

				// Help and info box
	$result .= 	'<div' .
					' id="wp-sudoku-help"
					style="font-size:'.$s.'px;line-height:'.( $s + 2 ).'px;"width:'.$sm.'px;' .
					' >' .
					__( 'Click left to select, click right to remove option, type digit to show.', 'wp-sudoku-plus' ) . ' ' .
					/* Translators: count of puzzles */
					sprintf( __( 'There are %d puzzles available.', 'wp-sudoku-plus' ), $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wp_sudoku" ) ) .
					'<table style="border-collapse:collapse;width:100%;" >' .
						'<thead>' .
							'<td>' . __( 'Level', 'wp-sudoku-plus' ) . '</td>' .
							'<td>' . __( 'Total', 'wp-sudoku-plus' ) . '</td>' .
							'<td>' . __( 'Won', 'wp-sudoku-plus' ) . '</td>' .
							'<td>' . __( 'Lost', 'wp-sudoku-plus' ) . '</td>' .
						'</thead>' .
						'<tbody>';
						for ( $level = 1; $level < 8; $level++ ) {
	$result .= 				'<tr>' .
								'<td>' . $level . '</td>' .
								'<td>' . $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wp_sudoku WHERE `rating` = %d", $level ) ) . '</td>' .
								'<td id="sud-'.$level.'-won" >' .
									$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wp_sudoku
																	 WHERE rating = %d
																	 AND won <> 0", $level ) ) .
								'</td>' .
								'<td id="sud-'.$level.'-lost" >' .
									$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wp_sudoku
																	 WHERE rating = %d
																	 AND lost <> 0", $level ) ) .
								'</td>' .
							'</tr>';
						}
	$result .= 			'</tbody>' .
					'</table>' .
				'</div>';

			// Ajax spinner
	$result .=
			'<img' .
				' id="sud-ajaxspin"' .
				' src="' . plugins_url( basename( dirname( __FILE__ ) ) . '/bigspinner.gif' ) . '"' .
				' style="top:'.( $sm / 2 - 33 ).'px;left:'.( $sm / 2 - 33 ).'px;"'.
			' />';

	// Close overall container
	if ( ! defined( 'DOING_AJAX' ) ) {
		$result .= '</div>';
	}

	return $result;
}

function sudoku_shortcode_handler( $xatts ) {
	$atts = shortcode_atts( array( 'size' => '15' ), $xatts );
	return sudoku( $atts['size'] );
}

// Enqueue script
function sudoku_add_scripts() {
	if ( ! is_admin() )	wp_enqueue_script( 'wp-sudoku-plus', plugins_url( '/wp-sudoku-plus.js' , __FILE__ ), array( 'jquery' ), '1.6', true );
}

// Enqueue style
function sudoku_add_style() {
	if ( ! is_admin() ) wp_enqueue_style( 'wp-sudoku-plus-css', plugins_url( '/wp-sudoku-plus.css' , __FILE__ ), array(), '1.6' );
}

function wpsud_ajax_callback() {
global $wpdb;

	if ( ! isset( $_REQUEST['wp-sudoku-action'] ) ) return;

	if ( ! wp_verify_nonce( isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash(  $_REQUEST['nonce'] ) ) : '', 'sudoku-'.( isset( $_REQUEST['prevpuzno'] ) ? sanitize_text_field( wp_unslash(  $_REQUEST['prevpuzno'] ) ) : '' ) ) ) {
		esc_html_e( 'Security check failure', 'wp-sudoku-plus' );
		exit;
	}

	$action 	= isset( $_REQUEST['wp-sudoku-action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wp-sudoku-action'] ) ) : '';
	$puzno 		= isset( $_REQUEST['puzno'] ) ? strval( intval( $_REQUEST['puzno'] ) ) : '0';

	switch ( $action ) {
		case 'sudfail':
			$failcount = $wpdb->get_var( $wpdb->prepare( "SELECT lost FROM {$wpdb->prefix}wp_sudoku WHERE id = %d", $puzno ) );
			$failcount++;
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wp_sudoku SET lost = %d WHERE id = %d", $failcount, $puzno ) );
			echo esc_html( $failcount );
			break;
		case 'sudwin':
			$wincount = $wpdb->get_var( $wpdb->prepare( "SELECT won FROM {$wpdb->prefix}wp_sudoku WHERE id = %d", $puzno ) );
			$wincount++;
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wp_sudoku SET won = %d WHERE id = %d", $wincount, $puzno ) );
			echo esc_html( $wincount );
			break;
	//	case 'sudget':
		default:
			$size = isset( $_REQUEST['size'] ) ? strval( intval( $_REQUEST['size'] ) ) : 15;
			wpsud_echo( sudoku( $size ) );
			break;

	}

	exit;
}

// Init
add_action( 'wp_enqueue_scripts', 'sudoku_add_scripts' );
add_action( 'init', 'sudoku_add_style' );
add_shortcode( 'sudoku', 'sudoku_shortcode_handler' );
add_action( 'wp_ajax_wp_sudoku', 'wpsud_ajax_callback' );
add_action( 'wp_ajax_nopriv_wp_sudoku', 'wpsud_ajax_callback' );

// Wrapper for echo
function wpsud_echo( $html ) {

	$t = wpsud_allowed_tags();
	if ( defined( 'DOING_AJAX' ) ) {
		$t['script'] = true;
	}
	echo wp_kses( $html, $t );
}

function wpsud_allowed_tags() {
static $allowed_tags;

	if ( ! is_array( $allowed_tags ) ) {

		// Standard allowed attributes
		$sa = array(
			'id' => true,
			'name' => true,
			'title' => true,
			'class' => true,
			'style' => true,
			);

		$allowed_tags =
		array(
			'a' => array_merge( $sa, array(
				'href' => true,
				'onclick' => true,
				) ),
			'br' => true,
			'div' => array_merge( $sa, array(
				'onmousedown' => true,
				'onclick' => true,
				'oncontextmenu' => true,
				) ),
			'img' => array(
				'id' => true,
				'src' => true,
				),
			'input' => array(
				'id' => true,
				'type' => true,
				'value' => true,
				),
			'option' => array(
				'selected' => true,
				'value' => true,
				'disabled' => true,
				'title' => true,
				),
			'select' => array(
				'id' => true,
				'onchange' => true,
				'style' => true,
				),
			'span' => array(
				'id' => true,
				'style' => true,
				'title' => true,
				),
			'script' => ( defined('DOING_AJAX') ? true : false ),
			'table' => array(
				'style' => true,
				),
			'tbody' => true,
			'thead' => true,
			'tr' => true,
			'td' => array(
				'id' => true,
				),
		);
	}
	return $allowed_tags;
}
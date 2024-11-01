=== WP Sudoku Plus ===
Contributors: opajaap
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=OpaJaap@OpaJaap.nl&item_name=WP-Sudoku-Plus&item_number=Support-Open-Source&currency_code=USD&lc=US
Tags: sudoku, game, puzzle
Requires at least: 6.0
Tested up to: 6.7
Version: 1.6
Stable tag: 1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin displays a sudoku puzzle diagram on your website that the visitor can try to solve.

== Description ==

This plugin can display a sudoku puzzle diagram on your website.
It comes with 200.000 unique puzzles in 7 different levels of difficulty.
The number of times a puzzle is successfully solved or failed is registered.
There is simple help and statistics available at the front-end.
 
== Installation ==

1. Install and activate the plugin through the 'Plugins' screen in WordPress
2. Add the sudoku shortcode to the content of the page or post where you want it to be shown. Example: **[sudoku size="15"]**
The optional size attribute can range from 8 to 32, 16 being the default.
3. After installation, the puzzle database will be filled automaticly in chunks of 10.000 upon displaying the diagrm, until the maximum of 200.000 is reached.

== Frequently Asked Questions ==

= How do i reset the won and lost counters =

If you de-activate the plugin and activate it again, the database table will entirely be rebuilt. This clears the statistics.

== Screenshots ==

1. The puzzle how it shows on the screen
2. The help an statistcs opens by a click on the **Help** link

== Changelog ==

= 1.6 =

* Maintenance release to better comply with the plugin coding standards

= 1.5 =

* Security release. Thanx to https://www.ripstech.com

= 1.4 =

* Added optional clickable boxes, equivalent to tying numbers.

= 1.3 =

* No longer clears data at de-activation
* Fixed spurious php warnings

= 1.2 =

= Bug Fixes =

* Language file is now loaded at the right moment.

= 1.1 =

= Bug Fixes =

* Fixed undefined error in activate. Plugin could only be activated if wp-photo-album-plus was active.

= 1.0 =

* Initial release

== Upgrade Notice ==

= 1.0 =

* This is the initial release.
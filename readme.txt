=== WP Simple Debug ===
Contributors: jessepearson
Tags: simple, debug log
Requires at least: 3.7.1
Tested up to: 4.3.1
Stable tag: 0.0.1

License: GPLv2 or later


== Description ==

WP Simple Debug creates a simple function to allow developers to write to the WordPress debug.log. 

Feature requests or bugs can be added to [the GitHub page](https://github.com/jessepearson/wp-simple-debug).

= Features =

* Creates wpsd_log() function that allows you to log data to the WordPress debug.log
* Turns on debugging and logging
* By default outputs file path and line number where function is called from. 
* Can output full backtrace to how function was called.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `wp-simple-debug` directory to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Note: This will turn on WordPress debugging, which will show any errors in any other plugins or themes.


= Usage =

After installing & activating the plugin...

1. Use the function where you would like to in your code: wpsd_log( $data, $note, $file, $line, $full_backtrace )
1. $data - required - mixed - The data you would like to log. This can be a string, array or object.
1. $note - optional - string - Will log any note you'd like before outputting the data, such as a variable name. Default '';
1. $file - optional - bool - If true, will output the path to file calling the function. Default true.
1. $line - optional - bool - If true, will output the line in the file calling the function. Default true.
1. $full_backtrace - optional - bool - If true, will output the full backtrace to how the function was called, then die to prevent excessive amounts of data being written. Default false.


== Changelog ==
= 0.0.1 =
* Initial release
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

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `wp-simple-debug` directory to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Note: This will turn on WordPress debugging, which will show any errors in any other plugins or themes.


= Usage =

After installing & activating the plugin...

1. Use the function where you would like to in your code: wpsd_log( $data, $name )
1. $data is the data you would like to log. This can be a string, array or object.
1. $name will log a variable name if you'd like to pass the variable name as a string.


== Changelog ==
= 0.0.1 =
* Initial release
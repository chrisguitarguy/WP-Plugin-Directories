Add Plugin Directories to WordPress
===================================

A work in progress related to a [WordPress stack exchange question](http://wordpress.stackexchange.com/questions/43262/add-multiple-plugin-directories).


EXAMPLE

For an example, take a look at the `example_plugin` folder and the `register_addt_plugins_dir.php` file.

You need to copy this folder to your actual plugins folder and then activate it from within your plugins list. It only works if the main plugin is activated.

*) the one you defined with the `WP_PLUGIN_DIR` or `WPMU_PLUGIN_DIR` in your wp-config.php file - or the default `plugins` folder in your install. 


CHANGELOG

0.1   Initial version
0.2   Clean Up & code styling alignment
0.3   Minor styling fixes
0.4   Moved to OOP concept
0.5   Improved API - now supports different plugin locations aside from the `WP_CONTENT_DIR`.
0.5.1 Minor fix for left over debug code
0.5.2 JS styling for readability
0.6   Removed "activate" link when plugin is already active, as suggested by Julien Chaumond in Issue #3

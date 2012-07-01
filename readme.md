Add Plugin Directories to WordPress
===================================

A work in progress related to a [WordPress stack exchange question](http://wordpress.stackexchange.com/questions/43262/add-multiple-plugin-directories).


EXAMPLE

For an example, take a look at the `example_plugin` folder and the `register_addt_plugins_dir.php` file.

You need to copy this folder to your actual plugins folder and then activate it from within your plugins list. It only works if the main plugin is activated.

*) the one you defined with the `WP_PLUGIN_DIR` or `WPMU_PLUGIN_DIR` in your wp-config.php file - or the default `plugins` folder in your install.
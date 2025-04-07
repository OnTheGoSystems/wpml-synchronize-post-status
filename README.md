# WPML Synchronize Status

## Disclaimer

Users may ask for a feature that we won't or can't add to our plugins for several reasons (low demand or higher priorities).

Sometimes, we create feature plugins that address one or more of these features.

However, OnTheGoSystem cannot ensure support for such plugins unless we decide to merge them into the core plugins.

On the other hand, these plugins are public, and everyone is welcome to contribute.

## Feature

When updating the status of an original post, this plugin synchronizes all the translations accordingly.

It won't update the status of an original post when updating the translation. In other words, the synchronization works only in one direction.

## Installation

Just copy the whole project in the "plugins" directory of your WordPress site, then activate the plugin from your website's Plugins page.

## Configuration

In the current iteration, the plugin has no customizable settings.

However, you can configure the post types to keep in sync using one or both options:

- In `wp-config.php`, define a constant called `WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES`, containing either an array or a comma-separated list of custom post type names.  
  Example: `define( WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES , 'post,page');`
- Hook to the `wpml_synchronize_post_status_post_types` filter. This filter receives the current list of post types.  
  Example: `add_filter( 'wpml_synchronize_post_status_post_types', function( $allowed_post_types ) { return array_merge($allowed_post_types, [ 'products' ]); } )`

You can also blacklist post types so they are not synchronized:

- Hook to the `wpml_synchronize_post_status_post_types_exclude` filter. This filter receives the current list of post types to exclude.  
  Example: `add_filter( 'wpml_synchronize_post_status_post_types_exclude', function( $excluded_post_types ) { return array_merge($excluded_post_types, [ 'books' ]); } )`

By default, the array of allowed posts types is empty. That means that all post types will be kept in sync.

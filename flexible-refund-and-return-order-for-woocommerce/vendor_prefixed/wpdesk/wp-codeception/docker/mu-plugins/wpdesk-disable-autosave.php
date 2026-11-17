<?php

namespace FRFreeVendor;

/**
 * Plugin Name: WP Desk Runner Disable Autosave
 * Description: Disables editor autosaving in the Docker test runner.
 */
\add_action('admin_print_scripts', static function () {
    \wp_deregister_script('autosave');
});
\add_action('enqueue_block_editor_assets', static function () {
    \wp_add_inline_script('wp-editor', "wp.data.dispatch( 'core/editor' ).lockPostAutosaving( 'wpdesk-runner' );");
});

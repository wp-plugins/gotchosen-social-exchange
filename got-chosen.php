<?php
/**
 * Plugin Name: GotChosen Integration
 * Plugin URI: http://gotchosen.com
 * Description: Enables support for GotChosen's web curtain and Social Exchange.
 * Version: 1.0.3
 */

// Require our class files.
require_once (plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'got-chosen-api.class.php');
require_once (plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'got-chosen-plugin.class.php');
// Get instance the API class to pass into our main plugin class.
$got_chosen_api_handler = GOT_CHOSEN_API_HANDLER::get_instance();
// Get instance of our main plugin class.
GOT_CHOSEN_INTG_PLUGIN::get_instance($got_chosen_api_handler, __FILE__);
// Unset variable so global space is kept as clean as possible.
unset($got_chosen_api_handler);

// Activation and deactivation hooks to set up and remove options.
register_activation_hook(__FILE__, 'got_chosen_intg_activation');
register_deactivation_hook(__FILE__, 'got_chosen_intg_activation');
function got_chosen_intg_activation() {
  // Set default option values.
  $default_opts = array('feedkey' => '', 'shareable' => true, 'commentable' => true, 'pub_minifeed_default' => true, 'webcurtain' => true, 'webcurtain_compat' => false );
  update_option('got_chosen_intg_settings', $default_opts);
}

function got_chosen_intg_deactivation() {
  // Remove options.
  delete_option('got_chosen_intg_settings');
}

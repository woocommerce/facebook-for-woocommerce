<?php
/**
 * Functions used by plugins
 */
if (! class_exists('WC_Dependencies'))
  include_once 'class-wc-dependencies.php';

/**
 * WC Detection
 */
 if (! function_exists('is_woocommerce_active')) {
   function is_woocommerce_active() {
     return WC_Dependencies::woocommerce_active_check();
   }
 }

/**
 * Queue updates for the WooUpdater
 */
 if (! function_exists('woothemes_queue_update')) {
   function woothemes_queue_update($file, $file_id, $product_id) {
     global $woothemes_queued_updates;

     if (! isset($woothemes_queued_updates))
       $woothemes_queued_updates = array();

     $plugin             = new stdClass();
     $plugin->file       = $file;
     $plugin->file_id    = $file_id;
     $plugin->product_id = $product_id;

     $woothemes_queued_updates[] = $plugin;
   }
 }

/**
 * Prevent conflicts with older versions
 */
if (! class_exists('WooThemes_Plugin_Updater')) {
  class WooThemes_Plugin_Updater { function init() {} }
}

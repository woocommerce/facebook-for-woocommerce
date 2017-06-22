<?php
/**
 * @package FacebookCommerce
 */
if (! defined('ABSPATH')) {
  exit;
}

if (! class_exists('WC_Facebook_Product')) :

/**
 * Custom FB Product proxy class
 */
class WC_Facebook_Product {

  const FB_PRODUCT_DESCRIPTION = 'fb_product_description';

  public function __construct($wpid) {
    $this->id = $wpid;
    $this->fb_description = '';
    $this->fb_visibility = 'published';
    $this->woo_product = wc_get_product($wpid);
  }

  // Fall back to calling method on $woo_product
  public function __call($function, $args) {
    return call_user_func_array(array($this->woo_product, $function), $args);
  }

  public function get_image_urls() {
    if (is_callable(array($this, 'get_gallery_image_ids'))) {
      return $this->get_gallery_image_ids();
    } else {
      return $this->get_gallery_attachment_ids();
    }
  }

  public function get_post_data() {
    if (is_callable('get_post')) {
      return get_post($this->id);
    } else {
      return $this->get_post_data();
    }
  }

  public function set_description($description) {
    $description = WC_Facebookcommerce_Utils::clean_string($description);
    $this->fb_description = $description;
    update_post_meta(
      $this->id,
      self::FB_PRODUCT_DESCRIPTION,
      $description);
  }

  public function get_fb_description() {
    if ($this->fb_description) {
      return $this->fb_description;
    }

    $description = get_post_meta(
      $this->id,
      self::FB_PRODUCT_DESCRIPTION,
      true);

    if ($description) {
      return $description;
    }

    $post = $this->get_post_data();

    $post_content = WC_Facebookcommerce_Utils::clean_string(
      $post->post_content);
    $post_excerpt = WC_Facebookcommerce_Utils::clean_string(
      $post->post_excerpt);
    $post_title = WC_Facebookcommerce_Utils::clean_string(
      $post->post_title);

    // Sanitize description
    if ($post_content) {
      $description = $post_content;
    }
    if ($description == '' && $post_excerpt) {
      $description = $post_excerpt;
    }
    if ($description == '') {
      $description = $post_title;
    }

    return $description;
  }

}

endif;

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

  public function get_all_image_urls() {
    $image_urls = array();
    $parent_image_id = $this->get_parent_image_id();
    $image_url = wp_get_attachment_url(
      ($parent_image_id) ?: $this->woo_product->get_image_id());

    if ($image_url) {
      $image_url = WC_Facebookcommerce_Utils::make_url($image_url);
      array_push($image_urls, $image_url);
    }
    $attachment_ids = $this->get_image_urls();

    // For variable products, add the variation specific image.
    if ($parent_image_id) {
      $image_url2 = wp_get_attachment_url($this->woo_product->get_image_id());
      $image_url2 = WC_Facebookcommerce_Utils::make_url($image_url2);
      if ($image_url != $image_url2) {
        array_push($image_urls, $image_url2);
      }
    }

    foreach ($attachment_ids as $attachment_id) {
      $attachment_url = wp_get_attachment_url($attachment_id);
      if (!empty($attachment_url)) {
        array_push($image_urls,
          WC_Facebookcommerce_Utils::make_url($attachment_url));
      }
    }
    $image_urls = array_filter($image_urls);

    // If there are no images, create a placeholder image.
    if (empty($image_urls)) {
      $name = urlencode(strip_tags($this->woo_product->get_title()));
            $image_url = 'https://placeholdit.imgix.net/~text?txtsize=33&txt='
              . $name . '&w=530&h=530'; // TODO: BETTER PLACEHOLDER
      return array($image_url);
    }
    return $image_urls;
  }

  // Returns the parent image id for variable products only.
  public function get_parent_image_id() {
    if ($this->woo_product->get_type() === 'variation') {
      $parent_data = $this->get_parent_data();
      return $parent_data['image_id'];
    }
    return null;
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

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
  const FB_VISIBILITY = 'fb_visibility';
  const MIN_DATE = '0001-01-01';
  const MAX_DATE = '9999-12-31';

  public function __construct($wpid) {
    $this->id = $wpid;
    $this->fb_description = '';
    $this->fb_visibility = get_post_meta($wpid, self::FB_VISIBILITY, true);
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
    $description = stripslashes(
      WC_Facebookcommerce_Utils::clean_string($description));
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

  public function add_sale_price($product_data) {
    // initialize sale date and sale_price
    $product_data['sale_price_start_date'] = self::MIN_DATE;
    $product_data['sale_price_end_date'] = self::MIN_DATE;
    $product_data['sale_price'] = $product_data['price'];

    $sale_price = $this->woo_product->get_sale_price();
    // check if sale exist
    if (!is_numeric($sale_price)) {
      return $product_data;
    }
    $sale_price =
      intval(round($this->get_price_plus_tax($sale_price) * 100));

    $sale_start =
      ($date = get_post_meta($this->id, '_sale_price_dates_from', true))
      ? date_i18n('Y-m-d', $date)
      : self::MIN_DATE;

    $sale_end =
      ($date = get_post_meta($this->id, '_sale_price_dates_to', true))
      ? date_i18n('Y-m-d', $date)
      : self::MAX_DATE;

    // check if sale is expired and sale time range is valid
    if (strtotime($sale_end) >= time()
      && strtotime($sale_end) >= strtotime($sale_start)) {
        $product_data['sale_price_start_date'] = $sale_start;
        $product_data['sale_price_end_date'] = $sale_end;
        $product_data['sale_price'] = $sale_price;
   }
    return $product_data;
  }

  public function is_hidden() {
    $hidden_from_catalog = has_term(
      'exclude-from-catalog',
      'product_visibility',
      $this->id);
    $hidden_from_search = has_term(
      'exclude-from-search',
      'product_visibility',
      $this->id);
    return ($hidden_from_catalog && $hidden_from_search) || !$this->fb_visibility;
  }

  public function get_price_plus_tax($price) {
    $woo_product = $this->woo_product;
    // // wc_get_price_including_tax exist for Woo > 2.7
    if (function_exists('wc_get_price_including_tax')) {
      $args = array( 'qty' => 1, 'price' => $price);
      return get_option('woocommerce_tax_display_shop') === 'incl'
              ? wc_get_price_including_tax($woo_product, $args)
              : wc_get_price_excluding_tax($woo_product, $args);
    } else {
      return get_option('woocommerce_tax_display_shop') === 'incl'
              ? $woo_product->get_price_including_tax(1, $price)
              : $woo_product->get_price_excluding_tax(1, $price);
    }
  }

  public function get_grouped_product_option_names($key, $option_values) {
    // Convert all slug_names in $option_values into the visible names that
    // advertisers have set to be the display names for a given attribute value
    $terms = get_the_terms($this->id, $key);
    return array_map(
      function ($slug_name) use ($terms) {
        foreach ($terms as $term) {
          if ($term->slug === $slug_name) {
            return $term->name;
          }
        }
        return $slug_name;
      },
      $option_values);
  }

  public function get_variant_option_name($label, $default_value) {
    // For the given label, get the Visible name rather than the slug
    $meta = get_post_meta($this->id, $label, true);
    $attribute_name = str_replace('attribute_', '', $label);
    $term = get_term_by('slug', $meta, $attribute_name);
    return $term->name ?: $default_value;
  }

  public function update_visibility($is_product_page, $visible_box_checked) {
    $visibility = get_post_meta($this->id, self::FB_VISIBILITY, true);
    if ($visibility && !$is_product_page) {
      // If the product was previously set to visible, keep it as visible
      // (unless we're on the product page)
      $this->fb_visibility = $visibility;
    } else {
      // If the product is not visible OR we're on the product page,
      // then update the visibility as needed.
      $this->fb_visibility = $visible_box_checked ? true : false;
      update_post_meta($this->id, self::FB_VISIBILITY, $this->fb_visibility);
    }
  }

}

endif;

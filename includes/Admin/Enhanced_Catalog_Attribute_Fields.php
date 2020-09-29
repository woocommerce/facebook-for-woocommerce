<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Admin;

defined( 'ABSPATH' ) or exit;

/**
 * Enhanced Catalog attribute fields.
 *
 */
class Enhanced_Catalog_Attribute_Fields {
	const FIELD_ENHANCED_CATALOG_ATTRIBUTE_PREFIX = 'wc_facebook_enhanced_catalog_attribute';

  public function render( $category_id ) {
		$category_handler       = facebook_for_woocommerce()->get_facebook_category_handler();
		$category               = $category_handler->get_category_with_attrs($category_id);
		$all_attributes         = $category['attributes'];
		$recommended_attributes = array_filter($all_attributes, function($attr) { return $attr['recommended']; });
		$optional_attributes    = array_filter($all_attributes, function($attr) { return !$attr['recommended']; });

    foreach($recommended_attributes as $attribute) {
      $attr_id = self::FIELD_ENHANCED_CATALOG_ATTRIBUTE_PREFIX.'_'.$attribute['key'];
      ?>
        <tr class="form-field term-<?php echo esc_attr( $attr_id ); ?>-wrap">
          <th scope="row">
            <?php $this->render_label($attr_id, $attribute); ?>
          </th>
          <td>
            <?php $this->render_field($attr_id, $attribute); ?>
          </td>
        </tr>
      <?php
    }
  }

  private function render_label($attr_id, $attribute){
    $label = ucwords(str_replace('_', ' ', $attribute['key']));
    ?>
      <label for="<?php echo $attr_id; ?>">
        <?php echo esc_html( $label ); ?>
        <span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $attribute['description'] ); ?>"></span>
      </label>
    <?php
  }

  private function render_field($attr_id, $attribute) {
    $placeholder = isset($attribute['example']) ? $attribute['example'] : '';
    $can_have_multiple_values = isset($attribute['can_have_multiple_values']) && $attribute['can_have_multiple_values'];
    switch($attribute['type']) {
      case 'enum':
        if($can_have_multiple_values) {
          $this->render_text_field($attr_id, $attribute, $placeholder);
        } else {
          $this->render_select_field($attr_id, $attribute);
        }
        break;

      default: //string
        $this->render_text_field($attr_id, $attribute, $placeholder);
    }

  }

  private function render_select_field($attr_id, $attribute) {
    ?>
      <select name="<?php echo $attr_id; ?>" id="<?php echo $attr_id; ?>">
        <?php foreach($attribute['enum_values'] as $opt) {?>
          <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
        <?php } ?>
      </select/>
    <?php
  }

  private function render_text_field($attr_id, $attribute, $placeholder) {
    if(is_array($placeholder)){
      $placeholder = implode(",", $placeholder);
    }
    ?>
      <input type="text" name="<?php echo $attr_id; ?>" id="<?php echo $attr_id; ?>" placeholder="<?php echo $placeholder; ?>"/>
    <?php
  }
}

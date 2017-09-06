<?php
/**
 * @package FacebookCommerce
 */

if (!class_exists('FacebookWordPress_Config')) :

class FacebookWordPress_Config {
  const SETTINGS_KEY = 'facebook_config';
  const PIXEL_ID_KEY = 'pixel_id';
  const USE_PII_KEY = 'use_pii';
  const MENU_SLUG = 'facebook_options';
  const OPTION_GROUP = 'facebook_option_group';
  const SECTION_ID = 'facebook_settings_section';

  private $options;

  public function __construct() {
    add_action('admin_menu', array($this, 'add_menu'));
    add_action('admin_init', array($this, 'register_settings'));
  }

  public function add_menu() {
    add_options_page(
      'Facebook Pixel Settings',
      'Facebook Pixel',
      'manage_options',
      self::MENU_SLUG,
      array($this, 'create_menu_page'));
  }

  public function create_menu_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page'));
    }
    // Update class field
    $this->options = get_option(self::SETTINGS_KEY);

    ?>
    <div class="wrap">
      <h2>Facebook Pixel Settings</h2>
      <form action="options.php" method="POST">
        <?php
        settings_fields(self::OPTION_GROUP);
        do_settings_sections(self::MENU_SLUG);
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  public function register_settings() {
    register_setting(
      self::OPTION_GROUP,
      self::SETTINGS_KEY,
      array($this, 'sanitize_input'));
    add_settings_section(
      self::SECTION_ID,
      null,
      null,
      self::MENU_SLUG);
    add_settings_field(
      self::PIXEL_ID_KEY,
      'Pixel ID',
      array($this, 'pixel_id_form_field'),
      self::MENU_SLUG,
      self::SECTION_ID);
    add_settings_field(
      self::USE_PII_KEY,
      'Use Advanced Matching on pixel?',
      array($this, 'use_pii_form_field'),
      self::MENU_SLUG,
      self::SECTION_ID);
  }

  public function sanitize_input($input) {
    $new_config = array();
    if (isset($input[self::PIXEL_ID_KEY])) {
      $new_config[self::PIXEL_ID_KEY] = absint($input[self::PIXEL_ID_KEY]);
    }
    $new_config[self::USE_PII_KEY] =
      isset($input[self::USE_PII_KEY]) && $input[self::USE_PII_KEY] == 1
        ? '1'
        : '0';

    return $new_config;
  }

  public function pixel_id_form_field() {
    printf(
      '
<input name="%s" id="%s" value="%s" />
<p class="description">The unique identifier for your unique Facebook Pixel.</p>
      ',
      self::SETTINGS_KEY . '[' . self::PIXEL_ID_KEY . ']',
      self::PIXEL_ID_KEY,
      isset($this->options[self::PIXEL_ID_KEY])
        ? esc_attr($this->options[self::PIXEL_ID_KEY])
        : '');
  }

  public function use_pii_form_field() {
    ?>
    <label for="<?= self::USE_PII_KEY ?>">
      <input
        type="checkbox"
        name="<?= self::SETTINGS_KEY . '[' . self::USE_PII_KEY . ']' ?>"
        id="<?= self::USE_PII_KEY ?>"
        value="1"
        <?php checked(1, $this->options[self::USE_PII_KEY]) ?>
      />
      Enabling Advanced Matching improves audience building.
    </label>
    <p class="description">
      For businesses that operate in the European Union, you may need to take
      additional action. Read the
      <a href="https://developers.facebook.com/docs/privacy/">
        Cookie Consent Guide for Sites and Apps
      </a> for suggestions on complying with EU privacy requirements.
    </p>
    <?php
  }
}

endif;

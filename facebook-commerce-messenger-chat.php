<?php
/**
* @package FacebookCommerce
*/

if (!class_exists('WC_Facebookcommerce_MessengerChat')) :

if (!class_exists('WC_Facebookcommerce_Utils')) {
  include_once 'includes/fbutils.php';
}

class WC_Facebookcommerce_MessengerChat {

  public function __construct($settings) {
    $this->enabled = isset($settings['is_messenger_chat_plugin_enabled'])
      ? $settings['is_messenger_chat_plugin_enabled']
      : 'no';

    $this->page_id = isset($settings['fb_page_id'])
      ? $settings['fb_page_id']
      : '';

    $this->jssdk_version = isset($settings['facebook_jssdk_version'])
      ? $settings['facebook_jssdk_version']
      : '';

    add_action('wp_footer', array($this, 'inject_messenger_chat_plugin'));
  }

  public function inject_messenger_chat_plugin() {
    if ($this->enabled === 'yes') {
      echo sprintf("<div><div
  class=\"fb-customerchat\"
  page_id=\"%s\"
/></div>
<!-- Facebook JSSDK -->
<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId            : '',
      autoLogAppEvents : true,
      xfbml            : true,
      version          : '%s'
    });
  };

  (function(d, s, id){
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) {return;}
      js = d.createElement(s); js.id = id;
      js.src = 'https://connect.facebook.net/en_US/sdk.js';
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>
<div></div>",
        $this->page_id,
        $this->jssdk_version);
    }
  }

}

endif;

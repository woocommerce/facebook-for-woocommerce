/*
 *  Ajax helper function.
 *  Takes optional payload for POST and optional callback.
 */
function ajax(action, payload = null, callback = null, failcallback = null) {
  var data = {
    'action': action,
  };
  if (payload){
    for (var attrname in payload) { data[attrname] = payload[attrname]; }
  }

  // Since  Wordpress 2.8 ajaxurl is always defined in admin header and
  // points to admin-ajax.php
  jQuery.post(ajaxurl, data, function(response) {
    if(callback) {
      callback(response);
    }
  }).fail(function(errorResponse){
    if(failcallback) {
      failcallback(errorResponse);
    }
  });
}

function fb_woo_infobanner_post_click(){
  console.log("Woo infobanner post tip click!");
  return ajax('ajax_woo_infobanner_post_click');
}

function fb_woo_infobanner_post_xout() {
  console.log("Woo infobanner post tip xout!");
  return ajax('ajax_woo_infobanner_post_xout');
}

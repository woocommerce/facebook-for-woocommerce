/*
 *  Ajax helper function.  
 *  Takes optional payload for POST and optional callback.
 */
function ajax(action, payload = null, cb = null, failcb = null) {
  var data = {
    'action': action,
  };
  if (payload){
    for (var attrname in payload) { data[attrname] = payload[attrname]; }
  }  

  // Since  Wordpress 2.8 ajaxurl is always defined in admin header and
  // points to admin-ajax.php
  jQuery.post(ajaxurl, data, function(response) {    
    if(cb) {      
      cb(response);
    }
  }).fail(function(errorResponse){    
    if(failcb) {      
      failcb(errorResponse);
    }
  });  
}

function fb_toggle_visibility(wp_id, published) {  
  var buttonId = document.querySelector("#viz_" + wp_id);
  var tooltip = document.querySelector("#tip_" + wp_id);

  if(published){
    tooltip.setAttribute('data-tip', 
      'Product is synced and published (visible) on Facebook.'
    );
    buttonId.setAttribute('onclick','fb_toggle_visibility('+wp_id+', false)');
    buttonId.innerHTML = 'Hide';
    buttonId.setAttribute('class', 'button');
  } else {
    tooltip.setAttribute('data-tip', 
      'Product is synced but not marked as published (visible) on Facebook.'
    );
    buttonId.setAttribute('onclick','fb_toggle_visibility('+wp_id+', true)');
    buttonId.innerHTML = 'Show';
    buttonId.setAttribute('class', 'button button-primary button-large');
  }

  //Reset tooltip
  jQuery(function($) { 
    $('.tips').tipTip({
      'attribute': 'data-tip',
      'fadeIn': 50,
      'fadeOut': 50,
      'delay': 200
    });
  });

  return ajax(
    'ajax_fb_toggle_visibility', 
    {'wp_id': wp_id, 'published': published}
  );
}
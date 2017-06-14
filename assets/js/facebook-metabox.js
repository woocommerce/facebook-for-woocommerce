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

function fb_reset_product(wp_id) {  
  if(confirm('Resetting Facebook metadata will not remove this product from your shop. ' +
    'If you have duplicated another product and are trying to publish a new Facebook product, ' +
    'click OK to proceed. ' +
    'Otherwise, Facebook metadata will be restored when this product is updated again.')) {
    var metadata = document.querySelector('#fb_metadata');
    if(metadata) {
      metadata.innerHTML = 
      "<b>This product is not yet synced to Facebook.</b>";
    }
    return ajax(
      'ajax_reset_single_fb_product', 
      {'wp_id': wp_id}
    );
  }  
}

function fb_delete_product(wp_id) {  
  if(confirm('Are you sure you want to delete this product on Facebook? If you only want to "hide" the product, '+ 
    'uncheck the "Visible" checkbox and hit "Update". If you delete a product on Facebook and hit "Update" after, ' +     
    'this product will be recreated. To permanently remove this product from Facebook, hit "OK" and close the window.'+
    'This will not delete the product from WooCommerce.')) {
    var metadata = document.querySelector('#fb_metadata');
    if(metadata) {
      metadata.innerHTML = 
      "<b>This product is not yet synced to Facebook.</b>";
    }
    return ajax(
      'ajax_delete_fb_product', 
      {'wp_id': wp_id}
    );
  }  
}
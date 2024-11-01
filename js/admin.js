jQuery(document).ready(function($) {

  var ajaxUrl = DS_Fulfillment.ajaxUrl;

  console.log('WC Fulfillment - Admin');

  var status = jQuery('#fulfillment_status');
  if (status.length) {

    setTimeout(function checkStatus() {
      jQuery.ajax({
        type : "post",
        dataType : "json",
        url : ajaxUrl,
        data : {
          action: "fulfillment_status"
        },
    
        success: function(response) {
          console.log(response);
          status.text(response && response.ok ? response.import_message : '-');
          if (response.import_class) {
            jQuery('#fulfillment_status').removeAttr('class').addClass(response.import_class);
          }

          setTimeout(checkStatus, 5000);
        },
    
        error: function() {
          status.text('-');
          jQuery('#fulfillment_status').removeAttr('class');
          setTimeout(checkStatus, 5000);
        }
      })
    }, 0);
  }
});
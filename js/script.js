jQuery(document).ready(function($) {

  console.log('WC Fulfillment - Front');

  var ajaxUrl = DS_Fulfillment.ajaxUrl;

  window.packetaSelectorOpen = '.packeta-selector-open';
  window.packetaSelectorBranchId = '.packeta-selector-branch-id';   
  window.packetaSelectorBranchName = '.packeta-selector-branch-name';   
       
  window.packetaPrimaryButtonColor = '#39b54a';
  window.packetaBackgroundColor = '#ffffff';
  window.packetaFontColor = '#555555';
  window.packetaFontFamily = 'Arial';

  var packetaApiKey = window.packetaApiKey;

  // TODO[hardcoded]
  packetaApiKey = '880432d7bea665cc';

  var packetaUrl = 'https://widget.packeta.com/www/js/packetaWidget.js';
  packetaUrl = DS_Fulfillment.packetaUrl;
  jQuery('body').append(jQuery('<script src="' + packetaUrl + '" data-api-key="' + packetaApiKey + '"></' + 'script>'));
        
  if (jQuery(window.packetaSelectorOpen).length) {

    var currentBranchId = null;

    jQuery('body').on('mousedown touchstart keydown', '.checkout-button, #place_order', function(e) {
      if (jQuery(window.packetaSelectorOpen).closest('li').find('.shipping_method').is(':checked')) {
        if (!currentBranchId) {
          alert('Please, select Zasilkovna branch.');
          e.stopPropagation();
          e.preventDefault();
          return false;
        }
      }
    });

    setInterval(function() {
      var branchId = jQuery(window.packetaSelectorBranchId).text();
      if (currentBranchId != branchId) {
        currentBranchId = branchId;

        jQuery.ajax({
          type : "post",
          dataType : "json",
          url : ajaxUrl,
          data : {
            action: "fulfillment_zasilkovna_branch",
            branchId: branchId,
            branchName: jQuery(window.packetaSelectorBranchName).text()
          },
    
          success: function(response) {
            console.log(response);
          },
      
          error: function() {
          }
        });
      }
    }, 10);
  }
});
(function( $ ) {
        'use strict';

        $( function() {
                var field = $( '#mojito_shipping_ccr_guide_number' );
                if ( field.length && typeof mojito_ajax !== 'undefined' ) {
                        $.post(
                                mojito_ajax.ajax_url,
                                { action: 'mojito_shipping_ccr_get_guide_number' },
                                function( response ) {
                                        try {
                                                var data = JSON.parse( response );
                                                if ( data.success ) {
                                                        field.val( data.guide_number );
                                                }
                                        } catch (e) {}
                                }
                        );
                }
        });

	 $(document).on('click', '.mojito-shipping-toggle-tracking', function(event) {
		 event.preventDefault();
		 $('.tracking-details').toggleClass('open');
	 });


	 /*
		Download PDF
	*/
	$(document).on('click', '.mojito-shipping-download-customer-pdf:not(.working)', function (event) {

		event.preventDefault();
		$(this).addClass('working');

		var order_id = $(this).attr('id');

		var data = {
			action: 'mojito_shipping_pymexpress_download_pdf_customer',
			order_id: order_id,
		};

		$.post( mojito_shipping_ajax.ajax_url, data, function (response) {
			
			var data = JSON.parse(response);
			var guide_number = data.guide_number;
			var element = document.createElement('a');
			
			element.setAttribute('href', 'data:application/pdf;base64,' + data.content);
			element.setAttribute('download', guide_number + '.pdf' );
			element.style.display = 'none';
			document.body.appendChild(element);
			element.click();
			document.body.removeChild(element);

			$('.mojito-shipping-download-customer-pdf').removeClass('working');

		});

		return false;

	});

})( jQuery );

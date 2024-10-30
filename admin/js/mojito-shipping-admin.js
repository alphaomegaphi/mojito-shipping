(function( $ ) {
	
	'use strict';

	/**	
	 * Tabs control
	 */
	$(document).on('click', '.mojito-settings-wrap.shipping #mojito-settings-tabs-wrapper a', function () {

		$('.mojito-settings-wrap.shipping #mojito-settings-tabs-wrapper a').removeClass('active');
		$(this).addClass('active');

		var target = $(this).data('target');
		$('.mojito-settings-wrap.shipping .tab-content').removeClass('active');
		$('.mojito-settings-wrap.shipping .tab-content.target-' + target).addClass('active');
		localStorage['mojito-shipping-active-tab'] = target;

	})

	/**	
	 * Open or close at start
	*/
	$(document).ready(function(){
		for (var i = 0; i < localStorage.length; i++) {
			var key = localStorage.key(i);
			if (key.indexOf('mojito-shipping') !== -1 ){
				
				var cls = localStorage.getItem(localStorage.key(i));
				var item = $('#' + key.replace('mojito-shipping-', ''));

				if(cls == 'closed'){
					item.removeClass('open');
					item.addClass('closed');
				}else{
					item.addClass('open');
					item.removeClass('closed');
				}

				// Tabs
				if (key == 'mojito-shipping-active-tab'){
					
					var target = localStorage.getItem(localStorage.key(i));

					$('.mojito-settings-wrap.shipping #mojito-settings-tabs-wrapper a').removeClass('active');
					$('.mojito-settings-wrap.shipping #mojito-settings-tab-' + target ).addClass('active');
					
					$('.mojito-settings-wrap.shipping .tab-content').removeClass('active');
					$('.mojito-settings-wrap.shipping .tab-content.target-' + target).addClass('active');
				}
			}
			
		}
	})

	/*
		Toggle admin fields
	*/
	$(document).on('click', '.mojito-settings-wrap.shipping button.handlediv, .mojito-settings-wrap.shipping .mojito-box .title', function () {
		
		var box = $(this).parent();
		var box_id = box.attr('id')

		if( box.hasClass('closed') ){			
			box.removeClass('closed');
			localStorage['mojito-shipping-' + box_id] = 'open';
		}else{			
			box.addClass('closed');
			localStorage['mojito-shipping-' + box_id] = 'closed';
		}

	})

	/**	
	 * Search filter
	 */
	$(document).on('keyup change', '.mojito-settings-wrap.shipping #mojito-settings-filter', function(event) {
		
		if ( localStorage['mojito-shipping-active-filtering'] == '1' ){
			return;
		}

		localStorage['mojito-shipping-active-filtering'] = '1';

		var string = $(this).val().toLowerCase();		

		if (string.length == 0) {
			$('.mojito-settings-wrap.shipping .mojito-hidden').removeClass('mojito-hidden');
			localStorage['mojito-shipping-active-filtering'] = '0';
			return;
		}

		var options = $('.mojito-settings-wrap.shipping .mojito-input-wrap input, .mojito-settings-wrap.shipping .mojito-input-wrap select, .mojito-settings-wrap.shipping .mojito-input-wrap textarea');

		options.each(function (index) {

			var setting_name = $(this).attr('name').toLowerCase();
			var setting_text = $(this).text().toLowerCase();
			var target = $(this).parent().parent().parent();
			var box = $(this).closest('.mojito-box');
			var box_items = box.find('table tr').length;
			var hidden_items = box.find('table tr.mojito-hidden').length;

			if( box_items == hidden_items ){
				box.addClass('mojito-hidden');
			}else{
				box.removeClass('mojito-hidden');
			}

			if (setting_name !== undefined) {

				if ( setting_name.indexOf(string) !== -1 ||  setting_text.indexOf(string) !== -1 ) {
					target.removeClass('mojito-hidden');
					target.show();
				} else {
					target.addClass('mojito-hidden');
					target.hide();
				}
			}

		});
		
		localStorage['mojito-shipping-active-filtering'] = '0';

	});

	/*
		Manual Request (there is no guide number)
		Legacy system
	*/
	$(document).on('click', '.mojito-shipping-ccr-manual-request', function (event) {
		
		event.preventDefault();

		$(this).addClass('working');

		var order_id = $(this).attr('id');

		var data = {
			action: 'mojito_shipping_ccr_manual_request_guide_number',			
			order_id: order_id,
		};

		$.post('admin-ajax.php', data, function (response) {
			if (response == 'true') {
				location.reload();
			}
		});
	});

	/*
		Manual Request (there is no guide number)
		New system
	*/
	$(document).on('click', '.mojito-shipping-pymexpress-manual-request', function (event) {

		event.preventDefault();

		$(this).addClass('working');

		var order_id = $(this).attr('id');

		var data = {
			action: 'mojito_shipping_pymexpress_manual_request_guide_number',
			order_id: order_id,
		};

		$.post('admin-ajax.php', data, function (response) {
			if (response == 'true') {
				location.reload();
			}
		});
	});


	/*
		Manual Register (there is guide number but there was an error)
		Legacy system
	*/
	$(document).on('click', '.mojito-shipping-ccr-manual-register', function (event) {

		event.preventDefault();

		$(this).addClass('working');

		var order_id = $(this).attr('id');

		var data = {
			action: 'mojito_shipping_ccr_manual_register_guide_number',
			order_id: order_id,
		};

		$.post('admin-ajax.php', data, function (response) {
			if (response == 'true') {
				location.reload();
			}
		});
	});

	/*
		Manual Register (there is guide number but there was an error)
		New system
	*/
	$(document).on('click', '.mojito-shipping-pymexpress-manual-register', function (event) {

		event.preventDefault();

		$(this).addClass('working');

		var order_id = $(this).attr('id');

		var data = {
			action: 'mojito_shipping_pymexpress_manual_register_guide_number',
			order_id: order_id,
		};

		$.post('admin-ajax.php', data, function (response) {
			if (response == 'true') {
				location.reload();
			}
		});
	});

	/*
		Download PDF
		Legacy system
	*/
	$(document).on('click', '.mojito-shipping-ccr-download-pdf:not(.working)', function (event) {

		event.preventDefault();
		$(this).addClass('working');

		var order_id = $(this).attr('id');

		var data = {
			action: 'mojito_shipping_ccr_download_pdf',
			order_id: order_id,
		};

		$.post( 'admin-ajax.php', data, function (response) {
			
			var data = JSON.parse(response);
			var guide_number = data.guide_number;
			var element = document.createElement('a');
			
			element.setAttribute('href', 'data:application/pdf;base64,' + data.content);
			element.setAttribute('download', guide_number + '.pdf' );
			element.style.display = 'none';
			document.body.appendChild(element);
			element.click();
			document.body.removeChild(element);

			$('.mojito-shipping-ccr-download-pdf').removeClass('working');

		});

		return false;

	});

	/*
		Download PDF
		New system
	*/
	$(document).on('click', '.mojito-shipping-pymexpress-download-pdf:not(.working)', function (event) {

		event.preventDefault();
		$(this).addClass('working');

		var order_id = $(this).attr('id');

		var data = {
			action: 'mojito_shipping_pymexpress_download_pdf',
			order_id: order_id,
		};

		$.post('admin-ajax.php', data, function (response) {

			var data = JSON.parse(response);
			var guide_number = data.guide_number;
			var element = document.createElement('a');

			element.setAttribute('href', 'data:application/pdf;base64,' + data.content);
			element.setAttribute('download', guide_number + '.pdf');
			element.style.display = 'none';
			document.body.appendChild(element);
			element.click();
			document.body.removeChild(element);

			$('.mojito-shipping-pymexpress-download-pdf').removeClass('working');

		});

		return false;

	});

	/**	
	 * Update Cantons lists
	 */
	$(document).on('change','.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-province', function() {
		
		var province = $(this).val()
		if ( province.length > 0 ) {
			$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-canton').addClass('working');

			var data = {
				action: 'mojito_shipping_pymexpress_get_cantons_list',
				province: province,
			};

			$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-canton').empty();
			$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-district').empty();
			$.post('admin-ajax.php', data, function (response) {			
				
				$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-canton').append('<option value=""></option>');
				
				var data = JSON.parse( response );
				Object.entries(data).forEach(([key, value]) => {
					$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-canton').append('<option value="' + key + '">' + value + '</option>');
				});
				$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-canton').removeClass('working');
			});
		}
	
	})

	/**		
	 * Update district list
	 */
	$(document).on('change','.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-canton', function() {
		
		var province = $('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-province').val()
		var canton = $(this).val()

		if ( province.length > 0 && canton.length > 0 ) {
		
			$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-district').addClass('working');

			var data = {
				action: 'mojito_shipping_pymexpress_get_district_list',
				province: province,
				canton: canton,
			};

			$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-district').empty();
			$.post('admin-ajax.php', data, function (response) {
				
				$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-district').append('<option value=""></option>');
				var data = JSON.parse( response );
				Object.entries(data).forEach(([key, value]) => {
					$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-district').append('<option value="' + key + '">' + value + '</option>');
				});
				$('.mojito-settings-wrap.shipping #mojito-shipping-pymexpress-store-district').removeClass('working');
			});
		}
	})

	/**	
	 * check all required selects
	 */
	jQuery(window).on("load", function () {

		/**	
		 * Check required fields
		 */
		$('.mojito-settings-wrap.shipping select.required').each(function () {
			if ($(this).val() == '' || $(this).find('option').length == 0 ) {
				$(this).addClass('marked')
			} else {
				$(this).removeClass('marked')
			}
		})
		
	});	
	
	$(document).on('change','.mojito-settings-wrap.shipping select.required', function(){
		if( $(this).val() == '' ){
			$(this).addClass('marked')
		} else {
			$(this).removeClass('marked')
		}
	})


	$(document).on('change','#mojito-shipping-pymexpress-pdf-export-origin', function(){
		var value = $(this).val();
		var rows = document.querySelectorAll('#pymexpress-pdf-export .form-table tr:not(#mojito-shipping-pymexpress-pdf-export-origin-row)');
		if (value == 'pymexpress') {
			rows.forEach(function (row) {
				row.style.display = 'none';
			});
		} else {
			rows.forEach(function (row) {
				row.style.display = '';
			});
		}
	});
})( jQuery );

/**	
 * Based in WC Provincia-Canton-Distrito. Thank you Keylor Mendoza A.!
 */
jQuery(function ($) {

	var cities = JSON.parse(mojito_shipping_ajax.json);
	var debug = mojito_shipping_ajax.debug

	if ( debug == '1' ) {
		debug = true;
	} else {
		debug = false;
	}

	function log( $msg ) {
		if ( debug ) {
			console.log( $msg )
		}
	}

	/**
	** WC Provincia-Canton functionality
	**/
	function getEnhancedSelectFormatString() {
		log( 'running: getEnhancedSelectFormatString()' );
		if ( typeof wc_country_select_params === 'undefined' ) {
			log( 'wc_country_select_params is undefined' )
			return;
		}

		return {
			'language': {
				errorLoading: function () {
					// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
					return wc_country_select_params.i18n_searching;
				},
				inputTooLong: function (args) {
					var overChars = args.input.length - args.maximum;

					if (1 === overChars) {
						return wc_country_select_params.i18n_input_too_long_1;
					}

					return wc_country_select_params.i18n_input_too_long_n.replace('%qty%', overChars);
				},
				inputTooShort: function (args) {
					var remainingChars = args.minimum - args.input.length;

					if (1 === remainingChars) {
						return wc_country_select_params.i18n_input_too_short_1;
					}

					return wc_country_select_params.i18n_input_too_short_n.replace('%qty%', remainingChars);
				},
				loadingMore: function () {
					return wc_country_select_params.i18n_load_more;
				},
				maximumSelected: function (args) {
					if (args.maximum === 1) {
						return wc_country_select_params.i18n_selection_too_long_1;
					}

					return wc_country_select_params.i18n_selection_too_long_n.replace('%qty%', args.maximum);
				},
				noResults: function () {
					return wc_country_select_params.i18n_no_matches;
				},
				searching: function () {
					return wc_country_select_params.i18n_searching;
				}
			}
		};
	}

	var select2_args = $.extend({
		placeholderOption: 'first',
		width: '100%'
	}, getEnhancedSelectFormatString());

	function state_city_load() {
		log( 'running: state_city_load()' );
		var $this_state = $('select.state_select');
		var $this_city = $('input[id*="_city"]');
		var state_id = $this_state.attr('id'), state_val = $this_state.val();
		var city_id = $this_city.attr('id'), city_val = $this_city.val();

		setTimeout(function () {
			if (state_val != "") {
				$this_state.parents('.form-row').data('val', state_val).data('id', state_id);
				$this_state.trigger('change');
			} else {
				$('select.state_select option').each(function () {
					cur = $(this).not(':empty').first().val();
					if (cur)
						return false;
				});
				$this_state.val(cur);
				$this_state.trigger('change');
			}

			if (city_val != "") {
				$this_city.parents('.form-row').data('val', city_val).data('id', city_id);
				$this_state.trigger('change');
			}
		}, 450);
	}
	state_city_load();

	function country_load() {
		log('running: country_load()');
		var $this_country = $('select.country_select');
		var country_id = $this_country.attr('id'), country_val = $this_country.val();

		if (country_val != "") {
			$this_country.parents('.form-row').data('val', country_val).data('id', country_id);
		}
	}
	country_load();

	$(document.body).on('click', '.shipping-calculator-button', function () {
		setTimeout(function () {
			$('#calc_shipping_state').trigger('change');
		}, 200);
	});

	$(document).on('change', 'select.country_to_state', function () {
		var $parent = $(this).closest('.form-row').parent();
		var country = $(this).val();
		$(document.body).trigger('country_changing', [country, $parent]);
		country_load();
	});

	$(document).on('change', 'select.state_select', function () {
		var $container = $(this).closest('.form-row').parent();
		var state = $(this).val();
		var country = $container.find('.country_to_state').val();

		if (cities[country] != undefined || $('.woocommerce-shipping-calculator').length) {
			$(document.body).trigger('state_changing', [state, $container]);
		}
	});

	$(document).on('change', 'select.city_select', function () {
		var $container = $(this).closest('.form-row').parent();
		var state = $container.find('select.state_select').val();
		var city = $(this).val();
		var country = $container.find('.country_to_state').val();

		if (cities[country] != undefined) {
			$(document.body).trigger('update_checkout').trigger('city_changing', [state, city, $container]);
		}
	});

	$('body').on('country_changing', function (e, country, $parent) {
		var $state = $parent.find('select.state_select');
		var $citybox = $parent.find('select.city_select');

		country_changing($state, $citybox, country, $parent);
	});

	$('body').on('state_changing', function (e, state, $container) {
		var $citybox = $container.find('#billing_city, #shipping_city, #calc_shipping_city');
		city_select($citybox, state);
	});

	$('body').on('city_changing', function (e, state, city, $container) {
		var $citybox = $container.find('#billing_city, #shipping_city, #calc_shipping_city');
		change_zip_code($citybox, $container, state, city);
	});

	/**
	** Compatibility to multiple countries
	**/
	function country_changing($state, $citybox, country, $parent) {
		log('running: country_changing()');
		var state = $state.val();
		var city  = $citybox.val();

		if ($state.is('select')) {
			state = (state) ? state : $state.find('option:eq(1)').val();
			$state.val(state);
			if ($state.length) {
				$state.select2(select2_args);
				$state.trigger('change');
			}
		}

		if ($citybox.is('select') && cities[country] === undefined) {
			var input_name = $citybox.attr('name');
			var input_id = $citybox.attr('id');
			var placeholder = $citybox.attr('placeholder');

			$citybox.replaceWith('<input type="text" name="' + input_name + '" id="' + input_id + '" class="city_select input-text" placeholder="' + placeholder + '" />');
			if (city) {
				$('#' + input_id).val(city);
			}

			$citybox = $('#' + input_id);
			$citybox.next('.select2').remove();

			$state.val(state);
		}
	}

	function city_select($citybox, state) {
		log('running: city_select()');
		
		var country = $citybox.closest('form').find('.country_to_state').val();
		if ( country !== 'CR' ) {
			return;
		}
		var current_cities = cities[country][state];
		var value = $citybox.val();

		if (mojito_shipping_ajax.json != '') {
			if ($citybox.is('input')) {
				var input_name = $citybox.attr('name');
				var input_id = $citybox.attr('id');
				var placeholder = $citybox.attr('placeholder');
				var value = $citybox.val();

				$citybox.replaceWith('<select name="' + input_name + '" id="' + input_id + '" class="city_select" data-value="' + value + '" placeholder="' + placeholder + '"></select>');

				$citybox = $('#' + input_id);
			} else {
				$citybox.prop('disabled', false);
			}

			var options = '';
			if ( mojito_shipping_ajax.preselect_address == 'no' ) {
				options = options + '<option></option>';
			}
			for (var index in current_cities) {
				if (current_cities.hasOwnProperty(index)) {
					var cityName = current_cities[index]['city'];

					options = options + '<option value="' + cityName + '">' + cityName + '</option>';
				}
			}

			$citybox.html('<option value="">' + mojito_shipping_ajax.city_first_option + '</option>' + options);

			if (value != '' && $('option[value="' + value + '"]', $citybox).length) {
				$citybox.val(value).change();
			} else {
				$citybox.val($citybox.find('option:eq(1)').val()).change();
			}

			if ($citybox.length) {
				$citybox.select2(select2_args);
			}
			$(document.body).trigger('city_to_select');
		}
	}

	function change_zip_code($citybox, $container, state, city) {
		log('running: change_zip_code()');
		if ( state == null ) {
			return;
		}
		var country = $container.find('.country_to_state').val();
		var current_cities = cities[country][state];

		for (var index in current_cities) {
			if (current_cities.hasOwnProperty(index)) {
				if (current_cities[index]['city'] === city) {
					$container.find('input[id*="postcode"], input[id*="post_code"]').val(current_cities[index]['zip']);
				}
			}
		}
	}

	/**
	** Admin options
	**/
	$(document).on('click', 'a.edit_address', function (e) {
		$(document).trigger('wcadmin_order_edit_billing_address_click');
	});

	$(document).on('wcadmin_order_edit_billing_address_click', function (e) {
		admin_billing_change();
		admin_shipping_change();
	});

	$(document).on('change', '#_billing_state', admin_billing_change);
	function admin_billing_change() {
		log('running: admin_billing_change()');
		var billing_obj = $('#_billing_state');
		var billing_val = billing_obj.val() || woocommerce_admin_meta_boxes_order.default_state;
		if (billing_obj.length) {
			city_select($('#_billing_city'), billing_val);
		}
	}

	$(document).on('change', '#_shipping_state', admin_shipping_change);
	function admin_shipping_change() {
		log('running: admin_shipping_change()');
		var shipping_obj = $('#_shipping_state');
		var shipping_val = shipping_obj.val() || woocommerce_admin_meta_boxes_order.default_state;
		if (shipping_obj.length) {
			city_select($('#_shipping_city'), shipping_val);
		}
	}

	$(document).on('change', '#_billing_city, #_shipping_city', function () {
		var $container = $(this).closest('div');
		var state = $container.find('select.js_field-state').val();
		var city = $(this).val();
		change_zip_code($(this), $container, state, city);
	});
});

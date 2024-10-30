<?php
/**
 * A class definition that includes forms and inputs for admin
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/includes
 */

namespace Mojito_Shipping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class
 */
class Mojito_Settings {

	/**
	 * Main title
	 *
	 * @var String Main Title.
	 */
	protected $main_title;

	/**
	 * Boxes
	 *
	 * @var Array Setting Boxes
	 */
	protected $boxes;

	/**
	 * Settings
	 *
	 * @var Array Settings options
	 */
	protected $settings;

	/**
	 * Tabs
	 *
	 * @var Array Setting tabs
	 */
	protected $tabs;

	/**
	 * Settings loaded from WP
	 *
	 * @var Array settings in db
	 */
	private $live_settings;

	/**
	 * Flag to know if settings were already loaded.
	 *
	 * @var bool flag
	 */
	private $settings_loaded;

	/**
	 * Links for help
	 *
	 * @var Array links for help.
	 */
	public $help_links;

	/**
	 * Constructor
	 *
	 * @param string $main_title Optional title.
	 */
	public function __construct( $main_title = '' ) {

		$this->main_title      = $main_title;
		$this->boxes           = array();
		$this->tabs            = array();
		$this->live_settings   = array();
		$this->settings_loaded = false;
		$this->help_links      = array();
	}

	/**
	 * Add tab
	 *
	 * @param array $tabs registered tabs.
	 * @return void
	 */
	public function add_tabs( $tabs = array() ) {
		$this->tabs = array_merge( $this->tabs, $tabs );
	}

	/**
	 * Add setting box
	 *
	 * @param array $args params for box.
	 * @return void
	 */
	public function add_setting_box( $args = array() ) {

		$defaults = array(
			'title'       => null,
			'description' => null,
			'inputs'      => array(),
			'box-id'      => null,
		);

		$data = array_merge( $defaults, $args );

		$this->settings[] = $data;

	}

	/**
	 * Get setting box
	 *
	 * @param array $setting Params for the box.
	 * @return string
	 */
	private function get_setting_box( $setting ) {

		$class = ( ! empty( $setting['class'] ) ) ? $setting['class'] : '';

		$html  = '';
		$html .= '<div class="mojito-box ' . $class . '" id="' . $setting['box-id'] . '">';

		$html .= '<button type="button" class="handlediv" aria-expanded="false">';
		$html .= '<span class="screen-reader-text">Toggle panel: ' . $setting['title'] . '</span>';
		$html .= '<span class="toggle-indicator" aria-hidden="true"></span>';
		$html .= '</button>';

		$link = '';
		if ( isset( $this->help_links[ $setting['box-id'] ] ) ) {
			$link .= '<a href="' . $this->help_links[ $setting['box-id'] ] . '" target="_blank" class="mojito-help-link">';
			$link .= '<img src="' . plugin_dir_url( __DIR__ ) . 'public/img/help.svg">';
			$link .= '</a>';
		}

		$html .= '<div class="title"><h3>' . $setting['title'] . ' ' . $link . '</h3></div>';
		$html .= '<div class="inner">';
		if ( ! empty( $setting['description'] ) ) {
			$html .= '<p>' . $setting['description'] . '</p>';
		}

		if ( isset( $setting['callback'] ) && is_callable( $setting['callback'] ) ) {

			if ( is_array( $setting['callback'] ) ) {

				$object = $setting['callback'][0];
				$method = $setting['callback'][1];

				if ( $object instanceof self ) {
					$html .= $this->$method();
				} else {
					$html .= $object->$method();
				}
			} else {
				$function = $setting['callback'];
				$html    .= $function();
			}
		} else {

			$html .= '<table class="form-table">';
			$html .= '<tbody>';

			foreach ( $setting['inputs'] as $key => $input ) {

				$option_name  = 'mojito-shipping-' . $setting['box-id'] . '-' . $input['name'];
				$option_value = $this->live_settings[ $option_name ];

				$html .= '<tr id="' . $option_name . '-row">';
				$html .= '<th>';
				$html .= '<label for="mojito-field">' . $input['label'] . '</label>';
				$html .= '</th>';
				$html .= '<td>';

				$html .= '<div class="mojito-input-wrap">';

				

				if ( 'number' === $input['type'] ) {
					if ( ! is_numeric( $option_value ) || $option_value < 0 ) {
						$option_value = ( ! empty( $input['value'] ) ) ? $input['value'] : 0;
					}
				} else {
					if ( empty( $option_value ) ) {
						$option_value = ( ! empty( $input['value'] ) ) ? $input['value'] : '';
					}
				}

				$class       = ( ! empty( $input['class'] ) ) ? $input['class'] : '';
				$placeholder = ( ! empty( $input['placeholder'] ) ) ? $input['placeholder'] : '';

				switch ( $input['type'] ) {

					case 'text':
						$html .= '<input type="text" id="' . $option_name . '" name="' . $option_name . '" value="' . $option_value . '" class="' . $class . '" placeholder="' . $placeholder . '">';
						break;

					case 'email':
						$html .= '<input type="email" id="' . $option_name . '" name="' . $option_name . '" value="' . $option_value . '" class="' . $class . '" placeholder="' . $placeholder . '">';
						break;

					case 'number':
						$html .= '<input type="number" id="' . $option_name . '" name="' . $option_name . '" value="' . $option_value . '" class="' . $class . '" placeholder="' . $placeholder . '">';
						break;

					case 'password':
						$html .= '<input type="password" id="' . $option_name . '" name="' . $option_name . '" value="' . $option_value . '" class="' . $class . '" placeholder="' . $placeholder . '">';
						break;

					case 'select':
						$html .= '<select id="' . $option_name . '" name="' . $option_name . '" class="' . $class . '" placeholder="' . $placeholder . '">';

						foreach ( $input['options'] as $option_key => $value ) {
							$option_key = (string) $option_key;
							if ( isset( $input['data-type'] ) && 'array' === $input['data-type'] && is_array( $option_value ) ) {
								$selected = ( in_array( $option_key, $option_value, true ) ) ? 'selected' : '';
							} else {
								$selected = ( $option_key === $option_value ) ? 'selected' : '';
							}
							$html .= '<option value="' . $option_key . '" ' . $selected . '>' . $value . '</option>';
						}
						$html .= '</select>';
						break;

					case 'multiselect':
						$html .= '<select id="' . $option_name . '" name="' . $option_name . '[]" class="' . $class . '" multiple>';
						foreach ( $input['options'] as $option_key => $value ) {
							if ( ! is_array( $option_value ) ) {
								$option_value = array();
							}
							$selected = ( in_array( $option_key, $option_value, true ) ) ? 'selected' : '';
							$html    .= '<option value="' . $option_key . '" ' . $selected . '>' . $value . '</option>';
						}
						$html .= '</select>';
						break;

					case 'textarea':
						$html .= '<textarea id="' . $option_name . '" name="' . $option_name . '" class="' . $class . '">' . $option_value . '</textarea>';
						break;

					case 'checkbox':
						foreach ( $input['options'] as $option_key => $value ) {
							$checked = ( 'true' === $option_value ) ? 'checked' : '';
							$html   .= '<div class="checkbox-container">';
							$html   .= '<input type="checkbox" name="' . $option_name . '" value="' . $option_key . '" class="' . $class . '" ' . $checked . '>' . $value;
							$html   .= '</div>';
						}
						break;

					default:
						$html .= '<input type="text" id="' . $option_name . '" name="' . $option_name . '" value="' . $option_value . '" class="' . $class . '">';

						break;
				}
				if ( ! empty( $input['tooltip'] ) ) {
					$html .= '<p class="tooltip">' . $input['tooltip'] . '</p>';
				}
				$html .= '</div>';
				$html .= '</td>';

				$html .= '</tr>';
			}
			$html .= '</tbody>';
			$html .= '</table>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Display settings
	 *
	 * @return void
	 */
	public function display( $class = '' ) {

		$html = '';
		$tabs = array();
		$this->load_settings();

		$html .= '<div class="wrap mojito-settings-wrap ' . $class . '">';

		// Add settings Filter.
		$html .= '<form id="mojito-settings-filter-form" onsubmit="return false;">';
		$html .= '<label id="mojito-settings-filter-label">';
		$html .= __( 'Filter settings:', 'mojito-shipping' );
		$html .= '</label>';
		$html .= '<input type="text" id="mojito-settings-filter" placeholder="' . __( 'Type to search', 'mojito-shipping' ) . '">';
		$html .= '</form>';

		$html .= '<form action="" method="post">';
		$html .= settings_fields( 'mojito-shipping-group' );
		$html .= '<h1>' . $this->main_title . '</h1>';

		/**
		 * Build tabs
		 */
		$active_tab = 0;
		foreach ( $this->settings as $key => $setting ) {
			if ( isset( $setting['tab-id'] ) ) {
				$tabs[ $setting['tab-id'] ] = array(
					'name'    => $this->tabs[ $setting['tab-id'] ],
					'title'   => '',
					'content' => '',
					'active'  => ( 0 === $active_tab ) ? true : false,
				);
				++$active_tab;
			}
		}

		if ( count( $tabs ) > 0 ) {

			foreach ( $tabs as $tab_id => $tab ) {

				$active_tab_class = '';

				if ( $tab['active'] ) {
					$active_tab_class = 'active';
				}

				$tabs[ $tab_id ]['title'] = '<a id="mojito-settings-tab-' . $tab_id . '" data-target="' . $tab_id . '" class="nav-tab ' . $active_tab_class . '" href="#tab-' . $tab_id . '">' . $tab['name'] . '</a>';
			}
		}

		/**
		 * Build settings form
		 */
		foreach ( $tabs as $tab_id => $tab ) {

			foreach ( $this->settings as $key => $setting ) {

				if ( empty( $setting['tab-id'] ) ) {
					continue;
				}

				if ( $setting['tab-id'] !== $tab_id ) {
					continue;
				}

				if ( isset( $setting['require'] ) ) {

					$required_setting = $setting['require']['required-setting'];
					$required_value   = $setting['require']['required-value'];

					if ( empty( $this->live_settings[ $required_setting ] ) ) {
						unset( $tabs[ $tab_id ] );

					} else {
						if ( is_array( $required_value ) && in_array( $this->live_settings[ $required_setting ], $required_value, true ) ) {
							$tabs[ $tab_id ]['content'] .= $this->get_setting_box( $setting );
						} else {
							if ( $this->live_settings[ $required_setting ] === $required_value || in_array( $required_value, $this->live_settings[ $required_setting ], true ) ) {
								$tabs[ $tab_id ]['content'] .= $this->get_setting_box( $setting );
							} else {
								unset( $tabs[ $tab_id ] );
							}
						}
					}
				} else {
					if ( isset( $tabs[ $tab_id ] ) ) {
						$tabs[ $tab_id ]['content'] .= $this->get_setting_box( $setting );
					}
				}
			}
		}

		/**
		 * Display
		 */
		$html .= '<div id="mojito-settings-tabs-wrapper" class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_id => $tab ) {
			$html .= $tab['title'];
		}
		$html .= '</div>';

		foreach ( $tabs as $tab_id => $tab ) {
			$active_tab_class = '';

			if ( $tab['active'] ) {
				$active_tab_class = 'active';
			}

			$html .= '<div id="mojito-settings-tab-content-' . $tab_id . '" class="tab-content ' . $active_tab_class . ' target-' . $tab_id . '">';
			$html .= $tab['content'];
			$html .= '</div>';
		}
		$html .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( '_mojito_shipping_save' ) . '">';
		$html .= get_submit_button( __( 'Save', 'mojito-shipping' ) );
		$html .= '</form>';
		$html .= '</div>';

		echo $html;
	}

	/**
	 * Create list
	 *
	 * @param array $items Items to list.
	 * @return String HTML
	 */
	public function item_list( $items = array() ) {

		$html  = '';
		$html .= '<ul class="mojito-shipping-information-list">';
		foreach ( $items as $key => $item ) {
			$html .= '<li>' . $item . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}


	/**
	 * Process POST
	 */
	public function process_settings() {

		if ( ! $_POST ) {
			return;
		}

		$nonce = ( ! empty( $_POST['_wpnonce'] ) ) ? $_POST['_wpnonce'] : '';
		if ( ! wp_verify_nonce( $nonce, '_mojito_shipping_save' ) ) {
			return;
		}

		$this->load_settings();

		foreach ( $this->settings as $key => $setting ) {

			if ( empty( $setting['inputs'] ) ) {
				continue;
			}

			foreach ( $setting['inputs'] as $key => $input ) {

				$option_name = 'mojito-shipping-' . $setting['box-id'] . '-' . $input['name'];

				if ( isset( $_POST[ $option_name ] ) ) {

					if ( empty( $_POST[ $option_name ] ) ) {
						switch ( $input['type'] ) {

							case 'text':
							case 'password':
							case 'textarea':
							case 'email':
								update_option( $option_name, '' );
								break;

							case 'number':
								update_option( $option_name, 0 );
								break;

							case 'select':
								update_option( $option_name, array() );
								break;

							default:
								update_option( $option_name, '' );
								break;
						}
					} else {
						$value = '';
						switch ( $input['type'] ) {

							case 'text':
							case 'password':
								$value = sanitize_text_field( $_POST[ $option_name ] );
								break;

							case 'number':
								$value = (int) sanitize_text_field( $_POST[ $option_name ] );
								break;

							case 'textarea':
								$value = sanitize_textarea_field( $_POST[ $option_name ] );
								break;

							case 'email':
								$value = sanitize_email( $_POST[ $option_name ] );
								break;

							case 'select':
							case 'multiselect':
								if ( isset( $input['data-type'] ) && 'array' === $input['data-type'] ) {
									if ( ! is_array( $_POST[ $option_name ] ) ) {
										$post_value = array( sanitize_text_field( $_POST[ $option_name ] ) );
									} else {
										$post_value = array();
										foreach ( $_POST[ $option_name ] as $k => $opt ) {
											$post_value[] = sanitize_text_field( $opt );
										}
									}
									$value = array();
									foreach ( $post_value as $k => $v ) {
										$value[] = sanitize_text_field( $v );
									}
								} else {
									$value = sanitize_text_field( $_POST[ $option_name ] );
								}
								break;

							default:
								$value = sanitize_text_field( $_POST[ $option_name ] );
								break;
						}
						update_option( $option_name, $value );

						do_action( 'save_mojito_setting_' . $option_name, $value );
					}
				} elseif ( 'multiselect' === $input['type'] && empty( $_POST[ $option_name ] ) ) {
					update_option( $option_name, array() );
				}
			}
		}

		$this->load_settings( true );
	}

	/**
	 * Load settings
	 *
	 * @param boolean $force Force load the settings.
	 * @return void
	 */
	public function load_settings( $force = false ) {

		if ( false === $force ) {
			if ( $this->settings_loaded ) {
				return;
			}
		}

		foreach ( $this->settings as $key => $setting ) {
			if ( isset( $setting['inputs'] ) ) {
				foreach ( $setting['inputs'] as $key => $input ) {

					$option_name = 'mojito-shipping-' . $setting['box-id'] . '-' . $input['name'];

					$this->live_settings[ $option_name ] = get_option( $option_name );
				}
			}
		}

		$this->settings_loaded = true;
	}

	/**
	 * Get settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get live settings
	 */
	public function get_live_settings() {
		return $this->live_settings;
	}

	/**
	 * Get tabs
	 */
	public function get_tabs() {
		return $this->tabs;
	}
}

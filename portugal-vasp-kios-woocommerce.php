<?php
/*
 * Plugin Name: Portugal VASP Expresso Kios network for WooCommerce
 * Plugin URI: https://www.webdados.pt/wordpress/plugins/rede-vasp-expresso-kios-em-portugal-para-woocommerce-wordpress/
 * Description: Lets you deliver on the VASP Expresso Kios network of partners. This is not a shipping method. This is an add-on for any shipping method you activate it on.
 * Version: 3.0
 * Author: PT Woo Plugins (by Webdados)
 * Author URI: https://ptwooplugins.com
 * Text Domain: portugal-vasp-kios-woocommerce
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 4.0
 * WC tested up to: 7.3
*/

/* WooCommerce CRUD and HPOS ready */

/**
 * Check if WooCommerce is active
 **/
// Get active network plugins - "Stolen" from Novalnet Payment Gateway
function pvkw_active_nw_plugins() {
	if ( !is_multisite() )
		return false;
	$pvkw_activePlugins = ( get_site_option( 'active_sitewide_plugins' ) ) ? array_keys( get_site_option( 'active_sitewide_plugins' ) ) : array();
	return $pvkw_activePlugins;
}
if ( in_array( 'woocommerce/woocommerce.php', ( array ) get_option( 'active_plugins' ) ) || in_array( 'woocommerce/woocommerce.php', ( array ) pvkw_active_nw_plugins() ) ) {

	/* Loads textdomain */
	add_action( 'init', 'pvkw_load_textdomain' );
	function pvkw_load_textdomain() {
		//load_plugin_textdomain( 'portugal-vasp-kios-woocommerce', false, basename( dirname( __FILE__ ) ) . '/languages' );
		load_plugin_textdomain( 'portugal-vasp-kios-woocommerce' );
	}

	//Init everything
	add_action( 'plugins_loaded', 'pvkw_init', 999 ); // 999 because of WooCommerce Table Rate
	function pvkw_init() {
		//Only on WooCommerce >= 3.0
		if ( version_compare( WC_VERSION, '4.0', '>=' ) ) {
			//Cron
			pvkw_cronstarter_activation();
			add_action( 'pvkw_update_pickup_list', 'pvkw_update_pickup_list_function' );
			//De-activate cron
			register_deactivation_hook( __FILE__, 'pvkw_cronstarter_deactivate' );
			//Add our settings to the available shipping methods - should be a loop with all the available ones
				add_action( 'wp_loaded', 'pvkw_fields_filters' );
				//WooCommerce Table Rate Shipping - http://bolderelements.net/plugins/table-rate-shipping-woocommerce/ - Not available at plugins_loaded time
				add_filter( 'woocommerce_shipping_instance_form_fields_betrs_shipping', 'pvkw_woocommerce_shipping_instance_form_fields_betrs_shipping' );
				//WooCommerce Advanced Shipping - https://codecanyon.net/item/woocommerce-advanced-shipping/8634573 - Not available at plugins_loaded time
				add_filter( 'was_after_meta_box_settings', 'pvkw_was_after_meta_box_settings' );
			//Add to checkout
			add_action( 'woocommerce_review_order_before_payment', 'pvkw_woocommerce_review_order_before_payment' );
			//Add to checkout - Fragment
			add_filter( 'woocommerce_update_order_review_fragments', 'pvkw_woocommerce_update_order_review_fragments' );
			//Validate
			add_action( 'woocommerce_after_checkout_validation', 'pvkw_woocommerce_after_checkout_validation', 10, 2 );
			//Save order meta
			add_action( 'woocommerce_checkout_update_order_meta', 'pvkw_save_extra_order_meta' );
			//Show order meta on order screen
			add_action( 'woocommerce_admin_order_data_after_shipping_address', 'pvkw_woocommerce_admin_order_data_after_shipping_address' );
			add_action( 'woocommerce_admin_order_preview_end', 'pvkw_woocommerce_admin_order_preview_end' );
			add_filter( 'woocommerce_admin_order_preview_get_order_details', 'pvkw_woocommerce_admin_order_preview_get_order_details', 10, 2 );
			//Ajax for point details update
			add_action( 'wc_ajax_' . 'pvkw_point_details', 'wc_ajax_' . 'pvkw_point_details' );
			//Add information to emails
			if ( get_option( 'pvkw_email_info', 'yes' ) == 'yes' ) {
				//Ideally we would use the same space used by the shipping address, but it's not possible - https://github.com/woocommerce/woocommerce/issues/19258
				add_action( 'woocommerce_email_customer_details', 'pvkw_woocommerce_email_customer_details', 30, 3 );
				add_action( 'woocommerce_order_details_after_order_table', 'pvkw_woocommerce_order_details_after_order_table' , 11 );
			}
			//Hide shipping address
			if ( get_option( 'pvkw_hide_shipping_address', 'yes' ) == 'yes' ) {
				add_filter( 'woocommerce_order_needs_shipping_address', 'pvkw_woocommerce_order_needs_shipping_address', 10, 3 );
			}
			//Add instructions to the checkout
			if ( trim( get_option( 'pvkw_instructions', '' ) ) != '' ) {
				add_action( 'woocommerce_after_shipping_rate', 'pvkw_woocommerce_after_shipping_rate', 10, 2 );
			}
			//Settings
			if ( is_admin() && !wp_doing_ajax() ) {
				add_filter( 'woocommerce_shipping_settings', 'pvkw_woocommerce_shipping_settings' );
				add_action( 'admin_notices', 'pvkw_admin_notices' );
			}
		}
	}

	//Scripts
	add_action( 'wp_enqueue_scripts', 'pvkw_wp_enqueue_scripts' );
	function pvkw_wp_enqueue_scripts() {
		if ( ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_cart' ) && is_cart() ) ) {
			if ( !function_exists( 'get_plugin_data' ) ) require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$plugin_data = get_plugin_data( __FILE__ );
			wp_enqueue_style( 'pvkw-css', plugins_url( '/assets/style.css', __FILE__ ), array(), $plugin_data['Version'] );
			if ( class_exists( 'Flatsome_Default' ) && apply_filters( 'pvkw_fix_flatsome', true ) ) {
				wp_enqueue_style( 'pvkw-flatsome-css', plugins_url( '/assets/style-flatsome.css', __FILE__ ), array(), $plugin_data['Version'] );
			}
			if ( is_checkout() ) {
				wp_enqueue_script( 'pvkw-js', plugins_url( '/assets/functions.js', __FILE__ ), array( 'jquery' ), $plugin_data['Version'], true );
				wp_localize_script( 'pvkw-js', 'pvkw', array(
					'shipping_methods' => pvkw_get_shipping_methods(),
					'shop_country'     => wc_get_base_location()['country'],
				) );
			}
		}
	}

	//Add fields to settings
	function pvkw_fields_filters() {
		//Avoid fatal errors on some weird scenarios
		if ( is_null( WC()->countries ) ) WC()->countries = new WC_Countries();
		//Load our filters
		foreach ( WC()->shipping()->get_shipping_methods() as $method ) { //https://woocommerce.wp-a2z.org/oik_api/wc_shippingget_shipping_methods/
			if ( ! $method->supports( 'shipping-zones' ) ) {
				continue;
			}
			switch ( $method->id ) {
				// Flexible Shipping for WooCommerce - https://wordpress.org/plugins/flexible-shipping/
				case 'flexible_shipping':
				case 'flexible_shipping_single':
					add_filter( 'flexible_shipping_method_settings', 'pvkw_woocommerce_shipping_instance_form_fields_flexible_shipping', 10, 2 );
					add_filter( 'flexible_shipping_process_admin_options', 'pvkw_woocommerce_shipping_instance_form_fields_flexible_shipping_save' );
					break;
				// The WooCommerce or other standard methods that implement the 'woocommerce_shipping_instance_form_fields_' filter
				default:
					add_filter( 'woocommerce_shipping_instance_form_fields_'.$method->id, 'pvkw_woocommerce_shipping_instance_form_fields' );
					break;
			}
		}
	}


	//Our field on each shipping method
	function pvkw_woocommerce_shipping_instance_form_fields( $settings ) {
		if ( !is_array( $settings ) ) $settings = array();
		$settings['pvkw'] = array( 
			'title'			=> __( 'VASP Expresso Kios in Portugal', 'portugal-vasp-kios-woocommerce' ),
			'type'			=> 'select',
			'description'	=> __( 'Shows a field to select a point from the VASP Expresso Kios network in Portugal', 'portugal-vasp-kios-woocommerce' ),
			'default'       => '',
			'options'		=> array( 
				''	=> __( 'No', 'portugal-vasp-kios-woocommerce' ),
				'1'	=> __( 'Yes', 'portugal-vasp-kios-woocommerce' ),
			 ),
			'desc_tip'		=> true,
		 );
		return $settings;
	}


	//Our field on Flexible Shipping for WooCommerce - https://wordpress.org/plugins/flexible-shipping/
	function pvkw_woocommerce_shipping_instance_form_fields_flexible_shipping( $settings, $shipping_method ) {
		$settings['pvkw'] = array(
			'title'         => __( 'VASP Expresso Kios in Portugal', 'portugal-vasp-kios-woocommerce' ),
			'type' 	        => 'select',
			'description'	=> __( 'Shows a field to select a point from the VASP Expresso Kios network in Portugal', 'portugal-vasp-kios-woocommerce' ),
			'default'       => isset($shipping_method['pvkw']) && intval($shipping_method['pvkw'])==1 ? '1' : '',
			'options'		=> array( 
				''	=> __( 'No', 'portugal-vasp-kios-woocommerce' ),
				'1'	=> __( 'Yes', 'portugal-vasp-kios-woocommerce' ),
			 ),
			'desc_tip'		=> true,
		);
		return $settings;
	}
	function pvkw_woocommerce_shipping_instance_form_fields_flexible_shipping_save( $shipping_method ) {
		$shipping_method['pvkw'] = $_POST['woocommerce_flexible_shipping_pvkw'];
		return $shipping_method;
	}

	//Our field on WooCommerce Table Rate Shipping - http://bolderelements.net/plugins/table-rate-shipping-woocommerce/
	function pvkw_woocommerce_shipping_instance_form_fields_betrs_shipping( $settings ) {
		$settings['general']['settings']['pvkw'] = array(
			'title'         => __( 'VASP Expresso Kios in Portugal', 'portugal-vasp-kios-woocommerce' ),
			'type' 	        => 'select',
			'description'	=> __( 'Shows a field to select a point from the VASP Expresso Kios network in Portugal', 'portugal-vasp-kios-woocommerce' ),
			'default'       => '',
			'options'		=> array( 
				''	=> __( 'No', 'portugal-vasp-kios-woocommerce' ),
				'1'	=> __( 'Yes', 'portugal-vasp-kios-woocommerce' ),
			 ),
			'desc_tip'		=> true,
		);
		return $settings;
	}

	//Our field on WooCommerce Advanced Shipping - https://codecanyon.net/item/woocommerce-advanced-shipping/8634573
	function pvkw_was_after_meta_box_settings( $settings ) {
		?>
		<p class='was-option'>
			<label for='tax'><?php _e( 'VASP Expresso Kios in Portugal', 'portugal-vasp-kios-woocommerce' ); ?></label>
			<select name='_was_shipping_method[pvkw]' style='width: 189px;'>
				<option value='' <?php @selected( $settings['pvkw'], '' ); ?>><?php _e( 'No', 'portugal-vasp-kios-woocommerce' ); ?></option>
				<option value='1' <?php @selected( $settings['pvkw'], '1' ); ?>><?php _e( 'Yes', 'portugal-vasp-kios-woocommerce' ); ?></option>
			</select>
		</p>
		<?php
	}


	//Get all shipping methods available
	function pvkw_get_shipping_methods() {
		$shipping_methods = array();
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods" );
		foreach ( $results as $method ) {
			switch ( $method->method_id ) {
				// Flexible Shipping for WooCommerce - https://wordpress.org/plugins/flexible-shipping/
				case 'flexible_shipping':
					$options = get_option( 'flexible_shipping_methods_'.$method->instance_id, array() );
					foreach ($options as $key => $fl_options) {
						if ( isset( $fl_options['pvkw'] ) && intval( $fl_options['pvkw'] )==1 ) $shipping_methods[] = $method->method_id.'_'.$method->instance_id.'_'.$fl_options['id'];
					}
					break;
				// WooCommerce Table Rate Shipping - http://bolderelements.net/plugins/table-rate-shipping-woocommerce/
				case 'betrs_shipping':
					$options = get_option( 'woocommerce_betrs_shipping_'.$method->instance_id.'_settings', array() );
					if ( isset( $options['pvkw'] ) && intval( $options['pvkw'] ) == 1 ) {
						$options_instance = get_option( 'betrs_shipping_options-'.$method->instance_id, array() );
						if ( isset( $options_instance['settings'] ) && is_array( $options_instance['settings'] ) ) {
							foreach ( $options_instance['settings'] as $setting ) {
								if ( isset( $setting['option_id'] ) ) $shipping_methods[] = $method->method_id.':'.$method->instance_id.'-'.$setting['option_id'];
							}
						}
					}
					break;
				// Table Rate Shipping - https://woocommerce.com/products/table-rate-shipping/
				case 'table_rate':
					$options = get_option( 'woocommerce_table_rate_'.$method->instance_id.'_settings', array() );
					if ( isset( $options['pvkw'] ) && intval( $options['pvkw'] ) == 1 ) {
						$rates = $wpdb->get_results( sprintf( "SELECT rate_id FROM {$wpdb->prefix}woocommerce_shipping_table_rates WHERE shipping_method_id = %d ORDER BY rate_order ASC", $method->instance_id ) );
						foreach ( $rates as $rate ) {
							$shipping_methods[] = $method->method_id.':'.$method->instance_id.':'.$rate->rate_id;
						}
					}
					break;
				// The WooCommerce or other standard methods that implement the 'woocommerce_shipping_instance_form_fields_' filter
				default:
					$options = get_option( 'woocommerce_'.$method->method_id.'_'.$method->instance_id.'_settings', array() );
					if ( isset( $options['pvkw'] ) && intval( $options['pvkw'] )==1 ) $shipping_methods[] = $method->method_id.':'.$method->instance_id;
					break;
			}
		}
		//WooCommerce Advanced Shipping - https://codecanyon.net/item/woocommerce-advanced-shipping/8634573
		if ( class_exists( 'WooCommerce_Advanced_Shipping' ) ) {
			$methods = get_posts( array( 'posts_per_page' => '-1', 'post_type' => 'was', 'orderby' => 'menu_order', 'order' => 'ASC', 'suppress_filters' => false ) );
			foreach ( $methods as $method ) {
				$settings = get_post_meta( $method->ID, '_was_shipping_method', true );
				if ( is_array( $settings ) && isset( $settings['pvkw'] ) && intval( $settings['pvkw'] ) == 1 ) {
					$shipping_methods[] = (string)$method->ID;
				}
			}
		}
		//Filter and return them
		$shipping_methods = array_unique( apply_filters( 'pvkw_get_shipping_methods', $shipping_methods ) );
		return $shipping_methods;
	}

	
	//Add our DIV to the checkout
	function pvkw_woocommerce_review_order_before_payment() {
		$shipping_methods = pvkw_get_shipping_methods();
		if ( count( $shipping_methods )>0 ) {
			?>
			<div id="pvkw" style="display: none;">

				<p class="form-row form-row-wide <?php if ( get_option( 'pvkw_checkout_default_empty' ) == 'yes' ) echo 'validate-required woocommerce-invalid'; ?>" id="pkvw_field">
					<label for="pvkw_point">
						<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/vaspkios_104_74.png' ); ?>" width="104" height="74" id="vaspkios_img"/>
						<?php _e( 'Select the VASP Expresso Kios point', 'portugal-vasp-kios-woocommerce' ); ?>
						<span class="pvkw-clear"></span>
					</label>
					<?php echo pvkw_points_fragment(); ?>
				</p>
				
				<div class="pvkw-clear"></div>

			</div>
			<?php
		}
	}

	//Add instructions to the checkout
	function pvkw_woocommerce_after_shipping_rate( $method, $index ) {
		$show = false;
		switch ( $method->get_method_id() ) {
			case 'flexible_shipping':
				$options = get_option( 'flexible_shipping_methods_'.$method->get_instance_id(), array() );
				foreach ( $options as $key => $fl_options ) {
					$show = isset( $fl_options['pvkw'] ) && ( intval( $fl_options['pvkw'] ) == 1 );
				}
				break;
			/*case 'advanced_shipping':
				break;*/
			case 'table_rate':
				$options = get_option( 'woocommerce_table_rate_'.$method->get_instance_id().'_settings', array() );
				$show =  isset( $options['pvkw'] ) && intval( $options['pvkw'] ) == 1;
				break;
			default:
				$options = get_option( 'woocommerce_'.$method->get_method_id().'_'.$method->get_instance_id().'_settings', array() );
				$show =  isset( $options['pvkw'] ) && intval( $options['pvkw'] ) == 1;
				break;
		}
		if ( $show ) {
			?>
			<div class="pvkw_shipping_method_instructions"><?php echo nl2br( trim( get_option( 'pvkw_instructions', '' ) ) ); ?></div>
			<?php
		}
	}

	//Fragment
	function pvkw_points_fragment() {
		$postcode = '';
		$country  = '';
		$nearby   = intval( get_option( 'pvkw_nearby_points', 10 ) );
		$total    = intval( get_option( 'pvkw_total_points', 50 ) );
		if ( isset( $_POST['s_postcode'] ) && trim( $_POST['s_postcode'] )!='' ) {
			$postcode = trim( sanitize_text_field( $_POST['s_postcode'] ) );
		} else {
			if ( isset( WC()->session ) ) {
				if ( $customer = WC()->session->get( 'customer' ) ) {
					$postcode = $customer['shipping_postcode'];
				}
			}
		}
		$postcode = wc_format_postcode( $postcode, 'PT' );
		if ( isset( $_POST['s_country'] ) && trim( $_POST['s_country'] ) != '' ) {
			$country = trim( sanitize_text_field( $_POST['s_country'] ) );
		} else {
			if ( isset( WC()->session ) ) {
				if ( $customer = WC()->session->get( 'customer' ) ) {
					$country = $customer['shipping_country'];
				}
			}
		}
		ob_start();
		?>
		<span class="pvkw-points-fragment">
			<?php
			if ( $country == 'PT' ) {
				$points = pvkw_get_pickup_points( $postcode );
				//Developers can choose not to show all $points
				$points = apply_filters( 'pvkw_available_points', $points, $postcode );
				if ( is_array( $points ) && count( $points )>0 ) {
					?>
					<select name="pvkw_point" id="pvkw_point">
						<?php if ( get_option( 'pvkw_checkout_default_empty' ) == 'yes' ) { ?>
							<option value="">- <?php _e( 'Select point', 'portugal-vasp-kios-woocommerce' ); ?> -</option>
						<?php } ?>
						<optgroup label="<?php _e( 'Near you', 'portugal-vasp-kios-woocommerce' ); ?>">
						<?php
						$i = 0;
						foreach( $points as $ponto ) {
							$i++;
							if ( $i == 1 ) {
								$first = $ponto;
							}
							if ( $i == $nearby + 1 ) {
							?>
						</optgroup>
						<optgroup label="<?php _e( 'Other spots', 'portugal-vasp-kios-woocommerce' ); ?>">
							<?php
							}
							?>
							<option value="<?php echo $ponto['number']; ?>">
								<?php echo $ponto['localidade']; ?>
								-
								<?php echo $ponto['nome']; ?>
							</option>
							<?php
							if ( $i == $total ) {
								break;
							}
						}
						?>
						</optgroup>
					</select>
					<input type="hidden" name="pvkw_point_active" id="pvkw_point_active" value="0"/>
					<?php
					pvkw_point_details( get_option( 'pvkw_checkout_default_empty' ) == 'yes' ? null : $first );
				} else {
					?>
					<p><strong><?php _e( 'ERROR: There are no VASP Expresso Kios points in the database. The update process has not yet ended successfully.', 'portugal-vasp-kios-woocommerce' ); ?></strong></p>
					<?php
				}
			}
			?>
		</span>
		<?php
		return ob_get_clean();
	}

	//Update select with points on each checkout update
	function pvkw_woocommerce_update_order_review_fragments( $fragments ) {
		$fragments['.pvkw-points-fragment'] = pvkw_points_fragment();
		return $fragments;
	}
	//Each point details
	function pvkw_point_details( $point ) {
		if ( $point ) {
			$mapbox_public_token = trim( get_option( 'pvkw_mapbox_public_token', '' ) );
			$google_api_key = trim( get_option( 'pvkw_google_api_key', '' ) );
			$map_width = intval( apply_filters( 'pvkw_map_width', 80 ) );
			$map_height = intval( apply_filters( 'pvkw_map_height', 80 ) );
			$img_html = '<!-- No map because neither Mapbox public token or Google Maps API Key are filled in -->';
			if ( trim( $mapbox_public_token ) != '' ) {
					$img_html = sprintf(
						'<img src="https://api.mapbox.com/styles/v1/mapbox/streets-v10/static/pin-s+FF0000(%s,%s)/%s,%s,%d,0,0/%dx%d%s?access_token=%s" width="%d" height="%d"/>',
						esc_attr( trim( $point['gps_lon'] ) ),
						esc_attr( trim( $point['gps_lat'] ) ),
						esc_attr( trim( $point['gps_lon'] ) ),
						esc_attr( trim( $point['gps_lat'] ) ),
						apply_filters( 'pvkw_map_zoom', 10 ),
						$map_width,
						$map_height,
						intval( apply_filters( 'pvkw_map_scale', 2 ) == 2 ) ? '@2x' : '',
						esc_attr( $mapbox_public_token ),
						$map_width,
						$map_height
					);
			} elseif ( trim( $google_api_key ) != '' ) {
					$img_html = sprintf(
						'<img src="https://maps.googleapis.com/maps/api/staticmap?center=%s,%s&amp;markers=%s,%s&amp;size=%dx%d&amp;scale=%d&amp;zoom=%d&amp;language=%s&amp;key=%s" width="%d" height="%d"/>',
						esc_attr( trim( $point['gps_lat'] ) ),
						esc_attr( trim( $point['gps_lon'] ) ),
						esc_attr( trim( $point['gps_lat'] ) ),
						esc_attr( trim( $point['gps_lon'] ) ),
						$map_width,
						$map_height,
						intval( apply_filters( 'pvkw_map_scale', 2 ) == 2 ) ? 2 : 1,
						apply_filters( 'pvkw_map_zoom', 11 ),
						esc_attr( get_locale() ),
						esc_attr( $google_api_key ),
						$map_width,
						$map_height
					);
			}
			?>
			<span class="pvkw-points-fragment-point-details">
				<span id="pvkw-points-fragment-point-details-address">
					<span id="pvkw-points-fragment-point-details-map">
						<a href="https://www.google.pt/maps?q=<?php echo esc_attr( trim( $point['gps_lat'] ) ); ?>,<?php echo esc_attr( trim( $point['gps_lon'] ) ); ?>" target="_blank">
							<?php echo $img_html; ?>
						</a>
					</span>
					<strong><?php echo $point['nome']; ?></strong>
					<br/>
					<?php echo $point['morada1']; ?>
					<br/>
					<?php echo $point['cod_postal']; ?>
					<?php echo $point['localidade']; ?>
					<?php if ( get_option( 'pvkw_display_phone', 'yes' ) == 'yes' || get_option( 'pvkw_display_schedule', 'yes' ) == 'yes' ) { ?>
						<div><small>
							<?php if ( get_option( 'pvkw_display_phone', 'yes' ) == 'yes' && trim( $point['telefone'] ) != '' ) { ?>
								<br/>
								<?php _e( 'Phone:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['telefone']; ?>
							<?php } ?>
							<?php if ( get_option( 'pvkw_display_schedule', 'yes' ) == 'yes' ) { ?>
								<?php if ( trim( $point['horario_semana'] ) != '' ) { ?>
									<br/>
									<?php _e( 'Work days:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['horario_semana']; ?>
								<?php } ?>
								<?php if ( trim( $point['horario_sabado'] ) != '' ) { ?>
									<br/>
									<?php _e( 'Saturday:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['horario_sabado']; ?>
								<?php } ?>
								<?php if ( trim( $point['horario_domingo'] ) != '' ) { ?>
									<br/>
									<?php _e( 'Sunday:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['horario_domingo']; ?>
								<?php } ?>
							<?php } ?>
						</small></div>
					<?php } ?>
				</span>
				<span class="pvkw-clear"></span>
			</span>
			<?php
		} else {
			?>
			<span class="pvkw-points-fragment-point-details">
				<!-- empty -->
				<span class="pvkw-clear"></span>
			</span>
			<?php
		}
	}
	//Each point details - AJAX
	function wc_ajax_pvkw_point_details() {
		$fragments = array();
		if ( isset( $_POST['pvkw_point'] ) ) {
			$pvkw_point = trim( sanitize_text_field( $_POST['pvkw_point'] ) );
			$points = pvkw_get_pickup_points();
			if ( isset( $points[$pvkw_point] ) ) {
				ob_start();
				pvkw_point_details( $points[$pvkw_point] );
				$fragments = array( 
					'.pvkw-points-fragment-point-details' => ob_get_clean(),
				);
			}
		}
		if ( count( $fragments ) == 0 ) {
			ob_start();
			pvkw_point_details( null );
			$fragments = array( 
				'.pvkw-points-fragment-point-details' => ob_get_clean(),
			);
		}
		wp_send_json( array( 
			'fragments' => $fragments
		) );
	}

	//Validate if point should be there and stop the checkout (if option true and active and empty field -> Error)
	function pvkw_woocommerce_after_checkout_validation( $fields, $errors ) {
		if ( get_option( 'pvkw_checkout_default_empty' ) == 'yes' ) {
			if ( isset( $_POST['pvkw_point'] ) && ( trim( $_POST['pvkw_point'] ) == '' ) && isset( $_POST['pvkw_point_active'] ) && ( intval( $_POST['pvkw_point_active'] ) == 1 ) ) {
				$errors->add(
					'pvkw_point_validation',
					__( 'You need to select a <strong>VASP Expresso Kios point</strong>.', 'portugal-vasp-kios-woocommerce' ),
					array( 'id' => 'pvkw_point' )
				);
			}
		}
	}

	//Save chosen point to the order
	function pvkw_save_extra_order_meta( $order_id ) {
		if ( isset( $_POST['pvkw_point'] ) && ( trim( $_POST['pvkw_point'] ) != '' ) && isset( $_POST['pvkw_point_active'] ) && ( intval( $_POST['pvkw_point_active'] ) == 1 ) ) {
			$pvkw_point = trim( sanitize_text_field( $_POST['pvkw_point'] ) );
			$order = new WC_Order( $order_id );
			$pvkw_shipping_methods = pvkw_get_shipping_methods();
			$order_shipping_method = $order->get_shipping_methods();
			$save = false;
			foreach( $order_shipping_method as $method ) {
				switch ( $method['method_id'] ) {
					case 'flexible_shipping':
						$options = get_option( 'flexible_shipping_methods_'.$method['instance_id'], array() );
						foreach ( $options as $key => $fl_options ) {
							if ( isset( $fl_options['pvkw'] ) && intval( $fl_options['pvkw'] ) == 1 && in_array( $method['method_id'].'_'.$method['instance_id'].'_'.$fl_options['id'], $pvkw_shipping_methods ) ) {
								$save = true;
							}
						}
						break;
					case 'advanced_shipping':
						//We'll trust on intval( $_POST['pvkw_point_active'] ) ==  1 because we got no way to identify which of the Advanced Shipping rules was used
						$save = true;
						break;
					case 'table_rate':
						$options = get_option( 'woocommerce_table_rate_'.$method['instance_id'].'_settings', array() );
						if ( isset( $options['pvkw'] ) && intval( $options['pvkw'] ) == 1 ) $save = true;
						break;
					case 'betrs_shipping':
						$options_instance = get_option( 'betrs_shipping_options-'.$method['instance_id'], array() );
						if ( isset( $options_instance['settings'] ) && is_array( $options_instance['settings'] ) ) {
							foreach ( $options_instance['settings'] as $setting ) {
								if ( isset( $setting['option_id'] ) && in_array( $method['method_id'].':'.$method['instance_id'].'-'.$setting['option_id'], $pvkw_shipping_methods ) ) {
									$save = true;
									break;
								}
							}
						}
						break;
					default:
						//Others
						if ( in_array( $method['method_id'], $pvkw_shipping_methods ) || in_array( $method['method_id'].':'.$method['instance_id'], $pvkw_shipping_methods ) ) {
							$save = true;
						}
						break;
				}
				break; //Only one shipping method supported
			}
			if ( $save ) {
				//Save order meta
				$order->update_meta_data( 'pvkw_point', $pvkw_point );
				$order->save();
			}
		}
	}


	//Show chosen point at the order screen
	function pvkw_woocommerce_admin_order_data_after_shipping_address( $order ) {
		$pvkw_point = $order->get_meta( 'pvkw_point' ) ;
		if ( trim( $pvkw_point )!='' ) {
			?>
			<h3><?php _e( 'VASP Expresso Kios point', 'portugal-vasp-kios-woocommerce' ); ?></h3>
				<p><strong><?php echo $pvkw_point; ?></strong></p>
				<?php
				$points = pvkw_get_pickup_points();
				if ( isset( $points[trim( $pvkw_point )] ) ) {
					$point = $points[trim( $pvkw_point )];
					pvkw_point_information( $point, false, true, true );
			} else {
				?>
				<p><?php _e( 'Unable to find point on the database', 'portugal-vasp-kios-woocommerce' ); ?></p>
				<?php
			}
		}
	}


	//Check if points are still not loaded on admin
	function pvkw_admin_notices() {
		global $pagenow;
		if ( $pagenow=='admin.php' && isset($_GET['page']) && trim($_GET['page'])=='wc-settings' ) {
			$points = pvkw_get_pickup_points();
			if ( count($points)==0 ) {
				if ( isset($_GET['pvkw_force_update']) ) {
					if ( pvkw_update_pickup_list_function() ) {
						?>
						<div class="notice notice-success">
							<p><?php _e( 'VASP Expresso Kios points updated.', 'portugal-vasp-kios-woocommerce' ); ?></p>
						</div>
						<?php
					} else {
						?>
						<div class="notice notice-error">
							<p><?php _e( 'It was not possible to update the VASP Expresso Kios points.', 'portugal-vasp-kios-woocommerce' ); ?></p>
						</div>
						<?php
					}
				} else {
					?>
					<div class="notice notice-error">
						<p><?php _e( 'ERROR: There are no VASP Expresso Kios points in the database. The update process has not yet ended successfully.', 'portugal-vasp-kios-woocommerce' ); ?></p>
						<p><a href="admin.php?page=wc-settings&amp;pvkw_force_update"><strong><?php _e( 'Click here to force the update process', 'portugal-vasp-kios-woocommerce' ); ?></strong></a></p>
					</div>
					<?php
				}
			}
		}
	}


	//Update points from VASP Kios webservice
	function pvkw_update_pickup_list_function() {
		$urls = array(
			'https://vaspapirest.vaspexpresso.pt/Kios/GetServiceDeliveryPointsWithFilters',
			//'https://vasp.webdados.pt/webservice_proxy.php', //Our backup (with cache) - 2023-01-17 - Makes no sense to keep this
		);
		shuffle( $urls ); //Random order
		$args = array( 
			'headers'	=> array( 
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Cache-Control' => 'no-cache',
			 ),
			'sslverify'	=> false,
			'timeout' => 25,
			'body'	=> json_encode( array( 'Country' => 'PT' ) ),
		);
		update_option( 'pvkw_points_last_update_try_datetime', date_i18n( 'Y-m-d H:i:s' ), false );
		$done = false;
		foreach ( $urls as $url ) {
			$response = wp_remote_post( $url, $args );
			if( ( !is_wp_error( $response ) ) && is_array( $response ) && $response['response']['code']=='200' ) {
				if ( $body = json_decode( $response['body'] ) ) {
					if ( isset( $body->response ) && is_array( $body->response ) && count( $body->response ) > 0 ) {
						$points = array();
						foreach( $body->response as $point ) {
							$points[trim( $point->serviceDeliveryPointCode )] = array( 
								'number'          => pvkw_fix_spot_text( $point->serviceDeliveryPointCode ),
								'nome'            => pvkw_fix_spot_text( trim( $point->businessName ) != '' ? $point->businessName : $point->name ),
								'morada1'         => pvkw_fix_spot_text( $point->address ),
								'cod_postal'      => pvkw_fill_postcode( $point->zipCode ),
								'localidade'      => pvkw_fix_spot_text( $point->city ),
								'gps_lat'         => pvkw_fix_spot_text( $point->latitude ),
								'gps_lon'         => pvkw_fix_spot_text( $point->longitude ),
								'telefone'        => pvkw_fix_spot_text( trim( $point->cellPhone ) != '' ? $point->cellPhone : ( trim( $point->phone1 ) != '' ? $point->phone1 : $point->phone2 ) ),
								'horario_semana'  => pvkw_fix_spot_text( str_replace( 'FECHADO', '', $point->weekSchedule ) ),
								'horario_sabado'  => pvkw_fix_spot_text( str_replace( 'FECHADO', '', $point->saturdaySchedule ) ),
								'horario_domingo' => pvkw_fix_spot_text( str_replace( 'FECHADO', '', $point->sundaySchedule ) ),
							);
						}
						update_option( 'pvkw_points', $points, false );
						update_option( 'pvkw_points_last_update_datetime', date_i18n( 'Y-m-d H:i:s' ), false );
						update_option( 'pvkw_points_last_update_server', $url, false );
						$done = true;
						return true;
						break;
					} else {
						if ( apply_filters( 'pvkw_update_pickup_list_error_log', false ) ) {
							error_log( '[Portugal VASP Expresso Kios network for WooCommerce] It was not possible to get the points update: no points array in response ('.$url.')' );
						}
					}
				} else {
					if ( apply_filters( 'pvkw_update_pickup_list_error_log', false ) ) {
						error_log( '[Portugal VASP Expresso Kios network for WooCommerce] It was not possible to get the points update: no body in response ('.$url.')' );
					}
				}
			} else {
				if ( apply_filters( 'pvkw_update_pickup_list_error_log', false ) ) {
					error_log( '[Portugal VASP Expresso Kios network for WooCommerce] It was not possible to get the points update via webservice: ('.$url.') '.(  is_wp_error( $response ) ? print_r( $response, true ) : 'unknown error' ) );
				}
			}
		}
		if ( $done ) {
			//NICE!
		} else {
			//FTP fallback
			//Doesn't exist
			return false;
		}
	}
	//Fix text
	function pvkw_fix_spot_text( $string ) {
		$string = strtolower( $string );
		$string = ucwords( $string );
		$org = array( 'Ç', ' Da ', ' De ', ' Do ', 'Ii', ' E ' );
		$rep = array( 'ç', ' da ', ' de ', ' do ', 'II', ' e ' );
		$string = str_ireplace( $org, $rep, $string );
		return trim( $string );
	}
	//Fix postcode
	function pvkw_fill_postcode( $cp ) {
		$cp = trim( $cp );
		//Até 4
		if ( strlen( $cp )<4 ) {
			$cp=str_pad( $cp,4,'0' );
		}
		if ( strlen( $cp )==4 ) {
			$cp.='-';
		}
		if ( strlen( $cp )<8 ) {
			$cp=str_pad( $cp,8,'0' );
		}
		return trim( $cp );
	}


	//Daily cron to update points list
	function pvkw_cronstarter_activation() {
		if( ! wp_next_scheduled( 'pvkw_update_pickup_list' ) ) {
			//Schedule
			wp_schedule_event( time(), 'daily', 'pvkw_update_pickup_list' );
			//And run now - just in case
			do_action( 'pvkw_update_pickup_list' );
		}
	}
	//Deactivate cron on plugin deactivation
	function pvkw_cronstarter_deactivate() {	
		// find out when the last event was scheduled
		$timestamp = wp_next_scheduled( 'pvkw_update_pickup_list' );
		// unschedule previous event if any
		wp_unschedule_event( $timestamp, 'pvkw_update_pickup_list' );
	}


	//Get all points from the database
	function pvkw_get_pickup_points( $postcode = '' ) {
		$points = get_option( 'pvkw_points', array() );
		if ( is_array( $points ) && count( $points ) > 0 ) {
			//SORT by postcode ?
			if ( $postcode!='' ) {
				$postcode = pvkw_fill_postcode( $postcode );
				$postcode = intval( str_replace( '-', '', $postcode ) );
				$points_sorted = array();
				$cp_order = array();
				//Sort by post code mathematically
				foreach( $points as $key => $ponto ) {
						$diff=abs( $postcode-intval( str_replace( '-', '', $ponto['cod_postal'] ) ) );
						$points_sorted[$key]=$ponto;
						$points_sorted[$key]['cp_order']=$diff;
						$cp_order[$key]=$diff;
				}
				array_multisort( $cp_order, SORT_ASC, $points_sorted );
				//Now by GPS distance
				$pontos2=array();
				$distancia=array();
				foreach( $points_sorted as $ponto ) {
					$gps_lat = $ponto['gps_lat'];
					$gps_lon = $ponto['gps_lon'];
					break;
				}
				foreach( $points_sorted as $key => $ponto ) {
					$points_sorted[$key]['distancia'] = pvkw_gps_distance( $gps_lat, $gps_lon, $ponto['gps_lat'], $ponto['gps_lon'] );
					$distancia[$key]['distancia'] = pvkw_gps_distance( $gps_lat, $gps_lon, $ponto['gps_lat'], $ponto['gps_lon'] );
				}
				array_multisort( $distancia, SORT_ASC, $points_sorted );
				return $points_sorted;
			} else {
				return $points;
			}
		} else {
			return array();
		}
	}
	//Points distance by GPS
	function pvkw_gps_distance( $lat1, $lon1, $lat2, $lon2 ) {
		$lat1 = floatval($lat1);
		$lon1 = floatval($lon1);
		$lat2 = floatval($lat2);
		$lon2 = floatval($lon2);
		$theta = $lon1 - $lon2;
		$dist = sin( deg2rad( $lat1 ) ) * sin( deg2rad( $lat2 ) ) +  cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * cos( deg2rad( $theta ) );
		$dist = acos( $dist );
		$dist = rad2deg( $dist );
		$miles = $dist * 60 * 1.1515;
		return ( $miles * 1.609344 ); //Km
	}


	//Plugin settings
	function pvkw_woocommerce_shipping_settings( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'shipping_options' == $section['id'] && isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
				$updated_settings[] = array( 
					'title'		=> __( 'VASP Expresso Kios network in Portugal', 'portugal-vasp-kios-woocommerce' ),
					'desc'		=> __( 'Total of points to show', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_total_points',
					'default'	=> 50,
					'type'		=> 'number',
					'autoload'	=> false,
					'css'		=> 'width: 60px;',
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Near by points to show', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_nearby_points',
					'default'	=> 10,
					'type'		=> 'number',
					'autoload'	=> false,
					'css'		=> 'width: 60px;',
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Do not pre-select a point in the VASP Expresso Kios field and force the client to choose it', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_checkout_default_empty',
					'default'	=> 0,
					'type'		=> 'checkbox',
					'autoload'	=> false,
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Instructions for clients', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_instructions',
					'default'	=> __( 'Pick up your order in one of the more than 600 VASP Expresso Kios points available in Portugal mainland', 'portugal-vasp-kios-woocommerce' ),
					'type'		=> 'textarea',
					'autoload'	=> false,
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Mapbox Public Token (recommended)', 'portugal-vasp-kios-woocommerce' ).' (<a href="https://www.mapbox.com/account/access-tokens" target="_blank">'.__( 'Get one', 'portugal-vasp-kios-woocommerce' ).'</a>)',
					'desc_tip'	=> __( 'Go to your Mapbox account and get a Public Token, if you want to use this service static maps instead of Google Maps', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_mapbox_public_token',
					'default'	=> '',
					'type'		=> 'text',
					'autoload'	=> false,
					'css'		=> 'min-width: 350px;',
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Google Maps API Key', 'portugal-vasp-kios-woocommerce' ).' (<a href="https://developers.google.com/maps/documentation/maps-static/get-api-key" target="_blank">'.__( 'Get one', 'portugal-vasp-kios-woocommerce' ).'</a>)',
					'desc_tip'	=> __( 'Go to the Google APIs Console and create a project, then go to the Static Maps API documentation website and click on Get a key, choose your project and generate a new key (if the Mapbox public token is filled in, this will be ignored and can be left blank)', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_google_api_key',
					'default'	=> '',
					'type'		=> 'text',
					'autoload'	=> false,
					'css'		=> 'min-width: 350px;',
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Display the VASP Expresso Kios point information on emails sent to the customer and order details on the "My Account" page', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_email_info',
					'default'	=> 1,
					'type'		=> 'checkbox',
					'autoload'	=> false,
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Hide shipping address on order details and emails sent to the customer', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_hide_shipping_address',
					'default'	=> 1,
					'type'		=> 'checkbox',
					'autoload'	=> false,
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Display the VASP Expresso Kios point phone number (if available) on the checkout', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_display_phone',
					'default'	=> 1,
					'type'		=> 'checkbox',
					'autoload'	=> false,
				);
				$updated_settings[] = array( 
					'desc'		=> __( 'Display the VASP Expresso Kios point opening/closing hours (if available) on the checkout', 'portugal-vasp-kios-woocommerce' ),
					'id'		=> 'pvkw_display_schedule',
					'default'	=> 1,
					'type'		=> 'checkbox',
					'autoload'	=> false,
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	//Information basics
	function pvkw_point_information( $point, $plain_text = false, $echo = true, $order_screen = false ) {
		ob_start();
		?>
		<p>
			<?php echo $point['nome']; ?>
			<br/>
			<?php echo $point['morada1']; ?>
			<br/>
			<?php echo $point['cod_postal']; ?> <?php echo $point['localidade']; ?>
			<?php if ( get_option( 'pvkw_display_phone', 'yes' ) == 'yes' || get_option( 'pvkw_display_schedule', 'yes' ) == 'yes' ) { ?>
				<small>
					<?php if ( get_option( 'pvkw_display_phone', 'yes' ) == 'yes' && trim( $point['telefone'] ) != '' ) { ?>
						<br/>
						<?php _e( 'Phone:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['telefone']; ?>
					<?php } ?>
					<?php if ( get_option( 'pvkw_display_schedule', 'yes' ) == 'yes' ) { ?>
						<?php if ( trim( $point['horario_semana'] ) != '' ) { ?>
							<br/>
							<?php _e( 'Work days:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['horario_semana']; ?>
						<?php } ?>
						<?php if ( trim( $point['horario_sabado'] ) != '' ) { ?>
							<br/>
							<?php _e( 'Saturday:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['horario_sabado']; ?>
						<?php } ?>
						<?php if ( trim( $point['horario_domingo'] ) != '' ) { ?>
							<br/>
							<?php _e( 'Sunday:', 'portugal-vasp-kios-woocommerce' ); ?> <?php echo $point['horario_domingo']; ?>
						<?php } ?>
					<?php } ?>
				</small>
			<?php } ?>
		</p>
		<?php
		$html = ob_get_clean();
		if ( $plain_text ) {
			$html = strip_tags( str_replace( "\t", '', $html ) ) . "\n";
			$html = "\n" . strtoupper( __( 'VASP Expresso Kios point', 'portugal-vasp-kios-woocommerce' ) ) . "\n" . $point['number'] . "\n" . $html;
		} else {
			if ( ! $order_screen ) {
				$html = '<h2>'.__( 'VASP Expresso Kios point', 'portugal-vasp-kios-woocommerce' ).'</h2><p><strong>'.$point['number'].'</strong></p>' . $html;
			}
		}
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	//Information on emails
	function pvkw_woocommerce_email_customer_details( $order, $sent_to_admin = false, $plain_text = false ) {
		$pvkw_point = $order->get_meta( 'pvkw_point' ) ;
		if ( trim( $pvkw_point ) != '' ) {
			$points = pvkw_get_pickup_points();
			if ( isset( $points[trim( $pvkw_point )] ) ) {
				$point = $points[trim( $pvkw_point )];
				pvkw_point_information( $point, $plain_text );
			}
		}
	}
	//Information on the order details
	function pvkw_woocommerce_order_details_after_order_table( $order ) {
		$pvkw_point = $order->get_meta( 'pvkw_point' ) ;
		if ( trim( $pvkw_point )!='' ) {
			$points = pvkw_get_pickup_points();
			if ( isset( $points[trim( $pvkw_point )] ) ) {
				$point = $points[trim( $pvkw_point )];
				?>
				<section>
					<?php pvkw_point_information( $point ); ?>
				</section>
				<?php
			}
		}
	}
	//Information on the admin order preview
	function pvkw_woocommerce_admin_order_preview_end() {
		?>
		{{{ data.pvkw_info }}}
		<?php
	}
	function pvkw_woocommerce_admin_order_preview_get_order_details( $data, $order ) {
		$data['pvkw_info'] = '';
		$pvkw_point = $order->get_meta( 'pvkw_point' ) ;
		if ( trim( $pvkw_point ) != '' ) {
			$points = pvkw_get_pickup_points();
			if ( isset( $points[trim( $pvkw_point )] ) ) {
				$point = $points[trim( $pvkw_point )];
				ob_start();
				?>
				<div class="wc-order-preview-addresses">
					<div class="wc-order-preview-note">
						<?php pvkw_point_information( $point ); ?>
					</div>
				</div>
				<?php
				$data['pvkw_info'] = ob_get_clean();
			}
		}
		return $data;
	}

	//Hide shipping address
	function pvkw_woocommerce_order_needs_shipping_address( $needs_address, $hide, $order ) {
		$pvkw_point = $order->get_meta( 'pvkw_point' ) ;
		if ( trim( $pvkw_point ) != '' ) {
			$needs_address = false;
		}
		return $needs_address;
	}

}

/* HPOS Compatible */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


/* If you’re reading this you must know what you’re doing ;- ) Greetings from sunny Portugal! */


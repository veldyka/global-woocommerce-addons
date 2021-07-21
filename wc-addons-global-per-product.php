<?php
/*
 * Plugin Name: Global WooCommerce Add-ons per Product
 * Plugin URI: 
 * Description: Доработка для комплектации товаров
 * Version: 1.0.0
 * Author: Vel Wild
 * Author URI: https://vel-wild.pro
 * Requires at least: 1.0
 * WC requires at least: 3.8.0    
 * Tested up to: 5.7.0
 * WC tested up to: 5.0   
 *
 * Copyright: © 2019 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Usage Tip: Create a dummy product category. Assign global add-ons to that category, this way they will only appear when selected for individual products.
 *
 */




/**
 * Add a custom field to the Add-ons meta panel
 */
function kia_add_custom_addons_fields() {

	global $product_object;

	if( ! $product_object instanceof WC_Product )  {
		return;
	}

	$global_addons = WC_Product_Addons_Groups::get_all_global_groups();

	?>
	<div class="wc-pao-field-header">
		<p><strong><?php esc_html_e( 'Комплектация', 'your-text-domain' ); ?><?php echo wc_help_tip( __( 'Выбрать комплектацию', 'your-text-domain' ) ); ?></strong></p>
	</div>

	<div class="wc-pao-global-addons wc-pao-addons wc-pao-has-addons">

		<p class="form-field">

			<label for="global_addons"><?php _e( 'Выбрать комплектацию', 'your-text-domain' ); ?></label>

			<?php

			// Generate some data for the select2 input.
			$product_add_ons = array_filter( (array) $product_object->get_meta( '_global_addons' ) );

			?>

			<select id="global_addons" class="wc-enhanced-select" name="global_addons[]" multiple="multiple" style="width: 400px;" data-sortable="sortable" data-placeholder="<?php esc_attr_e( 'Search for a Global Add-on&hellip;', 'your-text-domain' ); ?>" >
			<?php
				foreach ( $global_addons as $add_on ) {
					echo '<option value="' . esc_attr( $add_on['id'] ) . '"' . selected( in_array( $add_on['id'], $product_add_ons ), true, false ) . '>' . wp_kses_post( $add_on['name'] ) . '</option>';
					
				}
			?>
			</select>

		</p>

	</div>
<?php
}
add_action( 'woocommerce_product_addons_panel_start', 'kia_add_custom_addons_fields' );


/**
 * Save the custom field data.
 *
 * @param obj $product WC_Product - the product object.
 */
function kia_save_custom_addons_fields( $product ) {

	// Layout.
	if ( ! empty( $_POST[ 'global_addons' ] ) ) {

		$meta = array_map( 'intval', (array) $_POST[ 'global_addons' ] );

		$product->add_meta_data( '_global_addons', $meta, true );
	}

}
add_action( 'woocommerce_admin_process_product_object', 'kia_save_custom_addons_fields' );


/**
 * Force the custom add-on into the product display.
 *
 * @param  array $product_addons
 * @param  int $post_id
 * @param  return array 
 */
function kia_add_global_product_addons( $product_addons, $post_id ) {

	global $product;

	if( ! is_admin() && $product instanceof WC_Product && $post_id === $product->get_id() ) {

		$meta = $product->get_meta( '_global_addons', true );

		if( ! empty( $meta ) ) {

			$args = array(
				'posts_per_page'   => -1,
				'post_type'        => 'global_product_addon',
				'post_status'      => 'publish',
				'suppress_filters' => true,
				'include' => $meta
			);

			$global_addons = get_posts( $args );

			if ( $global_addons ) {
				$new_addons = array();
				foreach ( $global_addons as $global_addon ) {
					            
					$new_addon = apply_filters( 'get_product_addons_fields', array_filter( (array) get_post_meta( $global_addon->ID, '_product_addons', true ) ), $global_addon->ID );
					$new_addons = $new_addons + $new_addon;
				}

				$product_addons = array_merge( $new_addons, $product_addons );

			}

		}
	}

	return $product_addons;
}
add_filter( 'get_product_addons_fields', 'kia_add_global_product_addons', 10, 3 );

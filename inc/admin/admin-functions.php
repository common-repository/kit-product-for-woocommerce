<?php
/**
 * Surfer
 */

/**
 * Show pricing fields for kit_product product.
 */
function wckp_product_custom_js() {

	if ( 'product' != get_post_type() ) :
		return;
	endif;

	?>
	<script type='text/javascript'>
		jQuery(document).ready(function () {
			jQuery('.options_group.pricing').addClass('show_if_kit_product').show();
			jQuery('._kit_pricing_field .wc-radios li:last-child input').prop('disabled', true);

			jQuery('#kit_options ._wckp_cat_ids_field').hide();
			jQuery('#kit_options ._wckp_product_ids_field').hide();

			if (jQuery('#kit_options input[name="_kit_from_cat_product"]:checked').val() === 'defined_category') {
				jQuery('#kit_options ._wckp_cat_ids_field').show();
			}
			if (jQuery('#kit_options input[name="_kit_from_cat_product"]:checked').val() === 'defined_product') {
				jQuery('#kit_options ._wckp_product_ids_field').show();
			}

			jQuery('#kit_options input[name="_kit_from_cat_product"]').on('click', function () {

				if (jQuery(this).val() == 'defined_category') {
					jQuery('#kit_options ._wckp_cat_ids_field').show();
					jQuery('#kit_options ._wckp_product_ids_field').hide();
				}
				if (jQuery(this).val() == 'defined_product') {
					jQuery('#kit_options ._wckp_cat_ids_field').hide();
					jQuery('#kit_options ._wckp_product_ids_field').show();
				}
			});


		});

	</script><?php

}

add_action( 'admin_footer', 'wckp_product_custom_js' );

add_action( 'woocommerce_product_options_general_product_data', 'wckp_kit_product_type_show_price' );
function wckp_kit_product_type_show_price() {
	global $product_object;
	if ( $product_object && 'kit_product' === $product_object->get_type() ) {
		wc_enqueue_js( "
         $('.product_data_tabs .general_tab').addClass('show_if_kit_product').show();
         $('.pricing').addClass('show_if_kit_product').show();
      " );
	}
}

/**
 * Add a custom product tab.
 */
function wckp_product_tabs( $tabs ) {

	$tabs['kit_options'] = array(
		'label'  => __( 'Kit Options', 'woocommerce' ),
		'target' => 'kit_options',
		'class'  => array( 'show_if_kit_product', 'show_if_variable_rental' ),
	);

	return $tabs;

}

add_filter( 'woocommerce_product_data_tabs', 'wckp_product_tabs' );

add_filter( 'woocommerce_form_field_kit_multiselect', 'wckp_multiselect_handler', 10, 4 );

function wckp_multiselect_handler( $field, $key, $args, $value ) {

	$options = '';

	if ( ! empty( $args['options'] ) ) {
		foreach ( $args['options'] as $option_key => $option_text ) {
			$options .= '<option value="' . $option_key . '" ' . selected( $value, $option_key, false ) . '>' . $option_text . '</option>';
		}

		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
		} else {
			$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
		}

		$field = '<p class="form-row ' . implode( ' ', $args['class'] ) . '" id="' . $key . '_field">
            <label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) . '">' . $args['label'] . $required . '</label>
            <select name="' . $key . '" id="' . $key . '" class="select" multiple="multiple">
                ' . $options . '
            </select>
        </p>' . $args['after'];
	}

	return $field;
}

function wckp_kit_multiselect_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = ! empty( $field['value'] ) ? $field['value'] : array();
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['type']          = isset( $field['type'] ) ? $field['type'] : '';


	if ( $field['type'] == 'product' ) {
		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="wc-product-search ' . esc_attr( $field['class'] ) . '" multiple="multiple" style="width: 50%;" data-maximum-selection-length="10" data-placeholder="' . esc_attr( $field['placeholder'] ) . '" data-exclude="<?php echo $thepostid; ?>" >';
		foreach ( $field['value'] as $key => $value ) {
			$product = wc_get_product( $value );
			if ( is_object( $product ) ) {
				echo '<option value="' . esc_attr( $value ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
			}
		}
		echo '</select> ';
	}
	if ( $field['type'] == 'category' ) {
		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="wc-category-search ' . esc_attr( $field['class'] ) . '" multiple="multiple" style="width: 50%;" data-maximum-selection-length="10" data-placeholder="' . esc_attr( $field['placeholder'] ) . '" data-exclude="<?php echo $thepostid; ?>" >';
		foreach ( $field['value'] as $key => $value ) {
			$term = get_term_by( 'slug', $value, 'product_cat' );
			if ( is_object( $term ) ) {
				echo '<option value="' . esc_attr( $value ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $term->name ) ) . '</option>';
			}
		}
		echo '</select> ';
	}


	if ( ! empty( $field['description'] ) ) {
		if ( isset( $field['desc_tip'] ) && false !== $field['desc_tip'] ) {
			echo '<span class="woocommerce-help-tip" data-tip="' . esc_attr( $field['description'] ) . '"></span>';
		} else {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}
	}

	echo '</p>';
}

/**
 * Contents of the kit options product tab.
 */
function kit_product_tab_content() {

	global $post;

	?>
	<div id='kit_options' class='panel woocommerce_options_panel'><?php

	?>
	<div class='options_group'><?php

		$kit_pricing = get_post_meta( $post->ID, '_kit_pricing', true );
		//var_dump($from_cat_product);


		woocommerce_wp_radio( array(
			'id'          => '_kit_pricing',
			'label'       => __( 'Kit Pricing:', 'kit-product-for-woocommerce' ),
			'value'       => $kit_pricing,
			'options'     => array(
				'regular' => __( 'Regular', 'kit-product-for-woocommerce' ),
				'custom'  => __( 'Custom (Pro feature)', 'kit-product-for-woocommerce' )
			),
			'desc_tip'    => 'true',
			'description' => __( 'Select option for kit pricing. "Regular" pricing take basic price set in kit product.<br>"Custom" pricing will price kit based on products added to kit by customer.', 'kit-product-for-woocommerce' ),
		) );


		$show_customer_saved_price = get_post_meta( $post->ID, '_show_customer_saved_price', true );
		woocommerce_wp_checkbox( array(
			'id'            => '_show_customer_saved_price',
			'label'         => __( 'Show "You have saved" price.', 'kit-product-for-woocommerce' ),
			'desc_tip'      => true,
			'value'         => $show_customer_saved_price,
			//'cbvalue'       => $show_customer_saved_price,
			'description'   => __( 'Show the price saved with buying kit".', 'kit-product-for-woocommerce' ),
			'wrapper_class' => 'hide_if_variable'
		) );


		$from_cat_product = get_post_meta( $post->ID, '_kit_from_cat_product', true );
		//var_dump($from_cat_product);
		woocommerce_wp_radio( array(
			'id'          => '_kit_from_cat_product',
			'label'       => __( 'Display Products From:', 'kit-product-for-woocommerce' ),
			'value'       => $from_cat_product,
			'options'     => array(
				'defined_category' => __( 'Categories', 'kit-product-for-woocommerce' ),
				'defined_product'  => __( 'Products', 'kit-product-for-woocommerce' )
			),
			'desc_tip'    => 'true',
			'description' => __( 'Select option to display products from defined categories or specific products.', 'kit-product-for-woocommerce' ),
		) );


		// Get data
		$cat_data     = get_post_meta( $post->ID, '_wckp_cat_ids', true );
		$product_data = get_post_meta( $post->ID, '_wckp_product_ids', true );

		// Add field via custom function
		wckp_kit_multiselect_field(
			array(
				'id'          => '_wckp_cat_ids',
				'type'        => 'category',
				'label'       => __( 'Categories', 'kit-product-for-woocommerce' ),
				'placeholder' => __( 'Select Categories', 'kit-product-for-woocommerce' ),
				'class'       => '',
				'name'        => '_wckp_cat_ids[]',
				'value'       => $cat_data,
				'desc_tip'    => true,
				'description' => __( '', 'kit-product-for-woocommerce' ),
			)
		);

		wckp_kit_multiselect_field(
			array(
				'id'          => '_wckp_product_ids',
				'type'        => 'product',
				'label'       => __( 'Products', 'kit-product-for-woocommerce' ),
				'placeholder' => __( 'Select Products', 'kit-product-for-woocommerce' ),
				'class'       => '',
				'name'        => '_wckp_product_ids[]',
				'value'       => $product_data,
				'desc_tip'    => true,
				'description' => __( '', 'kit-product-for-woocommerce' ),
			)
		);

		$min_kit_quantity = get_post_meta( $post->ID, '_min_kit_quantity', true );
		woocommerce_wp_text_input( array(
			'id'            => '_min_kit_quantity',
			'label'         => __('Minimum kit quantity', 'kit-product-for-woocommerce'),
			'placeholder'   => __('1', 'kit-product-for-woocommerce'),
			'description'   => __('Set minimum number of products required to choose to purchase the kit.', 'kit-product-for-woocommerce'),
			'desc_tip'      => 'true',
			'value'         => $min_kit_quantity
		));

		$max_kit_quantity = get_post_meta( $post->ID, '_max_kit_quantity', true );
		woocommerce_wp_text_input( array(
			'id'            => '_max_kit_quantity',
			'label'         => __('Maximum kit quantity', 'kit-product-for-woocommerce'),
			'placeholder'   => __('All', 'kit-product-for-woocommerce'),
			'description'   => __('Set maximum number of products can be selected in the kit.', 'kit-product-for-woocommerce'),
			'desc_tip'      => 'true',
			'value'         => $max_kit_quantity
		));

		do_action( 'kit_product_add_settings' );
		?></div>

	</div><?php


}

add_action( 'woocommerce_product_data_panels', 'kit_product_tab_content' );


/**
 * Save the custom fields.
 */
function wckp_save_options_field( $post_id ) {

	$kit_pricing = sanitize_text_field( $_POST['_kit_pricing'] );
	if ( $kit_pricing ) {
		update_post_meta( $post_id, '_kit_pricing', $kit_pricing );
	}

	$show_customer_saved_price = sanitize_text_field( $_POST['_show_customer_saved_price'] );
	//var_dump($_POST['_show_customer_saved_price']);die();
	update_post_meta( $post_id, '_show_customer_saved_price', $show_customer_saved_price );

	$kit_from_cat_product = sanitize_text_field( $_POST['_kit_from_cat_product'] );
	if ( $kit_from_cat_product ) {
		update_post_meta( $post_id, '_kit_from_cat_product', $kit_from_cat_product );
	}

	//$cat_data = isset( $_POST['_wckp_cat_ids'] ) ? (array) $_POST['_wckp_cat_ids'] : array();
	$cat_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['_wckp_cat_ids'] ) );
	if ( ! empty( $cat_data ) ) {
		update_post_meta( $post_id, '_wckp_cat_ids', $cat_data );
	}

	//$product_data = isset( $_POST['_wckp_product_ids'] ) ? (array) $_POST['_wckp_product_ids'] : array();
	$product_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['_wckp_product_ids'] ) );
	if ( ! empty( $product_data ) ) {
		update_post_meta( $post_id, '_wckp_product_ids', $product_data );
	}

	$_min_kit_quantity = sanitize_text_field( $_POST['_min_kit_quantity'] );
	if ( $_min_kit_quantity ) {
		update_post_meta( $post_id, '_min_kit_quantity', $_min_kit_quantity );
	}

	$_max_kit_quantity = sanitize_text_field( $_POST['_max_kit_quantity'] );
	if ( $_max_kit_quantity ) {
		update_post_meta( $post_id, '_max_kit_quantity', $_max_kit_quantity );
	}




}

add_action( 'woocommerce_process_product_meta_kit_product', 'wckp_save_options_field' );
add_action( 'woocommerce_process_product_meta_variable_kit_product', 'wckp_save_options_field' );


/**
 * Hide Attributes data panel.
 */
function wckp_hide_attributes_data_panel( $tabs ) {

	$tabs['attribute']['class'][] = 'hide_if_kit_product hide_if_variable_kit_product';

	return $tabs;

}

add_filter( 'woocommerce_product_data_tabs', 'wckp_hide_attributes_data_panel' );

function wckp_get_products() {
	$argsA    = array(
		//'category' => array( 'tshirts','accessories' ),
		'status' => 'published',
		'type'   => 'simple',
		'limit'  => - 1,
		'return' => 'ids,name'
	);
	$products = wc_get_products( $argsA );
	if ( $products ) {
		return $products;
	}

	return false;
	//$this->pr($products);
}

function wckp_get_categories() {
	$args    = array(
		'hide_empty' => true,
	);
	$terms   = get_terms( 'product_cat', $args );
	$termArr = array();
	if ( $terms ) {
		foreach ( $terms as $term ) {
			//woocommerce_subcategory_thumbnail( $term );
			$termArr[ $term->term_id ] = $term->name;
		}
	}

	return $termArr;
}

add_action( 'admin_head', 'wckp_admin_style' );

function wckp_admin_style() {
	echo '<style>
    body, td, textarea, input, select {
      font-family: "Lucida Grande";
      font-size: 12px;
    } 
    #kit_options ul.wc-radios li {
        display: inline-block !important;
        padding-right: 10px !important;
    }
  </style>';
}
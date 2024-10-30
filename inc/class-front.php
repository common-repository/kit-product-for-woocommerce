<?php
if ( ! class_exists( 'WC_Kit_Product' ) ) {
	class WC_Kit_Product {

		public $wc_kit_product;

		public function __construct() {

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );
			add_action( 'woocommerce_before_kit_product_add_to_cart_button', array( $this, 'kit_product_html' ), 10 );
			add_action( 'woocommerce_kit_product_add_to_cart', array( $this, 'buy_kit_button' ), 10 );

			add_filter( 'woocommerce_get_price_html', array($this, 'kit_saved_price_html'), 11, 2 );
		}

		public function enqueue_scripts() {
			global $post;

			wp_enqueue_style( 'wckp-css', WCKP_PLUGIN_URL . 'assets/css/style.css' );
			if ( is_product() ) {
				$product              = wc_get_product( $post->ID );
				$this->wc_kit_product = $product;
				$products             = $this->get_products( $product );
				//$product_count        = count( $products );

				$min_products = get_post_meta($product->get_id(), '_min_kit_quantity', true) ? get_post_meta($product->get_id(), '_min_kit_quantity', true) : 1;
				$max_products = get_post_meta($product->get_id(), '_max_kit_quantity', true) ? get_post_meta($product->get_id(), '_max_kit_quantity', true) : 99999;

				wp_enqueue_script( 'wckp-js', WCKP_PLUGIN_URL . 'assets/js/functions.js', array( 'jquery' ), '', true );
				wp_localize_script( 'wckp-js', 'wckp_vars', array(
					'ajaxurl'      => admin_url( 'admin-ajax.php' ),
					'homeurl'      => home_url( '/' ),
					'min_products' => $min_products,
					'max_products' => $max_products,
					'currency'     => get_woocommerce_currency_symbol(),
				) );

				wp_enqueue_style( 'select2' );
				wp_enqueue_script( 'selectWoo' );
			}
		}

		public function buy_kit_button() {
			add_filter( 'wc_get_template', array( $this, 'wckp_woo_template_path' ), 10, 5 );
			wc_get_template( 'add-to-cart/buy-kit-product.php', '', WCKP_PLUGIN_DIR . 'inc/' );
			remove_filter( 'wc_get_template', array( $this, 'wckp_woo_template_path' ), 10, 5 );
		}

		public function wckp_woo_template_path( $template, $template_name, $args, $template_path, $default_path ) {
			global $product;

			if ( $product->get_type() == 'kit_product' && is_single() && $template_name == 'add-to-cart/buy-kit-product.php' ) {
				$template = WCKP_PLUGIN_DIR . 'inc/add-to-cart/buy-kit-product.php';
			}

			return $template;
		}

		public function kit_product_html() {
			global $product;
			//lf_wckp($product);
			if ( ! $this->wc_kit_product ) {
				$this->wc_kit_product = $product;
			}
			$product_id = $this->wc_kit_product->get_id();
			$_kit_pricing = get_post_meta( $product_id, '_kit_pricing', true );
			?>

			<div class="kit_<?php esc_attr_e($_kit_pricing);?>_pricing" id="kit_products_wrapper">

				<?php
				ob_start();

				if ( wckp_get_kit_edit_params( 'edit-kit-products' ) ) {
					$edit_kit_products = explode( ',', wckp_get_kit_edit_params( 'edit-kit-products' ) );
					$kit_edit_key      = wckp_get_kit_edit_params( 'kit-edit-key' );
					$field_count       = 0; ?>
					<input type="hidden" name="edit-kit-product"
					       value="<?php esc_attr_e( $this->wc_kit_product->get_id() ); ?>" readonly="">
					<input type="hidden" name="kit-edit-key" value="<?php esc_attr_e( $kit_edit_key ); ?>" readonly="">
					<?php foreach ( $edit_kit_products as $ekp ) { ?>
						<div id="item_<?php esc_attr_e( $field_count ); ?>" class="kit_product_item">
							<div
								class="kit_product_dd_wrapper"><?php esc_html_e( $this->product_ddlist( '', $ekp ) ); ?></div>
							<input type="hidden" class="form-control quantity input_value" name="qty[]"
							       value="<?php esc_attr_e( $this->get_kit_item_quantity( $product_id, $ekp ) ); ?>"
							       data-formato="0" readonly="">

							<?php if ( $field_count != 0 ) { ?>
								<div class="kit_product_sub"><i class="fas fa-minus-circles wckp_kit_sub_selezione"></i>
								</div>
							<?php } else { ?>
								<div class="kit_product_sub"></div>
							<?php } ?>
						</div>
						<?php $field_count ++;
					}
				} else { ?>
					<div id="item_0" class="kit_product_item">
						<div class="kit_product_dd_wrapper"><?php esc_html_e( $this->product_ddlist() ); ?></div>
						<input type="hidden" class="form-control quantity input_value" name="qty[]" value="1"
						       data-formato="0" readonly="">
						<div class="kit_product_sub"></div>
					</div>
				<?php } ?>

				<div id="wckp-kit-selezione" class="hidden kit_product_item">
					<div class="kit_product_dd_wrapper">
						<?php esc_html_e( $this->product_ddlist() ); ?>
					</div>
					<input type="hidden" class="form-control quantity input_value" name="qty[]" value="1"
					       data-formato="0" readonly="">
					<div class="kit_product_sub"><i class="fas fa-minus-circles wckp_kit_sub_selezione"></i></div>
				</div>
				<div id="wckp-add-field">
					<button type="button" class="button wckp_kit_add_selezione">
						<span><?php esc_html_e( 'Add Product', 'kit-product-for-woocommerce' ); ?></span><i
							class="fas fa-plus-circles kp-icon-plus"></i></button>
				</div>
				<div class="kit-price"></div>
				<?php
				$kit_html = ob_get_contents();
				ob_end_clean();

				/**
				 *
				 */
				echo apply_filters('kit_product_html', $kit_html, $_kit_pricing, $product_id);
				?>
			</div>
		<?php }

		protected function get_products( $product ) {
			//global $product;
			$argsA          = array(
				'status' => 'published',
				'type'   => 'simple',
				'limit'  => - 1,
				'return' => 'ids'
			);
			$cat_or_product = get_post_meta( $product->get_id(), '_kit_from_cat_product', true );
			if ( $cat_or_product && $cat_or_product == 'defined_category' ) {
				$cat_data          = get_post_meta( $product->get_id(), '_wckp_cat_ids', true );
				$argsA['category'] = $cat_data;
			} elseif ( $cat_or_product && $cat_or_product == 'defined_product' ) {
				$product_data     = get_post_meta( $product->get_id(), '_wckp_product_ids', true );
				$argsA['include'] = $product_data;
			}

			$products = wc_get_products( $argsA );
			if ( $products ) {
				return $products;
			}

			return false;
			//$this->pr($products);

		}

		protected function product_ddlist( $key = null, $kp = 0 ) {
			//global $product;
			$products = $this->get_products( $this->wc_kit_product );
			$return   = '';
			if ( $products ) {
				?>
				<select name="wckp-products[<?php esc_attr_e( $key ); ?>]"
				        class="wc-enhanced-select form-control kit_product_select">
					<option vlaue="0"></option>
					<?php
					foreach ( $products as $product ) {
						$p         = wc_get_product( $product );
						$image_src = wp_get_attachment_image_src( get_post_thumbnail_id( $product ), 'thumbnail' )[0];
						if ( ! $image_src ) {
							$image_src = wc_placeholder_img_src( 'thumbnail' );
						} ?>
						<option <?php esc_attr_e( selected( $kp, $product, false ) ); ?>
							data-price="<?php esc_attr_e( wc_price( $p->get_price() ) ) ?>"
							data-img_src="<?php esc_attr_e( $image_src ); ?>"
							value="<?php esc_attr_e( $product ); ?>"><?php echo esc_html_e( get_the_title( $product ), 'kit-product-for-woocommerce' ); ?></option>
					<?php } ?>
				</select>
			<?php }
		}

		protected function get_kit_item_quantity( $parent_product, $product_id ) {

			if ( wckp_get_kit_edit_params( 'kit-edit-key' ) ) {
				$key  = wckp_get_kit_edit_params( 'kit-edit-key' );
				$cart = WC()->cart->get_cart();

				$cart_contents = WC()->cart;

				if ( $cart ) {
					$qty             = 0;
					$parent_item_key = '';
					foreach ( $cart as $cart_item_key => $cart_item ) {
						if ( $key == $cart_item['unique_key'] ) {
							$parent_item_key = $cart_item_key;
							break;
						}
					}
					foreach ( $cart as $cart_item_key => $cart_item ) {
						if ( ( isset( $cart_item['kit_item_of'] ) && $cart_item['kit_item_of'] == $parent_item_key ) && ( $cart_item['product_id'] == $product_id || $cart_item['variation_id'] == $product_id ) ) {
							$qty = $cart_item['quantity'];
							break;
						}
					}

					return $qty;
				}

			} else {
				return 1;
			}
		}


		public function kit_saved_price_html($price_html, $product){

			global $woocommerce_loop;

			if('yes' === get_post_meta($product->get_id(), '_show_customer_saved_price', true)){
				if ( is_product() && ! $woocommerce_loop['name'] == 'related' && ( 'regular' === get_post_meta( $product->get_id(), '_kit_pricing', true ) ) ) {

					$total_price = 0;
					$products    = $this->get_products( $product );
					if ( ! empty( $products ) ) {
						foreach ( $products as $product_id ) {
							$price = $this->kit_get_product_price( $product_id );
							$total_price += $price ? $price : 0;
						}
						$sp          = $total_price - $product->get_price();
						$saved_price = wc_price( $sp );
						$price_html  = $price_html . '<br><span><small>You save:' . $saved_price . '</small></span>';
					}

				}

			}
			return $price_html;

		}

		protected function kit_get_product_price($id){
			if((int)$id){
				$product = wc_get_product( (int)$id );
				if($product->get_price()) {
					return $product->get_price();
				}
			}
			return null;
		}

		public static function init() {
			$class = __CLASS__;
			new $class;
		}

	}
	//add_action('wp',array('WC_Kit_Product','init'), 99);
	new WC_Kit_Product;
}

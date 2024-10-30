<?php
/**
 * Surfer
 */

/**
 * Register the custom product type after init
 */
function wckp_register_kit_product_product_type() {

    /**
     * This should be in its own separate file.
     */
    class WC_Product_Kit_Product extends WC_Product {

        public function __construct( $product ) {

            $this->product_type = 'kit_product';
            $this->supports[]   = 'ajax_add_to_cart';
            parent::__construct( $product );
            if($this->product_type == 'kit_product'){
                add_filter('woocommerce_get_price_html', array($this, 'kit_get_price_html'), 10, 2 );
            }
        }

        public function add_to_cart_text() {
            return apply_filters( 'woocommerce_product_add_to_cart_text', $this->is_purchasable() ? __( 'Select products', 'kit-product-for-woocommerce' ) : __( 'Read more', 'kit-product-for-woocommerce' ), $this );
        }

        protected function sort_variation_prices( $prices ) {
            asort( $prices );
    
            return $prices;
        }


	/**
	 * Returns the price in html format.
	 *
	 * Note: Variable prices do not show suffixes like other product types. This
	 * is due to some things like tax classes being set at variation level which
	 * could differ from the parent price. The only way to show accurate prices
	 * would be to load the variation and get it's price, which adds extra
	 * overhead and still has edge cases where the values would be inaccurate.
	 *
	 * Additionally, ranges of prices no longer show 'striked out' sale prices
	 * due to the strings being very long and unclear/confusing. A single range
	 * is shown instead.
	 *
	 * @param string $price Price (default: '').
	 * @return string
	 */
	public function kit_get_price_html( $price = '' ) {
        global $product;
	
        $_kit_pricing = get_post_meta( $product->get_id(), '_kit_pricing', true );
			if($_kit_pricing == 'custom'){
                $_kit_products = $this->get_products($product);
                $prices = $this->get_kit_product_prices($_kit_products);
                $min_price     = current( $prices['price'] );
                $max_price     = end( $prices['price'] );
                $min_reg_price = current( $prices['regular_price'] );
                $max_reg_price = end( $prices['regular_price'] );

                if ( $min_price !== $max_price || ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) ) {
                    $price = 'From: ' . wc_price( $min_price ) . $product->get_price_suffix();
                 } else {
                    $price = wc_price( $min_price );
                }
            }else{
                if( $product->get_price() ) {
                    $price = wc_price( $product->get_regular_price() );
                } elseif ( $this->is_on_sale() ) {
                    $price = wc_format_sale_price( $product->get_regular_price(), $product->get_sale_price() );
                }
            }
            
			return $price;
			//$price = apply_filters( 'woocommerce_variable_price_html', $price . $this->get_price_suffix(), $this );
		//}
		//return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}

    
    public function get_kit_product_prices($products){
        $prices = array();
        if($products){
            foreach($products as $product_id){
                $product = wc_get_product( $product_id );
                $prices['price'][$product_id] = $product->get_price();
                $prices['regular_price'][$product_id] = $product->get_regular_price();
                $prices['sale_price'][$product_id] = $product->get_sale_price();
            }
        }
        return $prices;
    }
    public function get_products($product){
        //global $product;
        $argsA = array(
            'status'    => 'published',
            'type'  => 'simple',
            'limit' => -1,
            'return' => 'ids'
        );
        $cat_or_product = get_post_meta( $product->get_id(), '_kit_from_cat_product', true );
        if($cat_or_product && $cat_or_product == 'defined_category'){
            $cat_data = get_post_meta( $product->get_id(), '_wckp_cat_ids', true );
            $argsA['category'] = $cat_data;
        }elseif($cat_or_product && $cat_or_product == 'defined_product'){
            $product_data = get_post_meta( $product->get_id(), '_wckp_product_ids', true );
            $argsA['include'] = $product_data;
        }
    
        $products = wc_get_products( $argsA );
        if($products){
            return $products;
        }
        return false;
        //$this->pr($products);

    }

    }

}
add_action( 'init', 'wckp_register_kit_product_product_type' );

/**
 * Add to product type drop down.
 */
function wckp_add_kit_product( $types ){

    // Key should be exactly the same as in the class
    $types[ 'kit_product' ] = __( 'Kit Product', 'kit-product-for-woocommerce' );

    return $types;

}
add_filter( 'product_type_selector', 'wckp_add_kit_product' );
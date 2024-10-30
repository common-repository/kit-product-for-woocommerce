<?php
if(!class_exists('Kit_Product_Cart_Work')){
    class Kit_Product_Cart_Work{
        
        public function __construct(){

            add_action( 'woocommerce_add_to_cart', array( $this, 'add_kit_products_to_cart' ), 10, 6 );

            add_action('woocommerce_before_calculate_totals', array($this, 'sum_kit_child_product_prices'), 20, 1 );

            add_filter( 'woocommerce_cart_item_class', array( $this, 'kit_item_cart_class' ), 10, 3 );
            
            add_filter( 'woocommerce_mini_cart_item_class', array( $this, 'kit_item_cart_class' ), 10, 3 );
         
            add_filter( 'woocommerce_order_item_class', array( $this, 'kit_item_order_item_class' ), 10, 3 );
           
            add_filter( 'woocommerce_admin_html_order_item_class', array( $this, 'kit_item_order_item_class' ), 10, 3 );
            
            add_filter( 'woocommerce_add_cart_item_data', array( $this, 'set_cart_product_unique_key' ), 10, 4 );

            add_filter('woocommerce_cart_item_remove_link', array( $this, 'remove_product_delete_cart_item' ), 10, 2);
            
            add_action( 'woocommerce_cart_item_removed', array( $this, 'remove_kit_child_products' ), 10, 2);
            
			add_action( 'woocommerce_cart_item_restored', array( $this, 'kit_cart_items_restored' ), 10, 2 );

            add_filter('woocommerce_cart_item_subtotal', array($this, 'display_kit_item_cart_subtotal'), 10, 3);
            
            add_filter('woocommerce_cart_item_price', array($this, 'display_kit_item_cart_price'), 10, 3);

            add_filter( 'woocommerce_cart_item_quantity', array($this, 'kit_child_products_remove_quantity_field_regular_pricing'), 10, 3 );
            
            add_filter( 'woocommerce_add_to_cart_validation', array($this, 'add_kit_product_validation'), 10, 5 );  

            //add_filter('woocommerce_cart_item_removed_notice_type', '__return_null');

            add_filter('woocommerce_cart_item_name', array($this, 'add_kit_name_on_cart_child_product'), 10, 3);

            add_filter('woocommerce_order_item_name', array($this, 'add_kit_name_on_order_child_product'), 10, 3);

            add_action('woocommerce_after_cart_item_name', array($this, 'kit_product_edit_cart_item_permalink'), 10, 2);

            add_filter( 'woocommerce_add_to_cart_redirect', array($this, 'redirect_cart_add_kit') );

            add_filter( 'wc_add_to_cart_message', array($this, 'kit_update_to_cart_message'), 10, 2 );

            add_filter( 'wckp_buy_kit_button_text', array($this, 'kit_update_buy_kit_button_text'), 10, 1 );

            add_action( 'woocommerce_checkout_create_order_line_item', array($this, 'kit_product_checkout_create_order_line_item'), 10, 4 );

            add_filter( 'woocommerce_order_formatted_line_subtotal', array($this, 'kit_item_subtotal_formatting'), 10, 3 );

            add_action('woocommerce_after_order_itemmeta', array($this, 'add_kit_name_to_child_title_admin_order'), 10, 3);
            
            add_filter( 'woocommerce_hidden_order_itemmeta', array($this, 'kit_item_hide_order_itemmeta'), 10, 1 );
        }

        
        public function kit_update_buy_kit_button_text($text){
            if( wckp_get_kit_edit_params('kit-edit-key')){
                return $text = _x('Update Kit', 'placeholder','kit-product-for-woocommerce');
            }
            return $text;
        }
        
        public function kit_update_to_cart_message($message, $product_id){
            
            if(wckp_get_kit_edit_params('kit-edit-key')){
                return '';
            }
            return $message;
        }
 
        public function redirect_cart_add_kit() {
            if(wckp_get_kit_edit_params('kit-edit-key')){
                return wc_get_cart_url();
            }

        }

        public function kit_product_edit_cart_item_permalink ( $cart_item, $cart_item_key){
            
            if( isset($cart_item['kit_products']) && is_array($cart_item['kit_products']) ){
                $kit_products = implode(',', $cart_item['kit_products']);
                $ukey =  $cart_item['unique_key'];
                $qty =  $cart_item['quantity'];
                
                $product_url = get_permalink($cart_item['product_id']);
                $product_permalink = add_query_arg( array('edit-kit-products'=> $kit_products, 'kit-quantity' => $qty, 'kit-edit-key' => $ukey), $product_url );
                $edit_link = sprintf( '<br><span><a class="kit-edit-link" href="%s">%s</a></span>', esc_url( $product_permalink ), esc_html__('Edit Kit', 'kit-product-for-woocommerce') );
                
                echo apply_filters( 'wckp_edit_kit_item_permalink', $edit_link, $cart_item, $cart_item_key );
             }

        }

        public function add_kit_product_validation( $passed ) {
            
            if ( empty( wckp_get_posted_array_params('wckp-products') )) {
                wc_add_notice( __( 'Choose atleast 1 product.', 'kit-product-for-woocommerce' ), 'error' );
                $passed = false;
            }
            return $passed;
        }
            
        public function add_kit_products_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $parent_cart_key = null ) {

            $productsArray = array();
            //$wckp_products = $_POST['wckp-products'];
            
            //$wckp_products = isset( $_POST['wckp-products'] ) ? (array) $_POST['wckp-products'] : array();
            $wckp_products = array_map( 'sanitize_text_field', wp_unslash($_POST['wckp-products'] ));
            
            $_kit_pricing = get_post_meta( $product_id, '_kit_pricing', true );
            if($wckp_products){
                foreach ($wckp_products as $key => $val){

                    if(!$val) continue;
                    if($_kit_pricing == 'regular'){
                        $wckp_product_qty = sanitize_text_field($_POST['quantity'] * 1); //
                    }else{
                        $wckp_product_qty = sanitize_text_field($_POST['qty-'.$val]);
                    }
                    $productsArray[$val] = array('id' => $val, 'qty' => $wckp_product_qty, 'bulk_qty' => 1);
                }
            }

            if ( $productsArray ) {

                //Product available

                if ( null !== '' ) {
                    //return;
                }

                $kit_cart_item_data = array(
                    'kit_item_of' => $cart_item_key,
                );

                $kit_parent_id = sanitize_text_field($_POST['add-to-cart']);

                // if(isset($_POST['edit-kit-product']) && $_POST['edit-kit-product'] != '' && $_kit_pricing == 'regular'  ){
                //     $kit_item_cart_key = $this->check_regular_altered_kit( $parent_cart_key, $product_id, $productsArray, $wckp_product_qty, $cart_item_data );
                // }

                foreach ( $productsArray as $pid => $products_data ) {

                    $_product = wc_get_product( $pid );

                    if ( $_product instanceof WC_Product ) {

                        $this->kit_cart_item_data = $kit_cart_item_data;
                        
                        // Prepare for adding children to cart.
                        $kit_item_cart_key = $this->kit_add_to_cart( $cart_item_key, $pid, $kit_parent_id, $products_data['qty'], '', '', $kit_cart_item_data );

                    }
                }
            }
        }

        public function check_regular_altered_kit(){
            
            if( wckp_get_kit_edit_params('kit-edit-key')){
                $key = wckp_get_kit_edit_params('kit-edit-key');
                $cart = WC()->cart->get_cart();
                $cart_contents = WC()->cart;
                
                if($cart){
                    $c = 1;
                    foreach($cart as $cart_item_key => $cart_item){
                        if($cart_item['unique_key'] == $key){
                            WC()->cart->remove_cart_item($cart_item_key);
                        }
                        $c++;
                    }
                }

            }
            //return $cart_item_data;
        }

        public function kit_add_to_cart( $parent_cart_key, $product_id, $kit_parent_id, $quantity = 1, $variation_id = '', $variation = '', $cart_item_data ) {
            
            $this->check_regular_altered_kit();
            
            // Load cart item data when adding to cart.
            $cart_item_data = (array) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id, $quantity );

            // Generate a ID based on product ID, variation ID, variation data, and other cart item data.
            $cart_id = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

            // See if this product and its options is already in the cart.
            $cart_item_key = WC()->cart->find_product_in_cart( $cart_id );

            // Get the product.
            $product_data = wc_get_product( $variation_id ? $variation_id : $product_id );

            // If cart_item_key is set, the item is already in the cart and its quantity will be handled by update_quantity_in_cart().
            if ( ! $cart_item_key ) {

                $cart_item_key = $cart_id;

                WC()->cart->cart_contents[ $parent_cart_key ]['kit_products'][] = $variation_id ? $variation_id : $product_id;
               
                WC()->cart->cart_contents[ $cart_item_key ] = apply_filters(
                    'woocommerce_add_cart_item',
                    array_merge(
                        $cart_item_data,
                        array(
                            'kit_id'          => $kit_parent_id,
                            'product_id'          => $product_id,
                            'variation_id'        => $variation_id,
                            'variation'           => $variation,
                            'quantity'            => $quantity,
                            'data'                => $product_data,
                        )
                    ),
                    $cart_item_key
                );

            }

            return $cart_item_key;
        }

        public function remove_product_delete_cart_item($link, $cart_item_key) {
            $cart_item_data = WC()->cart->get_cart_item( $cart_item_key );
            if(isset($cart_item_data['kit_item_of'])){
                return '';
            }else{
                return $link;
            }
        }

        public function remove_kit_child_products($cart_item_key, $cart ){
            $cart_items = $cart->cart_contents;

            if ( ! empty( $cart_items ) ) {
                foreach ( $cart_items as $key => $item ) {
                    if($item['kit_item_of'] == $cart_item_key){
                        $cart->removed_cart_contents[ $key ] = $item;
                        unset( $cart->cart_contents[ $key ] );
                        do_action( 'woocommerce_cart_item_removed', $key, $cart );
                    }
                }
            }
        }
    
        public function kit_cart_items_restored($cart_item_key, $cart ){

            if ( ! empty( $cart->cart_contents[ $cart_item_key ] ) && ! empty( $cart->removed_cart_contents ) ) {
				foreach ( $cart->removed_cart_contents as $item_key => $item ) {
					if ( ! empty( $item['kit_item_of'] ) && $item['kit_item_of'] === $cart_item_key ) {
						$cart->cart_contents[ $item_key ] = $item;
						unset( $cart->removed_cart_contents[ $item_key ] );
						do_action( 'woocommerce_cart_item_restored', $item_key, $cart );
					}
				}
			}

        }

        public function set_cart_product_unique_key( $cart_item_data, $product_id ) {
            $product =  wc_get_product($product_id);
            if($product->get_type() == 'kit_product'){
                $cart_item_data['product_type'] = 'kit_product';
            }
            if($product->get_type() == 'kit_product' || $cart_item_data['kit_item_of']){
                $unique_cart_item_key = md5( microtime() . rand() );
                $cart_item_data['unique_key'] = $unique_cart_item_key;
                //$cart_item_data['org_price'] = $cart_item_data['data']->get_price();
            }
            return $cart_item_data;
          }
          

        public function kit_item_cart_class($class, $cart_item, $cart_item_key ) {

            if ( isset( $cart_item[ 'kit_item_of' ] ) ) {
                $class .= ' kit-item';
            }
        
            return $class;
        }

        public function add_kit_name_on_cart_child_product($product_name, $cart_item, $cart_item_key){
            if(isset($cart_item['kit_item_of']) && $cart_item['kit_item_of'] != ''){
                $cart = WC()->cart->get_cart();
                
                $cart_item = $cart[$cart_item['kit_item_of']];
                $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
                if($cart_item['data']){
                    $name = $cart_item['data']->get_title();
                    $link = $cart_item['data']->get_permalink($cart_item);
                    if(is_checkout()){
                        $parent_product_name = $name;
                    }else{
                        $parent_product_name = sprintf( '<a href="%s">%s</a>', esc_url( $link ), $name );
                    }
                    return $parent_product_name.' → '.$product_name;
                }
            }
            return $product_name;
        }

        public function add_kit_name_on_order_child_product($product_name, $item, $is_visible){
            $_kit_parent = $item->get_meta( '_kit_parent' );
            if( $_kit_parent != ''){
                $product = wc_get_product($_kit_parent);

                $name = $product->get_name();
                $link = $product->get_permalink();
                
                $parent_product_name = sprintf( '<a href="%s">%s</a>', esc_url( $link ), $name );
                return $parent_product_name.' → '.$product_name;
            }
            return $product_name;
        }

        public function kit_item_order_item_class($class, $item, $order ) {
           // lf_wckp($item);
            $_product_type = $item->get_meta( '_product_type' );
            $_kit_parent = $item->get_meta( '_kit_parent' );
            
            if ( $_product_type == 'kit_product' &&  $_kit_parent == '')  {
                $class .= ' kit-parent-item';
            }elseif($_kit_parent != ''){
                $class .= ' kit-child-item';
            }
        
            return $class;
        }

        public function kit_product_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
            
            $line_item_meta = apply_filters(
                'wckp_add_line_item_meta_args',
                array(
                    '_product_type' => isset( $values['product_type'] ) ? $values['product_type'] : '',
                    '_kit_parent' => isset( $values['kit_id'] ) ? $values['kit_id'] : '',
                )
            );
            foreach($line_item_meta as $key => $val){
                $item->add_meta_data( $key, $val,false);
            }
          }

          public function kit_item_hide_order_itemmeta($metakeys){
            $metakeys[] = '_product_type';
            $metakeys[] = '_kit_parent';
            return $metakeys;
        }

          public function add_kit_name_to_child_title_admin_order($item_id, $item, $product){
            
            $_kit_parent = $item->get_meta( '_kit_parent' );
            if($_kit_parent){
            $parent_product = wc_get_product($_kit_parent);
            $name = $parent_product->get_name();

            echo apply_filters( 'wckp_before_order_itemmeta_kit', '<div class="wckp-imeta-kit">' . sprintf( esc_html__( '%s : %s', 'kit-product-for-woocommerce' ), $name, $product->get_name() ) . '</div>', $item_id, $item );
            }
        }
          
        public function kit_item_subtotal_formatting($subtotal, $item, $order){
            $_product_type = $item->get_meta( '_product_type' );
            $_kit_parent = $item->get_meta( '_kit_parent' );
            //lf_wckp($item->get_product_id());
            $product = wc_get_product($item->get_product_id());
            if($_kit_parent){
                $subtotal = wc_price( $product->get_price() * $item->get_quantity() , array( 'currency' => $order->get_currency() ) );
                return '<del>'.$subtotal.'</del>';
            }
            return $subtotal;
        } 
          
        public function sum_kit_child_product_prices($cart){
           // var_dump($cart);
            if ( is_admin() && ! defined( 'DOING_AJAX' ) )
                return;

            if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
                return;

                
                $kit_pricing = '';
                
                foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {
                    
                    if($cart_item['product_type']  == 'kit_product'){
                        $kit_pricing = get_post_meta($cart_item['product_id'], '_kit_pricing', true );
                        $kit_price = 0;
                    }

                    if ( isset( $cart_item[ 'kit_item_of' ] ) ) {
                       $kit_item_of =  $cart->cart_contents[ $cart_item[ 'kit_item_of' ] ];
                       
                       $q = $kit_item_of['quantity'];

                       if($kit_pricing == 'regular'){
                            if($kit_item_of['key'] == $cart_item[ 'kit_item_of' ]){
                                $cart_item['data']->set_price(0);
                                
                                $cart->set_quantity($cart_item_key, $q);
                            }
                       }
                    }
                } 

        }

        public function set_kit_item_old_quantity($cart_item_key, $quantity, $old_quantity, $cart){
            $cart->cart_contents[ $cart_item_key ]['old_quantity'] = $old_quantity;
        }

        public function display_kit_item_cart_subtotal($subtotal, $cart_item, $cart_item_key){
            if ( isset( $cart_item[ 'kit_item_of' ] ) ) {
                $_product = wc_get_product( $cart_item['product_id'] );
                $product_id = $cart_item['kit_id'];
                $_kit_pricing = get_post_meta( $product_id, '_kit_pricing', true );
                if($_kit_pricing == 'regular'){
                    return '<del>'.WC()->cart->get_product_subtotal( $_product, $cart_item['quantity']).'</del>';
                }
                return WC()->cart->get_product_subtotal( $_product, $cart_item['quantity']);
            }

            return $subtotal;
        }

        public function display_kit_item_cart_price($price, $cart_item, $cart_item_key){
            if ( isset( $cart_item[ 'kit_item_of' ] ) ) {
                $_product = wc_get_product( $cart_item['product_id'] );
                $product_id = $cart_item['kit_id'];
                $_kit_pricing = get_post_meta( $product_id, '_kit_pricing', true );
                if($_kit_pricing == 'regular'){
                    return '<del>'.WC()->cart->get_product_price( $_product).'</del>';
                }
                return WC()->cart->get_product_price( $_product);
            }
            return $price;
        }
        
       public function kit_child_products_remove_quantity_field_regular_pricing($product_quantity, $cart_item_key, $cart_item){
        
        $product_id = $cart_item['kit_id'];
        $_kit_pricing = get_post_meta( $product_id, '_kit_pricing', true );
        
        if($_kit_pricing == 'regular'){
            return sprintf( $cart_item['quantity'].' <input type="hidden" name="cart[%s][qty]" value="'.$cart_item['quantity'].'" />', $cart_item_key );
        }
        return $product_quantity;
       }

    }
    new Kit_Product_Cart_Work;
}
<?php
/**
 * Surfer
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
    return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

    <?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

    <form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
        
        <?php
        do_action( 'woocommerce_before_add_to_cart_quantity' );

        do_action( 'woocommerce_before_kit_product_add_to_cart_button' );
        
        if(wckp_get_kit_edit_params('kit-quantity')){
            $quantity = wckp_get_kit_edit_params('kit-quantity');
        }else{
           $quantity = wckp_get_kit_edit_params('quantity') ? wc_stock_amount( wp_unslash( wckp_get_kit_edit_params('quantity') ) ) : $product->get_min_purchase_quantity(); // WPCS: CSRF ok, input var ok.
        }

        woocommerce_quantity_input(
            array(
                'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
                'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
                'input_value' => $quantity,
            )
        );
        do_action( 'woocommerce_after_kit_product_add_to_cart_quantity' );
        ?>

        <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="kit_product_add_to_cart_button button alt"><?php echo apply_filters('wckp_buy_kit_button_text', _x( 'Buy Kit', 'placeholder', 'kit-product-for-woocommerce' ));?></button>

        <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
    </form>

    <?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>

<?php

namespace vdws\woocommerce;

class WC_Custom_Shipping_Method extends \WC_Shipping_Method {

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct($instance_id = 0) {
        $this->id = 'custom_shipping_method';
        $this->instance_id = absint( $instance_id );
        $this->title = __('Click and Collect');
        $this->method_title = __('Click and Collect');
        $this->method_description = __('Description of this shipping method');
        $this->enabled = "yes";
        $this->shipping_fee = 0;
        $this->min_amount = 0;
        $this->supports = array('shipping-zones', 'instance-settings', 'instance-settings-modal');
        $this->init();
    }

    /**
     * Init settings
     *
     * @access public
     * @return void
     */
    function init() {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title' );
        $this->min_amount = $this->get_option( 'min_amount' );
        $this->shipping_fee = $this->get_option( 'shipping_fee' );

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = array()) {
        $this->add_rate( array(
            'label'   => $this->title,
            'cost'    => $this->shipping_fee,
            'taxes'   => false,
            'package' => $package,
        ) );
    }

    public function init_form_fields() {
        $this->instance_form_fields = array(
            'title' => array(
                'title'       => __( 'Title', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default'     => $this->method_title,
                'desc_tip'    => true,
            ),
            'shipping_fee' => array(
                'title'       => __( 'Shipping fee', 'woocommerce' ),
                'type'        => 'price',
                'description' => __( 'This controls how much will shipping cost to the customer.', 'woocommerce' ),
                'default'     => $this->shipping_fee,
                'desc_tip'    => true,
            ),
            'min_amount' => array(
                'title'       => __( 'Minimum order amount', 'woocommerce' ),
                'type'        => 'price',
                'description' => __( 'This controls the minimum order amount, which the customer should reach before this shipping method becomes available.', 'woocommerce' ),
                'default'     => $this->min_amount,
                'desc_tip'    => true,
            )
        );
    }

    public function get_instance_form_fields() {

        return parent::get_instance_form_fields();

    }

    public function is_available($package) {

        if (get_option('wc_settings_tab_map_pudo_enable') != 'yes' && get_option('wc_settings_tab_map_pp_enable') != 'yes') {

            return false;

        }

        $total = WC()->cart->get_displayed_subtotal();

        if ($total >= $this->min_amount) {

            return true;

        } else {

            return false;

        }

    }

}

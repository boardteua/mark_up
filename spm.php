<?php

/**
 *  Simple Product Margin
 *
 * @link              https://board.te.ua
 * @since             1.0.0
 * @package           simple_product_margin
 *
 * @wordpress-plugin
 * Plugin Name:       Simple Product Margin
 * Plugin URI:        #
 * Description:       Helps to make product margin in percentage. Globally and per product
 * Version:           1.0.0
 * Author:            org100h
 * Author URI:        #
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       spm
 */
if (!defined('WPINC')) {
    die;
}

class mark_up {

    public function __construct() {
        /// add option to product price 
        add_action('woocommerce_product_options_pricing', [$this, 'up_cost_product_field']);
        add_action('woocommerce_variation_options_pricing', [$this, 'up_cost_product_field']);
        // save post 
        add_action('save_post', [$this, 'up_save_product']);

        // load fields after theme setup
        add_filter('woocommerce_general_settings', [$this, 'up_woo_setting']);

        // Simple, grouped and external products
        add_filter('woocommerce_product_get_price', [$this, 'up_custom_price'], 99, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'up_custom_price'], 99, 2);

        // Variations 
        add_filter('woocommerce_product_variation_get_regular_price', [$this, 'up_custom_price'], 99, 2);
        add_filter('woocommerce_product_variation_get_price', [$this, 'up_custom_price'], 99, 2);

        // Variable (price range)
        add_filter('woocommerce_variation_prices_price', [$this, 'up_custom_variable_price'], 99, 3);
        add_filter('woocommerce_variation_prices_regular_price', [$this, 'up_custom_variable_price'], 99, 3);
    }

    public function up_cost_product_field() {
        woocommerce_wp_text_input([
            'id' => 'cost_price',
            'type' => 'number',
            'class' => 'wc_input_price short',
            'label' => __('Margin (%)', 'spm'),
            'css' => 'width:50px;',
            'default' => '1',
            'custom_attributes' => [
                'step' => 1,
            ],
        ]);
    }

    // global markup fields setting in woocommerce
    public function up_woo_setting($settings) {
        $updated_settings = array();
        foreach ($settings as $section) {
            // at the bottom of the General Options section
            if (isset($section['id']) && 'general_options' == $section['id'] &&
                    isset($section['type']) && 'sectionend' == $section['type']) {
                $updated_settings[] = [
                    'name' => __('Global margin (%)', 'spm'),
                    'id' => 'global_cost_price',
                    'type' => 'number',
                    'css' => 'width:50px;',
                    'default' => '1',
                    'desc_tip' => true,
                    'custom_attributes' => array(
                        'min' => 1,
                        'step' => 1,
                    ),
                ];
            }
            $updated_settings[] = $section;
        }
        return $updated_settings;
    }

    public function up_save_product($product_id) {
        // stop the quick edit interferring as this will stop it saving properly, when a user uses quick edit feature
        if (isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce'))
            return;

        // If this is a auto save do nothing, we only save when update button is clicked
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (isset($_POST['cost_price'])) {
            if (is_numeric($_POST['cost_price']))
                update_post_meta($product_id, 'cost_price', $_POST['cost_price']);
        } else
            delete_post_meta($product_id, 'cost_price');
    }

    public function up_custom_price($price, $product) {
        return $this->up_percentage($price, $this->up_get_price_multiplier($product->get_id()));
    }

    public function up_custom_variable_price($price, $variation, $product) {
        return $this->up_percentage($price, $this->up_get_price_multiplier($product->get_id()));
    }

    private function up_get_price_multiplier($product_id) {
        if (get_post_meta($product_id, 'cost_price')) {
            return get_post_meta($product_id, 'cost_price')[0];
        } elseif (get_option('global_cost_price')) {
            return get_option('global_cost_price');
        } else {
            return false;
        }
    }

    private function up_percentage($num, $per) {
        return $per ? $num + ($per / 100) * $num : 0;
    }

}

$mark_up = new mark_up();

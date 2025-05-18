<?php
/**
 * TheMoak Virtual Try-on Extensions
 * 
 * Adds "No Icon" option and per-product adjustment settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class TheMoak_Tryon_Extensions {

    public function __construct() {
        // Add filters for the button icon
        add_filter('themoak_tryon_button_icon_options', array($this, 'add_no_icon_option'), 10, 1);
        add_filter('themoak_tryon_button_html', array($this, 'modify_button_html'), 10, 3);
        
        // Add product settings
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_adjustment_options'), 20);
        add_action('woocommerce_process_product_meta', array($this, 'save_product_adjustment_options'), 20);
        
        // Modify AJAX response
        add_filter('themoak_tryon_product_data', array($this, 'add_product_settings_to_data'), 10, 2);
        
        // Add script to handle product-specific settings
        add_action('wp_footer', array($this, 'add_frontend_script'), 99);
    }

    /**
     * Add "No Icon" option to icon dropdown
     */
    public function add_no_icon_option($options) {
        return array('none' => __('No Icon', 'themoak-virtual-tryon')) + $options;
    }

    /**
     * Modify button HTML to handle "none" icon
     */
    public function modify_button_html($html, $button_icon, $button_text) {
        if ($button_icon === 'none') {
            return sprintf(
                '<button type="button" class="themoak-tryon-button" data-product-id="%d">
                    <span class="themoak-tryon-button-text">%s</span>
                </button>',
                $product_id,
                esc_html($button_text)
            );
        }
        return $html;
    }

    /**
     * Add product adjustment options
     */
    public function add_product_adjustment_options() {
        global $post;
        
        // Only add if try-on is enabled
        $enabled = get_post_meta($post->ID, '_themoak_tryon_enabled', true);
        if ($enabled !== 'yes') {
            return;
        }
        
        echo '<div class="options_group">';
        
        // Heading for custom adjustment settings
        echo '<h4 style="margin: 15px 12px; padding-top: 10px; border-top: 1px solid #eee;">' . __('Glasses Appearance Adjustments', 'themoak-virtual-tryon') . '</h4>';
        echo '<p style="margin: 15px 12px;">' . __('Customize how the glasses appear on the user\'s face. Leave empty to use global default settings.', 'themoak-virtual-tryon') . '</p>';
        
        // Position Y adjustment
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_position_y',
                'label'             => __('Vertical Position (Y)', 'themoak-virtual-tryon'),
                'description'       => __('Adjust vertical position. Negative values move up, positive values move down. Default: -4', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '-50',
                    'max'  => '50',
                ),
                'placeholder'       => '-4',
            )
        );
        
        // Position X adjustment
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_position_x',
                'label'             => __('Horizontal Position (X)', 'themoak-virtual-tryon'),
                'description'       => __('Adjust horizontal position. Negative values move left, positive values move right. Default: -2', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '-50',
                    'max'  => '50',
                ),
                'placeholder'       => '-2',
            )
        );
        
        // Size Scale adjustment
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_size_scale',
                'label'             => __('Size Scale', 'themoak-virtual-tryon'),
                'description'       => __('Adjust the size of the glasses. Values below 1.0 make smaller, above 1.0 make larger. Default: 0.9', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '0.05',
                    'min'  => '0.5',
                    'max'  => '1.5',
                ),
                'placeholder'       => '0.9',
            )
        );
        
        // Reflection Position
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_reflection_pos',
                'label'             => __('Reflection Position', 'themoak-virtual-tryon'),
                'description'       => __('Adjust the position of reflections on lenses. Higher values move reflections down. Default: 8', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '-20',
                    'max'  => '20',
                ),
                'placeholder'       => '8',
            )
        );
        
        // Reflection Size
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_reflection_size',
                'label'             => __('Reflection Size', 'themoak-virtual-tryon'),
                'description'       => __('Adjust the size of reflections. Values below 1.0 make smaller, above 1.0 make larger. Default: 0.5', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '0.05',
                    'min'  => '0.2',
                    'max'  => '1.5',
                ),
                'placeholder'       => '0.5',
            )
        );
        
        // Reflection Opacity
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_reflection_opacity',
                'label'             => __('Reflection Opacity', 'themoak-virtual-tryon'),
                'description'       => __('Adjust the opacity of reflections. Values below 1.0 make more transparent, above 1.0 make more visible. Default: 0.7', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '0.1',
                    'min'  => '0',
                    'max'  => '2',
                ),
                'placeholder'       => '0.7',
            )
        );
        
        // Shadow Opacity
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_shadow_opacity',
                'label'             => __('Shadow Opacity', 'themoak-virtual-tryon'),
                'description'       => __('Adjust the opacity of shadow beneath glasses. Higher values make shadow darker. Default: 0.4', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '0.05',
                    'min'  => '0',
                    'max'  => '1',
                ),
                'placeholder'       => '0.4',
            )
        );
        
        // Shadow Offset
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_shadow_offset',
                'label'             => __('Shadow Offset', 'themoak-virtual-tryon'),
                'description'       => __('Adjust the vertical offset of shadow. Higher values move shadow down further. Default: 10', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '0',
                    'max'  => '30',
                ),
                'placeholder'       => '10',
            )
        );
        
        echo '</div>';
    }

    /**
     * Save product adjustment options
     */
    public function save_product_adjustment_options($post_id) {
        // Save adjustment settings (if provided)
        $adjustment_fields = array(
            '_themoak_tryon_position_y' => 'floatval',
            '_themoak_tryon_position_x' => 'floatval',
            '_themoak_tryon_size_scale' => 'floatval',
            '_themoak_tryon_reflection_pos' => 'floatval',
            '_themoak_tryon_reflection_size' => 'floatval',
            '_themoak_tryon_reflection_opacity' => 'floatval',
            '_themoak_tryon_shadow_opacity' => 'floatval',
            '_themoak_tryon_shadow_offset' => 'floatval',
        );
        
        foreach ($adjustment_fields as $field => $sanitize_func) {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $value = call_user_func($sanitize_func, $_POST[$field]);
                update_post_meta($post_id, $field, $value);
            } else {
                // Delete the meta if it's empty (to use default)
                delete_post_meta($post_id, $field);
            }
        }
    }

    /**
     * Add product settings to AJAX response
     */
    public function add_product_settings_to_data($data, $product_id) {
        // Get product-specific adjustment settings
        $product_settings = array(
            'positionY' => $this->get_product_meta_value($product_id, '_themoak_tryon_position_y', -4),
            'positionX' => $this->get_product_meta_value($product_id, '_themoak_tryon_position_x', -2),
            'sizeScale' => $this->get_product_meta_value($product_id, '_themoak_tryon_size_scale', 0.9),
            'reflectionPos' => $this->get_product_meta_value($product_id, '_themoak_tryon_reflection_pos', 8),
            'reflectionSize' => $this->get_product_meta_value($product_id, '_themoak_tryon_reflection_size', 0.5),
            'reflectionOpacity' => $this->get_product_meta_value($product_id, '_themoak_tryon_reflection_opacity', 0.7),
            'shadowOpacity' => $this->get_product_meta_value($product_id, '_themoak_tryon_shadow_opacity', 0.4),
            'shadowOffset' => $this->get_product_meta_value($product_id, '_themoak_tryon_shadow_offset', 10)
        );
        
        $data['settings'] = $product_settings;
        
        return $data;
    }
    
    /**
     * Helper function to get product meta value with default fallback
     */
    private function get_product_meta_value($product_id, $meta_key, $default_value) {
        $value = get_post_meta($product_id, $meta_key, true);
        return $value !== '' ? $value : $default_value;
    }
    
    /**
     * Add frontend script to handle product-specific settings
     */
    public function add_frontend_script() {
        ?>
        <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                // Wait for TheMoakTryOn to be defined
                var checkInterval = setInterval(function() {
                    if (typeof TheMoakTryOn !== 'undefined') {
                        clearInterval(checkInterval);
                        
                        // Store the original onFaceMeshResults function
                        var originalOnFaceMeshResults = TheMoakTryOn.onFaceMeshResults;
                        
                        // Override the onFaceMeshResults function
                        TheMoakTryOn.onFaceMeshResults = function(results) {
                            // Set productSettings property if undefined
                            if (typeof this.productSettings === 'undefined') {
                                this.productSettings = this.settings.optimized_settings;
                            }
                            
                            // Call the original function
                            originalOnFaceMeshResults.call(this, results);
                        };
                        
                        // Override the getProductData method to handle product-specific settings
                        var originalGetProductData = TheMoakTryOn.getProductData;
                        TheMoakTryOn.getProductData = function(productId) {
                            var self = this;
                            
                            $.ajax({
                                url: themoak_tryon_params.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'themoak_get_glasses_data',
                                    nonce: themoak_tryon_params.nonce,
                                    product_id: productId
                                },
                                beforeSend: function() {
                                    // Show loading state
                                },
                                success: function(response) {
                                    if (response.success) {
                                        self.currentProductId = response.data.product_id;
                                        self.currentProductName = response.data.product_name;
                                        self.currentGlassesUrl = response.data.image_url;
                                        
                                        // Store product-specific settings if available
                                        if (response.data.settings) {
                                            self.productSettings = response.data.settings;
                                        } else {
                                            // Use default settings if none provided
                                            self.productSettings = self.settings.optimized_settings;
                                        }
                                        
                                        // Open popup and start try-on
                                        self.openPopup();
                                    } else {
                                        console.error('Error loading product data:', response.data.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX error:', error);
                                }
                            });
                        };
                    }
                }, 100);
            });
        })(jQuery);
        </script>
        <?php
    }
}

// Initialize the extensions
new TheMoak_Tryon_Extensions();
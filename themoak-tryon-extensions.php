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
        // Note: The 'themoak_tryon_button_html' filter and its callback 'modify_button_html'
        // do not seem to be used by class-themoak-tryon-frontend.php for button generation.
        // The frontend class handles the "none" icon case directly.
        // If this filter is used elsewhere, the $product_id issue in modify_button_html would need addressing.
        // add_filter('themoak_tryon_button_html', array($this, 'modify_button_html'), 10, 3); 
        
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
     * This function has an undefined $product_id variable.
     * It also appears to be unused by the provided class-themoak-tryon-frontend.php.
     * Commenting out its registration in __construct for now.
     */
    /*
    public function modify_button_html($html, $button_icon, $button_text) {
        if ($button_icon === 'none') {
            // $product_id is not defined in this scope.
            // This would require $product_id to be passed to the filter or retrieved globally (which can be unreliable).
            // For now, assuming the frontend class handles this, as it checks for 'none' icon.
            // If this function IS critical, $product_id needs to be correctly sourced.
            // Example: global $product; $current_product_id = is_a($product, 'WC_Product') ? $product->get_id() : 0;
            return sprintf(
                '<button type="button" class="themoak-tryon-button" data-product-id="%d"> 
                    <span class="themoak-tryon-button-text">%s</span>
                </button>',
                0, // Placeholder for $product_id until correctly sourced if needed
                esc_html($button_text)
            );
        }
        return $html;
    }
    */

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
        echo '<h4 style="margin: 15px 12px; padding-top: 10px; border-top: 1px solid #eee;">' . __('Glasses Appearance Adjustments (Extensions)', 'themoak-virtual-tryon') . '</h4>';
        echo '<p style="margin: 15px 12px;">' . __('Customize how the glasses appear on the user\'s face. Leave empty to use global default settings. These settings are specific to the product edit page.', 'themoak-virtual-tryon') . '</p>';
        
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
        // Ensure numeric values are returned as numbers, not strings from meta
        if (is_numeric($value)) {
            $value = floatval($value);
        }
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
                var checkInterval = setInterval(function() {
                    if (typeof TheMoakTryOn !== 'undefined' && 
                        typeof themoak_tryon_params !== 'undefined' && 
                        themoak_tryon_params.settings && 
                        typeof themoak_tryon_params.settings.optimized_settings !== 'undefined') {
                        
                        clearInterval(checkInterval);
                        
                        // Make a deep copy of the original global defaults ONCE when TheMoakTryOn is ready.
                        // This ensures we always have a clean slate of global defaults.
                        const initialGlobalOptimizedSettings = JSON.parse(JSON.stringify(themoak_tryon_params.settings.optimized_settings));

                        // Store the original onFaceMeshResults function
                        var originalOnFaceMeshResults = TheMoakTryOn.onFaceMeshResults;
                        
                        // Override the onFaceMeshResults function
                        // This override mainly ensures productSettings is defined if other parts of an extension might use it.
                        // The primary application of settings should happen by modifying TheMoakTryOn.settings.optimized_settings
                        TheMoakTryOn.onFaceMeshResults = function(results) {
                            if (typeof this.productSettings === 'undefined' && this.settings && this.settings.optimized_settings) {
                                this.productSettings = this.settings.optimized_settings;
                            }
                            originalOnFaceMeshResults.call(this, results);
                        };
                        
                        // Override the getProductData method to handle product-specific settings
                        var originalGetProductData = TheMoakTryOn.getProductData;
                        TheMoakTryOn.getProductData = function(productId) {
                            var self = this; // 'this' is TheMoakTryOn
                            
                            $.ajax({
                                url: themoak_tryon_params.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'themoak_get_glasses_data',
                                    nonce: themoak_tryon_params.nonce,
                                    product_id: productId
                                },
                                beforeSend: function() {
                                    // Show loading state (original plugin might handle this)
                                },
                                success: function(response) {
                                    if (response.success) {
                                        self.currentProductId = response.data.product_id;
                                        self.currentProductName = response.data.product_name;
                                        self.currentGlassesUrl = response.data.image_url;
                                        
                                        // Apply settings
                                        if (response.data.settings) {
                                            // Merge product-specific settings onto the initial global defaults.
                                            // This updates the object (`optimized_settings`) that the original rendering logic likely uses.
                                            self.settings.optimized_settings = { ...initialGlobalOptimizedSettings, ...response.data.settings };
                                            // Also update self.productSettings for consistency or if other parts of an extension use it.
                                            self.productSettings = response.data.settings; 
                                        } else {
                                            // No product-specific settings, so revert to a fresh copy of initial global defaults.
                                            self.settings.optimized_settings = { ...initialGlobalOptimizedSettings };
                                            self.productSettings = { ...initialGlobalOptimizedSettings };
                                        }
                                        
                                        self.openPopup();
                                    } else {
                                        console.error('Error loading product data:', response.data.message);
                                        // On error, revert to initial global defaults to be safe
                                        self.settings.optimized_settings = { ...initialGlobalOptimizedSettings };
                                        self.productSettings = { ...initialGlobalOptimizedSettings };
                                        // Optionally, display an error to the user before attempting to open popup or prevent popup
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX error:', error);
                                    // On AJAX error, revert to initial global defaults
                                    self.settings.optimized_settings = { ...initialGlobalOptimizedSettings };
                                    self.productSettings = { ...initialGlobalOptimizedSettings };
                                    // Optionally, display an error to the user
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
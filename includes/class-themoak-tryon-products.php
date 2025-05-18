<?php
/**
 * TheMoak Virtual Try-on Products Class
 *
 * Handles product-specific functionality for the plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TheMoak_Tryon_Products Class
 */
class TheMoak_Tryon_Products {

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add product meta
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_options'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_options'));
        
        // AJAX handlers for the products admin page
        add_action('wp_ajax_themoak_tryon_enable_product', array($this, 'ajax_enable_product'));
        add_action('wp_ajax_themoak_tryon_disable_product', array($this, 'ajax_disable_product'));
        add_action('wp_ajax_themoak_tryon_update_glasses_image', array($this, 'ajax_update_glasses_image'));
        
        // Add the missing AJAX handlers for adjustments
        add_action('wp_ajax_themoak_get_product_adjustments', array($this, 'ajax_get_product_adjustments'));
        add_action('wp_ajax_themoak_save_product_adjustments', array($this, 'ajax_save_product_adjustments'));
    }

    /**
     * Add product options in WooCommerce product data
     */
    public function add_product_options() {
        global $post;
        
        echo '<div class="options_group">';
        
        // Enable/disable try-on
        woocommerce_wp_checkbox(
            array(
                'id'          => '_themoak_tryon_enabled',
                'label'       => __('Enable Virtual Try-on', 'themoak-virtual-tryon'),
                'description' => __('Enable virtual try-on for this product', 'themoak-virtual-tryon'),
                'desc_tip'    => true,
            )
        );
        
        // Glasses image upload
        woocommerce_wp_text_input(
            array(
                'id'                => '_themoak_tryon_glasses_image',
                'label'             => __('Glasses Image (PNG)', 'themoak-virtual-tryon'),
                'description'       => __('Upload a transparent PNG image of the glasses for virtual try-on', 'themoak-virtual-tryon'),
                'desc_tip'          => true,
                'type'              => 'text',
                'custom_attributes' => array('readonly' => 'readonly'),
            )
        );
        
        // Add upload button
        echo '<p class="form-field">';
        echo '<button type="button" class="button themoak-tryon-upload-image">' . __('Upload Image', 'themoak-virtual-tryon') . '</button>';
        echo '<span id="themoak-tryon-image-preview" style="display: block; margin-top: 10px;">';
        
        // Show image preview if exists
        $image_id = get_post_meta($post->ID, '_themoak_tryon_glasses_image_id', true);
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" alt="' . __('Glasses Preview', 'themoak-virtual-tryon') . '" style="max-width: 150px; max-height: 150px;" />';
                echo '<button type="button" class="button themoak-tryon-remove-image" style="margin-left: 10px;">' . __('Remove', 'themoak-virtual-tryon') . '</button>';
            }
        }
        
        echo '</span>';
        echo '</p>';
        
        echo '</div>';
        
        // Glasses Appearance Adjustments section
        echo '<div class="options_group themoak-tryon-adjustments" id="themoak_tryon_adjustments"' . 
            (get_post_meta($post->ID, '_themoak_tryon_enabled', true) !== 'yes' ? ' style="display:none;"' : '') . '>';
        
        echo '<h4 style="margin: 15px 12px; padding-top: 10px; border-top: 1px solid #eee;">' . 
            __('Glasses Appearance Adjustments', 'themoak-virtual-tryon') . '</h4>';
        echo '<p style="margin: 0 12px 15px;">' . 
            __('Customize how the glasses appear on the user\'s face. Leave empty to use global default settings.', 'themoak-virtual-tryon') . '</p>';
        
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_position_y', true),
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_position_x', true),
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_size_scale', true),
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_reflection_pos', true),
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_reflection_size', true),
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_reflection_opacity', true),
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_shadow_opacity', true),
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
                'value'             => get_post_meta($post->ID, '_themoak_tryon_shadow_offset', true),
            )
        );
        
        echo '</div>';
        
        // Add script for image upload and adjustment fields visibility
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Image upload
                var file_frame;
                $('.themoak-tryon-upload-image').on('click', function(e) {
                    e.preventDefault();
                    
                    // If the media frame already exists, reopen it
                    if (file_frame) {
                        file_frame.open();
                        return;
                    }
                    
                    // Create the media frame
                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: '<?php _e('Select or Upload Glasses Image', 'themoak-virtual-tryon'); ?>',
                        button: {
                            text: '<?php _e('Use this image', 'themoak-virtual-tryon'); ?>'
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });
                    
                    // When an image is selected, run a callback
                    file_frame.on('select', function() {
                        var attachment = file_frame.state().get('selection').first().toJSON();
                        
                        // Check if PNG
                        if (attachment.subtype !== 'png') {
                            alert('<?php _e('Please select a PNG image with transparency for best results.', 'themoak-virtual-tryon'); ?>');
                            return;
                        }
                        
                        $('#_themoak_tryon_glasses_image').val(attachment.url);
                        $('<input>').attr({
                            type: 'hidden',
                            id: '_themoak_tryon_glasses_image_id',
                            name: '_themoak_tryon_glasses_image_id',
                            value: attachment.id
                        }).appendTo('#_themoak_tryon_glasses_image').parent();
                        
                        // Update preview
                        $('#themoak-tryon-image-preview').html('<img src="' + attachment.url + '" alt="Glasses Preview" style="max-width: 150px; max-height: 150px;" /><button type="button" class="button themoak-tryon-remove-image" style="margin-left: 10px;"><?php _e('Remove', 'themoak-virtual-tryon'); ?></button>');
                    });
                    
                    // Open the modal
                    file_frame.open();
                });
                
                // Remove image
                $(document).on('click', '.themoak-tryon-remove-image', function(e) {
                    e.preventDefault();
                    $('#_themoak_tryon_glasses_image').val('');
                    $('#_themoak_tryon_glasses_image_id').remove();
                    $('#themoak-tryon-image-preview').html('');
                });
                
                // Show/hide adjustment fields based on try-on enabled checkbox
                $('#_themoak_tryon_enabled').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#themoak_tryon_adjustments').show();
                    } else {
                        $('#themoak_tryon_adjustments').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Save product options
     */
    public function save_product_options($post_id) {
        // Save enable/disable option
        $enable_tryon = isset($_POST['_themoak_tryon_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_themoak_tryon_enabled', $enable_tryon);
        
        // Save glasses image URL
        if (isset($_POST['_themoak_tryon_glasses_image'])) {
            update_post_meta($post_id, '_themoak_tryon_glasses_image', sanitize_text_field($_POST['_themoak_tryon_glasses_image']));
        }
        
        // Save glasses image ID
        if (isset($_POST['_themoak_tryon_glasses_image_id'])) {
            update_post_meta($post_id, '_themoak_tryon_glasses_image_id', absint($_POST['_themoak_tryon_glasses_image_id']));
        }
        
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
     * AJAX enable product
     */
    public function ajax_enable_product() {
        // Check security
        check_ajax_referer('themoak-tryon-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get product ID
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Update product meta
        update_post_meta($product_id, '_themoak_tryon_enabled', 'yes');
        
        wp_send_json_success(array(
            'message' => __('Virtual try-on enabled for this product.', 'themoak-virtual-tryon')
        ));
        exit;
    }

   /**
     * AJAX disable product
     */
    public function ajax_disable_product() {
        // Check security
        check_ajax_referer('themoak-tryon-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get product ID
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Update product meta
        update_post_meta($product_id, '_themoak_tryon_enabled', 'no');
        
        wp_send_json_success(array(
            'message' => __('Virtual try-on disabled for this product.', 'themoak-virtual-tryon')
        ));
        exit;
    }

    /**
     * AJAX update glasses image
     */
    public function ajax_update_glasses_image() {
        // Check security
        check_ajax_referer('themoak-tryon-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get data
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $image_id = isset($_POST['image_id']) ? absint($_POST['image_id']) : 0;
        $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Update product meta
        update_post_meta($product_id, '_themoak_tryon_glasses_image_id', $image_id);
        update_post_meta($product_id, '_themoak_tryon_glasses_image', $image_url);
        
        wp_send_json_success(array(
            'message' => __('Glasses image updated.', 'themoak-virtual-tryon')
        ));
        exit;
    }

    /**
     * AJAX handler for getting product adjustments
     */
    public function ajax_get_product_adjustments() {
        // Check security
        check_ajax_referer('themoak-tryon-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get product ID
        $product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get current adjustment values with proper defaults
        $adjustments = array(
            'position_y' => get_post_meta($product_id, '_themoak_tryon_position_y', true) ?: '-4',
            'position_x' => get_post_meta($product_id, '_themoak_tryon_position_x', true) ?: '-2',
            'size_scale' => get_post_meta($product_id, '_themoak_tryon_size_scale', true) ?: '0.9',
            'reflection_pos' => get_post_meta($product_id, '_themoak_tryon_reflection_pos', true) ?: '8',
            'reflection_size' => get_post_meta($product_id, '_themoak_tryon_reflection_size', true) ?: '0.5',
            'reflection_opacity' => get_post_meta($product_id, '_themoak_tryon_reflection_opacity', true) ?: '0.7',
            'shadow_opacity' => get_post_meta($product_id, '_themoak_tryon_shadow_opacity', true) ?: '0.4',
            'shadow_offset' => get_post_meta($product_id, '_themoak_tryon_shadow_offset', true) ?: '10',
        );
        
        wp_send_json_success($adjustments);
        exit;
    }

    /**
     * AJAX handler for saving product adjustments
     */
    public function ajax_save_product_adjustments() {
        // Check security
        if (!isset($_POST['themoak_adjustment_nonce']) || !wp_verify_nonce($_POST['themoak_adjustment_nonce'], 'themoak_save_adjustments')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get product ID
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Debug output
        error_log('Saving product adjustments for product ID: ' . $product_id);
        error_log('POST data: ' . print_r($_POST, true));
        
        // Save adjustment settings
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
                
                // Debug output
                error_log("Setting $field to $value");
                
                update_post_meta($product_id, $field, $value);
            } else {
                // Delete the meta if it's empty (to use default)
                delete_post_meta($product_id, $field);
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'themoak-virtual-tryon')
        ));
        exit;
    }

    /**
     * Get all products enabled for try-on
     *
     * @return array Array of product data
     */
    public static function get_enabled_products() {
        $products = array();
        
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_themoak_tryon_enabled',
                    'value'   => 'yes',
                    'compare' => '=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);
                
                if (!$product) {
                    continue;
                }
                
                $image_id = get_post_meta($product_id, '_themoak_tryon_glasses_image_id', true);
                $image_url = get_post_meta($product_id, '_themoak_tryon_glasses_image', true);
                
                $products[] = array(
                    'id'        => $product_id,
                    'name'      => $product->get_name(),
                    'image_id'  => $image_id,
                    'image_url' => $image_url,
                );
            }
        }
        
        wp_reset_postdata();
        
        return $products;
    }
}

// Initialize the products class
new TheMoak_Tryon_Products();
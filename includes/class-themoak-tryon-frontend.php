<?php
/**
 * TheMoak Virtual Try-on Frontend Class
 *
 * Handles the frontend functionality for the plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TheMoak_Tryon_Frontend Class
 */
class TheMoak_Tryon_Frontend {

    /**
     * Settings options array
     *
     * @var array
     */
    private $settings = array();

    /**
     * Constructor
     */
    public function __construct() {
        // Get settings
        $this->settings = get_option('themoak_tryon_settings', array());

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register shortcodes
        add_shortcode('themoak_tryon', array($this, 'tryon_button_shortcode'));
        
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add button to product page
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_tryon_button_to_product'));
        
        // AJAX handler for getting product glasses data
        add_action('wp_ajax_themoak_get_glasses_data', array($this, 'ajax_get_glasses_data'));
        add_action('wp_ajax_nopriv_themoak_get_glasses_data', array($this, 'ajax_get_glasses_data'));
        
        // Add popup HTML to footer
        add_action('wp_footer', array($this, 'add_tryon_popup'));
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Main styles
        wp_enqueue_style(
            'themoak-tryon-frontend',
            THEMOAK_TRYON_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            THEMOAK_TRYON_VERSION
        );
        
        // Main scripts
        wp_enqueue_script(
            'themoak-tryon-frontend',
            THEMOAK_TRYON_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            THEMOAK_TRYON_VERSION,
            true
        );
        
        // Product settings extension
        wp_enqueue_script(
            'themoak-tryon-frontend-settings',
            THEMOAK_TRYON_PLUGIN_URL . 'assets/js/frontend-settings.js',
            array('jquery', 'themoak-tryon-frontend'),
            THEMOAK_TRYON_VERSION,
            true
        );
        
        // Face mesh scripts (only load when needed)
        if (is_product() || has_shortcode(get_the_content(), 'themoak_tryon')) {
            wp_enqueue_script(
                'mediapipe-facemesh',
                'https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js',
                array(),
                THEMOAK_TRYON_VERSION,
                true
            );
            
            wp_enqueue_script(
                'mediapipe-camera-utils',
                'https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js',
                array(),
                THEMOAK_TRYON_VERSION,
                true
            );
        }
        
        // Localize script with data
        wp_localize_script('themoak-tryon-frontend', 'themoak_tryon_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('themoak-tryon-frontend-nonce'),
            'settings' => $this->get_frontend_settings(),
        ));
    }

    /**
     * Get frontend settings
     */
    private function get_frontend_settings() {
        return array(
            'button_text' => isset($this->settings['button_text']) ? $this->settings['button_text'] : __('Virtual Try-on', 'themoak-virtual-tryon'),
            'button_icon' => isset($this->settings['button_icon']) ? $this->settings['button_icon'] : 'dashicons-visibility',
            'instruction_1' => isset($this->settings['instruction_1']) ? $this->settings['instruction_1'] : __('Position your face in the center of the screen', 'themoak-virtual-tryon'),
            'instruction_2' => isset($this->settings['instruction_2']) ? $this->settings['instruction_2'] : __('Move slightly closer for better fit', 'themoak-virtual-tryon'),
            'instruction_3' => isset($this->settings['instruction_3']) ? $this->settings['instruction_3'] : __('Turn your head slowly to each side', 'themoak-virtual-tryon'),
            'loading_text' => isset($this->settings['loading_text']) ? $this->settings['loading_text'] : __('Loading face detection...', 'themoak-virtual-tryon'),
            'error_message' => isset($this->settings['error_message']) ? $this->settings['error_message'] : __('Could not access webcam. Please ensure you\'ve granted camera permissions.', 'themoak-virtual-tryon'),
            'background_color' => isset($this->settings['background_color']) ? $this->settings['background_color'] : '#f5f5f5',
            'enable_glassmorphism' => isset($this->settings['enable_glassmorphism']) ? $this->settings['enable_glassmorphism'] : 'yes',
            'optimized_settings' => array(
                'positionY' => -4,
                'positionX' => -2,
                'sizeScale' => 0.9,
                'reflectionPos' => 8,
                'reflectionSize' => 0.5,
                'reflectionOpacity' => 0.7,
                'shadowOpacity' => 0.4,
                'shadowOffset' => 10
            )
        );
    }

    /**
     * Try-on button shortcode
     */
    public function tryon_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
        ), $atts, 'themoak_tryon');
        
        $product_id = absint($atts['product_id']);
        
        // If no product ID provided, get current product
        if (!$product_id && is_product()) {
            global $product;
            $product_id = $product->get_id();
        }
        
        if (!$product_id) {
            return '';
        }
        
        // Check if try-on is enabled for this product
        $enabled = get_post_meta($product_id, '_themoak_tryon_enabled', true);
        
        if ($enabled !== 'yes') {
            return '';
        }
        
        // Check if glasses image exists
        $image_url = get_post_meta($product_id, '_themoak_tryon_glasses_image', true);
        
        if (empty($image_url)) {
            return '';
        }
        
        // Get button text and icon
        $button_text = isset($this->settings['button_text']) ? $this->settings['button_text'] : __('Virtual Try-on', 'themoak-virtual-tryon');
        $button_icon = isset($this->settings['button_icon']) ? $this->settings['button_icon'] : 'dashicons-visibility';
        
        // Build button HTML
        $button_html = '<button type="button" class="themoak-tryon-button" data-product-id="' . esc_attr($product_id) . '">';
        
        // Add icon only if not 'none'
        if ($button_icon !== 'none') {
            $button_html .= '<span class="dashicons ' . esc_attr($button_icon) . '"></span>';
        }
        
        $button_html .= '<span class="themoak-tryon-button-text">' . esc_html($button_text) . '</span>';
        $button_html .= '</button>';
        
        return $button_html;
    }

    /**
     * Add try-on button to product page
     */
    public function add_tryon_button_to_product() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // Check if try-on is enabled for this product
        $enabled = get_post_meta($product_id, '_themoak_tryon_enabled', true);
        
        if ($enabled !== 'yes') {
            return;
        }
        
        // Check if glasses image exists
        $image_url = get_post_meta($product_id, '_themoak_tryon_glasses_image', true);
        
        if (empty($image_url)) {
            return;
        }
        
        // Get button text and icon
        $button_text = isset($this->settings['button_text']) ? $this->settings['button_text'] : __('Virtual Try-on', 'themoak-virtual-tryon');
        $button_icon = isset($this->settings['button_icon']) ? $this->settings['button_icon'] : 'dashicons-visibility';
        
        // Output button HTML
        echo '<div class="themoak-tryon-button-container">';
        echo '<button type="button" class="themoak-tryon-button" data-product-id="' . esc_attr($product_id) . '">';
        
        // Add icon only if not 'none'
        if ($button_icon !== 'none') {
            echo '<span class="dashicons ' . esc_attr($button_icon) . '"></span>';
        }
        
        echo '<span class="themoak-tryon-button-text">' . esc_html($button_text) . '</span>';
        echo '</button>';
        echo '</div>';
    }

    /**
     * AJAX handler for getting glasses data
     */
    public function ajax_get_glasses_data() {
        // Check nonce
        check_ajax_referer('themoak-tryon-frontend-nonce', 'nonce');
        
        // Get product ID
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Check if try-on is enabled for this product
        $enabled = get_post_meta($product_id, '_themoak_tryon_enabled', true);
        
        if ($enabled !== 'yes') {
            wp_send_json_error(array('message' => __('Virtual try-on is not enabled for this product.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get glasses image URL
        $image_url = get_post_meta($product_id, '_themoak_tryon_glasses_image', true);
        
        if (empty($image_url)) {
            wp_send_json_error(array('message' => __('No glasses image found for this product.', 'themoak-virtual-tryon')));
            exit;
        }
        
        // Get product data
        $product = wc_get_product($product_id);
        $product_name = $product ? $product->get_name() : '';
        
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
        
        wp_send_json_success(array(
            'product_id' => $product_id,
            'product_name' => $product_name,
            'image_url' => $image_url,
            'settings' => $product_settings
        ));
        exit;
    }

    /**
     * Helper function to get product meta value with default fallback
     */
    private function get_product_meta_value($product_id, $meta_key, $default_value) {
        $value = get_post_meta($product_id, $meta_key, true);
        return $value !== '' ? $value : $default_value;
    }

    /**
     * Add try-on popup to footer
     */
    public function add_tryon_popup() {
        // Get settings
        $background_color = isset($this->settings['background_color']) ? $this->settings['background_color'] : '#f5f5f5';
        $enable_glassmorphism = isset($this->settings['enable_glassmorphism']) ? $this->settings['enable_glassmorphism'] : 'yes';
        $loading_text = isset($this->settings['loading_text']) ? $this->settings['loading_text'] : __('Loading face detection...', 'themoak-virtual-tryon');
        $instruction_1 = isset($this->settings['instruction_1']) ? $this->settings['instruction_1'] : __('Position your face in the center of the screen', 'themoak-virtual-tryon');
        
        // Glassmorphism class
        $glassmorphism_class = $enable_glassmorphism === 'yes' ? 'glassmorphism' : '';
        
        // Popup HTML
        ?>
        <div id="themoak-tryon-popup" class="themoak-tryon-popup <?php echo esc_attr($glassmorphism_class); ?>" style="display: none;">
            <div class="themoak-tryon-popup-inner" style="background-color: <?php echo esc_attr($background_color); ?>;">
                <button type="button" class="themoak-tryon-close">&times;</button>
                
                <div class="themoak-tryon-header">
                    <h3 class="themoak-tryon-title"><?php _e('Virtual Try-on', 'themoak-virtual-tryon'); ?></h3>
                    <div class="themoak-tryon-product-name"></div>
                </div>
                
                <div class="themoak-tryon-content">
                    <div class="themoak-tryon-loading">
                        <div class="themoak-tryon-spinner"></div>
                        <p><?php echo esc_html($loading_text); ?></p>
                    </div>
                    
                    <div class="themoak-tryon-webcam-container">
                        <video id="themoak-tryon-webcam" autoplay playsinline muted></video>
                        <canvas id="themoak-tryon-overlay"></canvas>
                        
                        <div class="themoak-tryon-instructions">
                            <div class="themoak-tryon-instruction-text">
                                <?php echo esc_html($instruction_1); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the frontend class
new TheMoak_Tryon_Frontend();
<?php
/**
 * TheMoak Virtual Try-on Settings Class
 *
 * Handles the admin settings page for the plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TheMoak_Tryon_Settings Class
 */
class TheMoak_Tryon_Settings {

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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu item
        add_menu_page(
            __('TheMoak Virtual Try-on', 'themoak-virtual-tryon'),
            __('Virtual Try-on', 'themoak-virtual-tryon'),
            'manage_options',
            'themoak-virtual-tryon',
            array($this, 'settings_page'),
            'dashicons-visibility',
            56
        );

        // Settings submenu
        add_submenu_page(
            'themoak-virtual-tryon',
            __('Settings', 'themoak-virtual-tryon'),
            __('Settings', 'themoak-virtual-tryon'),
            'manage_options',
            'themoak-virtual-tryon-settings',
            array($this, 'settings_page')
        );

        // Products submenu
        add_submenu_page(
            'themoak-virtual-tryon',
            __('Products', 'themoak-virtual-tryon'),
            __('Products', 'themoak-virtual-tryon'),
            'manage_options',
            'themoak-virtual-tryon-products',
            array($this, 'products_page')
        );

        // Remove duplicate first submenu
        remove_submenu_page('themoak-virtual-tryon', 'themoak-virtual-tryon');
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'themoak_tryon_settings',
            'themoak_tryon_settings',
            array($this, 'sanitize_settings')
        );

        // General Settings Section
        add_settings_section(
            'themoak_tryon_general_section',
            __('General Settings', 'themoak-virtual-tryon'),
            array($this, 'general_section_callback'),
            'themoak-virtual-tryon-settings'
        );

        // Button Text Field
        add_settings_field(
            'button_text',
            __('Button Text', 'themoak-virtual-tryon'),
            array($this, 'button_text_callback'),
            'themoak-virtual-tryon-settings',
            'themoak_tryon_general_section'
        );

        // Button Icon Field
        add_settings_field(
            'button_icon',
            __('Button Icon', 'themoak-virtual-tryon'),
            array($this, 'button_icon_callback'),
            'themoak-virtual-tryon-settings',
            'themoak_tryon_general_section'
        );

        // Instruction Text Fields
        add_settings_field(
            'instructions',
            __('Instructions Text', 'themoak-virtual-tryon'),
            array($this, 'instructions_callback'),
            'themoak-virtual-tryon-settings',
            'themoak_tryon_general_section'
        );

        // Loading Text Field
        add_settings_field(
            'loading_text',
            __('Loading Text', 'themoak-virtual-tryon'),
            array($this, 'loading_text_callback'),
            'themoak-virtual-tryon-settings',
            'themoak_tryon_general_section'
        );

        // Error Message Field
        add_settings_field(
            'error_message',
            __('Error Message', 'themoak-virtual-tryon'),
            array($this, 'error_message_callback'),
            'themoak-virtual-tryon-settings',
            'themoak_tryon_general_section'
        );

        // Display Settings Section
        add_settings_section(
            'themoak_tryon_display_section',
            __('Display Settings', 'themoak-virtual-tryon'),
            array($this, 'display_section_callback'),
            'themoak-virtual-tryon-settings'
        );

        // Background Color Field
        add_settings_field(
            'background_color',
            __('Background Color', 'themoak-virtual-tryon'),
            array($this, 'background_color_callback'),
            'themoak-virtual-tryon-settings',
            'themoak_tryon_display_section'
        );

        // Glassmorphism Effect Field
        add_settings_field(
            'enable_glassmorphism',
            __('Glassmorphism Effect', 'themoak-virtual-tryon'),
            array($this, 'enable_glassmorphism_callback'),
            'themoak-virtual-tryon-settings',
            'themoak_tryon_display_section'
        );

        // Shortcode Information Section
        add_settings_section(
            'themoak_tryon_shortcode_section',
            __('Shortcode Information', 'themoak-virtual-tryon'),
            array($this, 'shortcode_section_callback'),
            'themoak-virtual-tryon-settings'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();

        // Sanitize text fields
        if (isset($input['button_text'])) {
            $sanitized_input['button_text'] = sanitize_text_field($input['button_text']);
        }

        if (isset($input['button_icon'])) {
            $sanitized_input['button_icon'] = sanitize_text_field($input['button_icon']);
        }

        if (isset($input['instruction_1'])) {
            $sanitized_input['instruction_1'] = sanitize_text_field($input['instruction_1']);
        }

        if (isset($input['instruction_2'])) {
            $sanitized_input['instruction_2'] = sanitize_text_field($input['instruction_2']);
        }

        if (isset($input['instruction_3'])) {
            $sanitized_input['instruction_3'] = sanitize_text_field($input['instruction_3']);
        }

        if (isset($input['loading_text'])) {
            $sanitized_input['loading_text'] = sanitize_text_field($input['loading_text']);
        }

        if (isset($input['error_message'])) {
            $sanitized_input['error_message'] = sanitize_text_field($input['error_message']);
        }

        // Sanitize color
        if (isset($input['background_color'])) {
            $sanitized_input['background_color'] = sanitize_hex_color($input['background_color']);
        }

        // Sanitize checkbox
        $sanitized_input['enable_glassmorphism'] = isset($input['enable_glassmorphism']) ? 'yes' : 'no';

        return $sanitized_input;
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Customize the text and appearance of the virtual try-on button and interface.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Button text callback
     */
    public function button_text_callback() {
        $button_text = isset($this->settings['button_text']) ? $this->settings['button_text'] : '';
        echo '<input type="text" id="button_text" name="themoak_tryon_settings[button_text]" value="' . esc_attr($button_text) . '" class="regular-text" />';
        echo '<p class="description">' . __('The text to display on the try-on button.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Button icon callback
     */
    public function button_icon_callback() {
        $button_icon = isset($this->settings['button_icon']) ? $this->settings['button_icon'] : 'dashicons-visibility';
        
        // Common dashicons for selection
        $dashicons = array(
            'none' => __('No Icon', 'themoak-virtual-tryon'),
            'dashicons-visibility' => __('Eye', 'themoak-virtual-tryon'),
            'dashicons-camera' => __('Camera', 'themoak-virtual-tryon'),
            'dashicons-admin-appearance' => __('Appearance', 'themoak-virtual-tryon'),
            'dashicons-admin-customizer' => __('Customizer', 'themoak-virtual-tryon'),
            'dashicons-image-filter' => __('Filter', 'themoak-virtual-tryon'),
            'dashicons-admin-generic' => __('Settings', 'themoak-virtual-tryon'),
            'dashicons-smartphone' => __('Smartphone', 'themoak-virtual-tryon'),
            'dashicons-testimonial' => __('User', 'themoak-virtual-tryon'),
        );
        
        echo '<select id="button_icon" name="themoak_tryon_settings[button_icon]">';
        
        foreach ($dashicons as $icon => $label) {
            echo '<option value="' . esc_attr($icon) . '" ' . selected($button_icon, $icon, false) . '>';
            if ($icon !== 'none') {
                echo '<span class="dashicons ' . esc_attr($icon) . '"></span> ';
            }
            echo esc_html($label);
            echo '</option>';
        }
        
        echo '</select>';
        
        echo '<div class="icon-preview" style="margin-top: 10px;">';
        if ($button_icon !== 'none') {
            echo '<span class="dashicons ' . esc_attr($button_icon) . '" style="font-size: 24px;"></span>';
        }
        echo '<span style="margin-left: 10px;">' . __('Preview', 'themoak-virtual-tryon') . '</span>';
        echo '</div>';
        
        echo '<p class="description">' . __('Select an icon to display next to the button text, or "No Icon" to display text only.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Instructions callback
     */
    public function instructions_callback() {
        $instruction_1 = isset($this->settings['instruction_1']) ? $this->settings['instruction_1'] : '';
        $instruction_2 = isset($this->settings['instruction_2']) ? $this->settings['instruction_2'] : '';
        $instruction_3 = isset($this->settings['instruction_3']) ? $this->settings['instruction_3'] : '';
        
        echo '<div class="instruction-fields">';
        
        echo '<div class="instruction-field">';
        echo '<label>' . __('Instruction 1:', 'themoak-virtual-tryon') . '</label>';
        echo '<input type="text" id="instruction_1" name="themoak_tryon_settings[instruction_1]" value="' . esc_attr($instruction_1) . '" class="regular-text" />';
        echo '</div>';
        
        echo '<div class="instruction-field">';
        echo '<label>' . __('Instruction 2:', 'themoak-virtual-tryon') . '</label>';
        echo '<input type="text" id="instruction_2" name="themoak_tryon_settings[instruction_2]" value="' . esc_attr($instruction_2) . '" class="regular-text" />';
        echo '</div>';
        
        echo '<div class="instruction-field">';
        echo '<label>' . __('Instruction 3:', 'themoak-virtual-tryon') . '</label>';
        echo '<input type="text" id="instruction_3" name="themoak_tryon_settings[instruction_3]" value="' . esc_attr($instruction_3) . '" class="regular-text" />';
        echo '</div>';
        
        echo '</div>';
        
        echo '<p class="description">' . __('The instructions displayed to users during the try-on experience.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Loading text callback
     */
    public function loading_text_callback() {
        $loading_text = isset($this->settings['loading_text']) ? $this->settings['loading_text'] : '';
        echo '<input type="text" id="loading_text" name="themoak_tryon_settings[loading_text]" value="' . esc_attr($loading_text) . '" class="regular-text" />';
        echo '<p class="description">' . __('The text displayed while the face detection is loading.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Error message callback
     */
    public function error_message_callback() {
        $error_message = isset($this->settings['error_message']) ? $this->settings['error_message'] : '';
        echo '<input type="text" id="error_message" name="themoak_tryon_settings[error_message]" value="' . esc_attr($error_message) . '" class="regular-text" />';
        echo '<p class="description">' . __('The error message displayed when camera access is denied.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Display section callback
     */
    public function display_section_callback() {
        echo '<p>' . __('Configure the visual appearance of the try-on popup.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Background color callback
     */
    public function background_color_callback() {
        $background_color = isset($this->settings['background_color']) ? $this->settings['background_color'] : '#f5f5f5';
        echo '<input type="text" id="background_color" name="themoak_tryon_settings[background_color]" value="' . esc_attr($background_color) . '" class="color-picker" />';
        echo '<p class="description">' . __('Background color for the try-on popup (default: light grey).', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Enable glassmorphism callback
     */
    public function enable_glassmorphism_callback() {
        $enable_glassmorphism = isset($this->settings['enable_glassmorphism']) ? $this->settings['enable_glassmorphism'] : 'yes';
        echo '<label for="enable_glassmorphism">';
        echo '<input type="checkbox" id="enable_glassmorphism" name="themoak_tryon_settings[enable_glassmorphism]" value="yes" ' . checked('yes', $enable_glassmorphism, false) . ' />';
        echo __('Enable glassmorphism effect for the popup', 'themoak-virtual-tryon');
        echo '</label>';
        echo '<p class="description">' . __('Adds a modern glass-like effect to the try-on popup.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Shortcode section callback
     */
    public function shortcode_section_callback() {
        echo '<p>' . __('Use the following shortcodes to add the virtual try-on button to your site:', 'themoak-virtual-tryon') . '</p>';
        echo '<code>[themoak_tryon]</code> - ' . __('Displays the try-on button for the current product.', 'themoak-virtual-tryon') . '<br>';
        echo '<code>[themoak_tryon product_id="123"]</code> - ' . __('Displays the try-on button for a specific product.', 'themoak-virtual-tryon') . '<br>';
        echo '<p>' . __('Note: The try-on button will only appear for products that have try-on enabled in the Products tab.', 'themoak-virtual-tryon') . '</p>';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('themoak_tryon_settings');
                do_settings_sections('themoak-virtual-tryon-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Products page
     */
    public function products_page() {
        // This is just a placeholder. The actual implementation is in the Products class
        require_once THEMOAK_TRYON_PLUGIN_DIR . 'admin/products-page.php';
    }
}

// Initialize the settings class
new TheMoak_Tryon_Settings();
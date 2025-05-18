<?php
/**
 * Plugin Name: TheMoak Virtual Try-on
 * Plugin URI: https://themoak.com/virtual-tryon
 * Description: A virtual try-on solution for WooCommerce products to allow customers to see how eyewear looks on their face using webcam.
 * Version: 1.0.0
 * Author: TheMoak
 * Author URI: https://themoak.com
 * Text Domain: themoak-virtual-tryon
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 7.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('THEMOAK_TRYON_VERSION', '1.0.0');
define('THEMOAK_TRYON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMOAK_TRYON_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THEMOAK_TRYON_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
if (!function_exists('is_woocommerce_active')) {
    function is_woocommerce_active() {
        $active_plugins = (array) get_option('active_plugins', array());

        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }

        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }
}

/**
 * Class TheMoak_Virtual_Tryon
 */
class TheMoak_Virtual_Tryon {

    /**
     * Instance of this class
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Return an instance of this class
     *
     * @return object A single instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Check if WooCommerce is active
        if (!is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_not_active_notice'));
            return;
        }

        // Include required files
        $this->includes();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Show admin notice if WooCommerce is not active
     */
    public function woocommerce_not_active_notice() {
        ?>
        <div class="error">
            <p><?php _e('TheMoak Virtual Try-on requires WooCommerce to be installed and active.', 'themoak-virtual-tryon'); ?></p>
        </div>
        <?php
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once THEMOAK_TRYON_PLUGIN_DIR . 'includes/class-themoak-tryon-settings.php';
        require_once THEMOAK_TRYON_PLUGIN_DIR . 'includes/class-themoak-tryon-products.php';
        require_once THEMOAK_TRYON_PLUGIN_DIR . 'includes/class-themoak-tryon-frontend.php';
require_once THEMOAK_TRYON_PLUGIN_DIR . 'themoak-tryon-extensions.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Plugin activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . THEMOAK_TRYON_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
    }

    /**
     * Plugin activation function
     */
    public function activate() {
        // Create default settings
        $default_settings = array(
            'button_text' => __('Virtual Try-on', 'themoak-virtual-tryon'),
            'button_icon' => 'dashicons-visibility',
            'instruction_1' => __('Position your face in the center of the screen', 'themoak-virtual-tryon'),
            'instruction_2' => __('Move slightly closer for better fit', 'themoak-virtual-tryon'),
            'instruction_3' => __('Turn your head slowly to each side', 'themoak-virtual-tryon'),
            'loading_text' => __('Loading face detection...', 'themoak-virtual-tryon'),
            'error_message' => __('Could not access webcam. Please ensure you\'ve granted camera permissions.', 'themoak-virtual-tryon'),
            'background_color' => '#f5f5f5',
            'enable_glassmorphism' => 'yes',
        );
        
        add_option('themoak_tryon_settings', $default_settings);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('themoak-virtual-tryon', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        $screen = get_current_screen();
        
        // Only enqueue scripts on plugin settings pages
        if (strpos($hook, 'themoak-virtual-tryon') !== false) {
            // Admin styles
            wp_enqueue_style('themoak-tryon-admin', THEMOAK_TRYON_PLUGIN_URL . 'assets/css/admin.css', array(), THEMOAK_TRYON_VERSION);
            
            // WordPress media uploader
            wp_enqueue_media();
            
            // Color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Admin scripts
            wp_enqueue_script('themoak-tryon-admin', THEMOAK_TRYON_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), THEMOAK_TRYON_VERSION, true);
            
            // Localize script with data
            wp_localize_script('themoak-tryon-admin', 'themoak_tryon_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('themoak-tryon-admin-nonce'),
            ));
        }
    }

    /**
     * Add settings link on plugins page
     */
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=themoak-virtual-tryon-settings') . '">' . __('Settings', 'themoak-virtual-tryon') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
function themoak_virtual_tryon() {
    return TheMoak_Virtual_Tryon::get_instance();
}

// Start the plugin
themoak_virtual_tryon();
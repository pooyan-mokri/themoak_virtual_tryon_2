<?php
/**
 * TheMoak Virtual Try-on Products Page
 *
 * Admin interface for managing products with try-on capability
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get all products
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => -1,
);

$products_query = new WP_Query($args);
$products = array();

if ($products_query->have_posts()) {
    while ($products_query->have_posts()) {
        $products_query->the_post();
        
        $product_id = get_the_ID();
        $product = wc_get_product($product_id);
        
        if (!$product) {
            continue;
        }
        
        $enabled = get_post_meta($product_id, '_themoak_tryon_enabled', true);
        $image_id = get_post_meta($product_id, '_themoak_tryon_glasses_image_id', true);
        $image_url = get_post_meta($product_id, '_themoak_tryon_glasses_image', true);
        
        $products[] = array(
            'id'        => $product_id,
            'name'      => $product->get_name(),
            'sku'       => $product->get_sku(),
            'enabled'   => $enabled === 'yes',
            'image_id'  => $image_id,
            'image_url' => $image_url,
        );
    }
}

wp_reset_postdata();

// Sort products by enabled status (enabled first)
usort($products, function($a, $b) {
    if ($a['enabled'] === $b['enabled']) {
        return 0;
    }
    return $a['enabled'] ? -1 : 1;
});

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Enable virtual try-on for your products and upload transparent PNG images of glasses.', 'themoak-virtual-tryon'); ?></p>
    </div>
    
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'themoak-virtual-tryon'); ?></label>
            <select name="action" id="bulk-action-selector-top">
                <option value="-1"><?php _e('Bulk Actions', 'themoak-virtual-tryon'); ?></option>
                <option value="enable"><?php _e('Enable Try-on', 'themoak-virtual-tryon'); ?></option>
                <option value="disable"><?php _e('Disable Try-on', 'themoak-virtual-tryon'); ?></option>
            </select>
            <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'themoak-virtual-tryon'); ?>">
        </div>
        <div class="alignright">
            <input type="text" id="product-search" class="regular-text" placeholder="<?php _e('Search products...', 'themoak-virtual-tryon'); ?>">
        </div>
        
        <br class="clear">
    </div>
    
    <table class="wp-list-table widefat fixed striped themoak-tryon-products-table">
        <thead>
            <tr>
                <th class="check-column"><input type="checkbox" id="cb-select-all"></th>
                <th class="column-actions"><?php _e('Actions', 'themoak-virtual-tryon'); ?></th>
                <th class="column-image"><?php _e('Glasses Image', 'themoak-virtual-tryon'); ?></th>
                <th class="column-enabled"><?php _e('Try-on Enabled', 'themoak-virtual-tryon'); ?></th>
                <th class="column-sku"><?php _e('SKU', 'themoak-virtual-tryon'); ?></th>
                <th class="column-name"><?php _e('Product', 'themoak-virtual-tryon'); ?></th>
            </tr>
        </thead>
        
        <tbody id="the-list">
            <?php if (empty($products)) : ?>
                <tr>
                    <td colspan="6"><?php _e('No products found.', 'themoak-virtual-tryon'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($products as $product) : ?>
                    <tr data-id="<?php echo esc_attr($product['id']); ?>" class="<?php echo $product['enabled'] ? 'tryon-enabled' : 'tryon-disabled'; ?>">
                        <td class="check-column">
                            <input type="checkbox" name="product[]" value="<?php echo esc_attr($product['id']); ?>">
                        </td>
                        
                        <td class="column-actions">
                            <?php if (!empty($product['image_url'])) : ?>
                                <button type="button" class="button remove-glasses-image" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                    <?php _e('Remove', 'themoak-virtual-tryon'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="button upload-glasses-image" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                <?php echo empty($product['image_url']) ? __('Upload Image', 'themoak-virtual-tryon') : __('Change Image', 'themoak-virtual-tryon'); ?>
                            </button>
                            
                            <?php if ($product['enabled']) : ?>
                                <button type="button" class="button adjust-glasses-settings" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                    <?php _e('Adjust', 'themoak-virtual-tryon'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                        
                        <td class="column-image">
                            <?php if (!empty($product['image_url'])) : ?>
                                <img src="<?php echo esc_url($product['image_url']); ?>" alt="<?php echo esc_attr($product['name']); ?>" class="tryon-glasses-image">
                            <?php else : ?>
                                <span class="no-image"><?php _e('No image', 'themoak-virtual-tryon'); ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="column-enabled">
                            <div class="themoak-tryon-toggle-wrap">
                                <label class="themoak-tryon-toggle">
                                    <input type="checkbox" class="tryon-toggle" <?php checked($product['enabled'], true); ?> data-product-id="<?php echo esc_attr($product['id']); ?>">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </td>
                        
                        <td class="column-sku">
                            <?php echo esc_html($product['sku']); ?>
                        </td>
                        
                        <td class="column-name">
                            <strong>
                                <a href="<?php echo esc_url(get_edit_post_link($product['id'])); ?>" target="_blank">
                                    <?php echo esc_html($product['name']); ?>
                                </a>
                            </strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Adjustment Modal -->
<div id="themoak-adjustment-modal" style="display:none;" class="themoak-modal">
    <div class="themoak-modal-content">
        <span class="themoak-modal-close">&times;</span>
        <h2><?php _e('Glasses Appearance Adjustments', 'themoak-virtual-tryon'); ?></h2>
        <p><?php _e('Customize how the glasses appear on the user\'s face. Leave empty to use global default settings.', 'themoak-virtual-tryon'); ?></p>
        
        <form id="themoak-adjustments-form" method="post">
            <input type="hidden" id="adjustment_product_id" name="product_id" value="">
            <?php wp_nonce_field('themoak_save_adjustments', 'themoak_adjustment_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="position_y"><?php _e('Vertical Position (Y)', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="position_y" name="_themoak_tryon_position_y" step="1" min="-50" max="50" placeholder="-4">
                        <p class="description"><?php _e('Negative values move up, positive values move down.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="position_x"><?php _e('Horizontal Position (X)', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="position_x" name="_themoak_tryon_position_x" step="1" min="-50" max="50" placeholder="-2">
                        <p class="description"><?php _e('Negative values move left, positive values move right.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="size_scale"><?php _e('Size Scale', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="size_scale" name="_themoak_tryon_size_scale" step="0.05" min="0.5" max="1.5" placeholder="0.9">
                        <p class="description"><?php _e('Values below 1.0 make smaller, above 1.0 make larger.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="reflection_pos"><?php _e('Reflection Position', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="reflection_pos" name="_themoak_tryon_reflection_pos" step="1" min="-20" max="20" placeholder="8">
                        <p class="description"><?php _e('Higher values move reflections down.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="reflection_size"><?php _e('Reflection Size', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="reflection_size" name="_themoak_tryon_reflection_size" step="0.05" min="0.2" max="1.5" placeholder="0.5">
                        <p class="description"><?php _e('Values below 1.0 make smaller, above 1.0 make larger.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="reflection_opacity"><?php _e('Reflection Opacity', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="reflection_opacity" name="_themoak_tryon_reflection_opacity" step="0.1" min="0" max="2" placeholder="0.7">
                        <p class="description"><?php _e('Values below 1.0 make more transparent, above 1.0 more visible.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="shadow_opacity"><?php _e('Shadow Opacity', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="shadow_opacity" name="_themoak_tryon_shadow_opacity" step="0.05" min="0" max="1" placeholder="0.4">
                        <p class="description"><?php _e('Higher values make shadow darker.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="shadow_offset"><?php _e('Shadow Offset', 'themoak-virtual-tryon'); ?></label></th>
                    <td>
                        <input type="number" id="shadow_offset" name="_themoak_tryon_shadow_offset" step="1" min="0" max="30" placeholder="10">
                        <p class="description"><?php _e('Higher values move shadow down further.', 'themoak-virtual-tryon'); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="submit-buttons">
                <button type="submit" class="button button-primary"><?php _e('Save Settings', 'themoak-virtual-tryon'); ?></button>
                <button type="button" class="button themoak-reset-defaults"><?php _e('Reset to Defaults', 'themoak-virtual-tryon'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
    .themoak-tryon-products-table .column-image {
        width: 150px;
    }
    
    .themoak-tryon-products-table .column-image img {
        max-width: 100px;
        max-height: 50px;
        background: #f0f0f0;
        padding: 5px;
        border: 1px solid #ddd;
    }
    
    .themoak-tryon-products-table .column-enabled {
        width: 100px;
        text-align: center;
    }
    
    .themoak-tryon-products-table .column-actions {
        width: 200px;
    }
    
    .themoak-tryon-toggle-wrap {
        text-align: center;
    }
    
    /* Toggle Switch Styles */
    .themoak-tryon-toggle {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .themoak-tryon-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .themoak-tryon-toggle .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }
    
    .themoak-tryon-toggle .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }
    
    .themoak-tryon-toggle input:checked + .slider {
        background-color: #2196F3;
    }
    
    .themoak-tryon-toggle input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }
    
    .themoak-tryon-toggle input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .themoak-tryon-toggle .slider.round {
        border-radius: 24px;
    }
    
    .themoak-tryon-toggle .slider.round:before {
        border-radius: 50%;
    }
    
    .tryon-enabled td {
        background-color: #f8ffff;
    }
    
    .no-image {
        color: #999;
        font-style: italic;
    }
    
    /* Modal Styles */
    .themoak-modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }

    .themoak-modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .themoak-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .themoak-modal-close:hover,
    .themoak-modal-close:focus {
        color: black;
        text-decoration: none;
    }

    .submit-buttons {
        margin-top: 20px;
        text-align: right;
    }

    .submit-buttons .button {
        margin-left: 10px;
    }

    #themoak-adjustments-form .form-table th {
        width: 200px;
    }
</style>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Toggle try-on enable/disable
        $('.tryon-toggle').on('change', function() {
            const productId = $(this).data('product-id');
            const isEnabled = $(this).prop('checked');
            const row = $(this).closest('tr');
            
            const action = isEnabled ? 'themoak_tryon_enable_product' : 'themoak_tryon_disable_product';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: '<?php echo wp_create_nonce('themoak-tryon-admin-nonce'); ?>',
                    product_id: productId
                },
                beforeSend: function() {
                    row.css('opacity', '0.5');
                },
                success: function(response) {
                    row.css('opacity', '1');
                    
                    if (response.success) {
                        if (isEnabled) {
                            row.removeClass('tryon-disabled').addClass('tryon-enabled');
                            // Add adjust button if it doesn't exist
                            if (row.find('.adjust-glasses-settings').length === 0) {
                                row.find('.column-actions').append('<button type="button" class="button adjust-glasses-settings" data-product-id="' + productId + '"><?php _e('Adjust', 'themoak-virtual-tryon'); ?></button>');
                            }
                        } else {
                            row.removeClass('tryon-enabled').addClass('tryon-disabled');
                            // Remove adjust button
                            row.find('.adjust-glasses-settings').remove();
                        }
                    } else {
                        alert(response.data.message);
                        // Revert toggle state
                        $(this).prop('checked', !isEnabled);
                    }
                },
                error: function() {
                    row.css('opacity', '1');
                    alert('<?php _e('An error occurred. Please try again.', 'themoak-virtual-tryon'); ?>');
                    // Revert toggle state
                    $(this).prop('checked', !isEnabled);
                }
            });
        });
        
        // Upload glasses image
        $('.upload-glasses-image').on('click', function() {
            const productId = $(this).data('product-id');
            const row = $(this).closest('tr');
            
            // Create media frame
            const frame = wp.media({
                title: '<?php _e('Select Glasses Image (PNG)', 'themoak-virtual-tryon'); ?>',
                button: {
                    text: '<?php _e('Use this image', 'themoak-virtual-tryon'); ?>'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When image selected
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                
                // Check if PNG
                if (attachment.subtype !== 'png') {
                    alert('<?php _e('Please select a PNG image with transparency for best results.', 'themoak-virtual-tryon'); ?>');
                    return;
                }
                
                // Update product image
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'themoak_tryon_update_glasses_image',
                        nonce: '<?php echo wp_create_nonce('themoak-tryon-admin-nonce'); ?>',
                        product_id: productId,
                        image_id: attachment.id,
                        image_url: attachment.url
                    },
                    beforeSend: function() {
                        row.css('opacity', '0.5');
                    },
                    success: function(response) {
                        row.css('opacity', '1');
                        
                        if (response.success) {
                            // Update image display
                            row.find('.column-image').html('<img src="' + attachment.url + '" alt="" class="tryon-glasses-image">');
                            
                            // Update button text
                            row.find('.upload-glasses-image').text('<?php _e('Change Image', 'themoak-virtual-tryon'); ?>');
                            
                            // Add remove button if not exists
                            if (row.find('.remove-glasses-image').length === 0) {
                                row.find('.column-actions').prepend('<button type="button" class="button remove-glasses-image" data-product-id="' + productId + '"><?php _e('Remove', 'themoak-virtual-tryon'); ?></button>');
                            }
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        row.css('opacity', '1');
                        alert('<?php _e('An error occurred. Please try again.', 'themoak-virtual-tryon'); ?>');
                    }
                });
            });
            
            // Open media uploader
            frame.open();
        });
        
        // Remove glasses image
        $(document).on('click', '.remove-glasses-image', function() {
            const productId = $(this).data('product-id');
            const row = $(this).closest('tr');
            
            // Confirm removal
            if (!confirm('<?php _e('Are you sure you want to remove this image?', 'themoak-virtual-tryon'); ?>')) {
                return;
            }
            
            // Update product
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'themoak_tryon_update_glasses_image',
                    nonce: '<?php echo wp_create_nonce('themoak-tryon-admin-nonce'); ?>',
                    product_id: productId,
                    image_id: 0,
                    image_url: ''
                },
                beforeSend: function() {
                    row.css('opacity', '0.5');
                },
                success: function(response) {
                    row.css('opacity', '1');
                    
                    if (response.success) {
                        // Update image display
                        row.find('.column-image').html('<span class="no-image"><?php _e('No image', 'themoak-virtual-tryon'); ?></span>');
                        
                        // Update button text
                        row.find('.upload-glasses-image').text('<?php _e('Upload Image', 'themoak-virtual-tryon'); ?>');
                        
                        // Remove remove button
                        row.find('.remove-glasses-image').remove();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    row.css('opacity', '1');
                    alert('<?php _e('An error occurred. Please try again.', 'themoak-virtual-tryon'); ?>');
                }
            });
        });
        
        // Search functionality
        $('#product-search').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('#the-list tr').each(function() {
                const productName = $(this).find('.column-name a').text().toLowerCase();
                const productSku = $(this).find('.column-sku').text().toLowerCase();
                
                if (productName.indexOf(searchTerm) > -1 || productSku.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Select all checkboxes
        $('#cb-select-all').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('#the-list input[type="checkbox"]').prop('checked', isChecked);
        });
        
        // Bulk actions
        $('#doaction').on('click', function(e) {
            e.preventDefault();
            
            const action = $('#bulk-action-selector-top').val();
            
            if (action === '-1') {
                alert('<?php _e('Please select an action to perform.', 'themoak-virtual-tryon'); ?>');
                return;
            }
            
            const selectedProducts = $('#the-list input[type="checkbox"]:checked');
            
            if (selectedProducts.length === 0) {
                alert('<?php _e('Please select at least one product.', 'themoak-virtual-tryon'); ?>');
                return;
            }
            
            // Confirm action
            if (!confirm('<?php _e('Are you sure you want to perform this action on the selected products?', 'themoak-virtual-tryon'); ?>')) {
                return;
            }
            
            const ajaxAction = action === 'enable' ? 'themoak_tryon_enable_product' : 'themoak_tryon_disable_product';
            
            // Process each product
            selectedProducts.each(function() {
                const productId = $(this).val();
                const row = $('tr[data-id="' + productId + '"]');
                const toggleCheckbox = row.find('.tryon-toggle');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxAction,
                        nonce: '<?php echo wp_create_nonce('themoak-tryon-admin-nonce'); ?>',
                        product_id: productId
                    },
                    beforeSend: function() {
                        row.css('opacity', '0.5');
                    },
                    success: function(response) {
                        row.css('opacity', '1');
                        
                        if (response.success) {
                            if (action === 'enable') {
                                row.removeClass('tryon-disabled').addClass('tryon-enabled');
                                toggleCheckbox.prop('checked', true);
                                // Add adjust button if it doesn't exist
                                if (row.find('.adjust-glasses-settings').length === 0) {
                                    row.find('.column-actions').append('<button type="button" class="button adjust-glasses-settings" data-product-id="' + productId + '"><?php _e('Adjust', 'themoak-virtual-tryon'); ?></button>');
                                }
                            } else {
                                row.removeClass('tryon-enabled').addClass('tryon-disabled');
                                toggleCheckbox.prop('checked', false);
                                // Remove adjust button
                                row.find('.adjust-glasses-settings').remove();
                            }
                        }
                    },
                    error: function() {
                        row.css('opacity', '1');
                    }
                });
            });
        });
        
        // Get the modal
        var modal = $('#themoak-adjustment-modal');
        
        // When user clicks on Adjust button
        $(document).on('click', '.adjust-glasses-settings', function() {
            var productId = $(this).data('product-id');
            $('#adjustment_product_id').val(productId);
            
            // Load existing values via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'themoak_get_product_adjustments',
                    nonce: '<?php echo wp_create_nonce('themoak-tryon-admin-nonce'); ?>',
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        // Fill form with existing values
                        $.each(response.data, function(key, value) {
                            $('#' + key).val(value);
                        });
                        
                        // Show the modal
                        modal.fadeIn(300);
                    }
                }
            });
        });
        
        // Close the modal
        $('.themoak-modal-close').on('click', function() {
            modal.fadeOut(300);
        });
        
        // Close modal on click outside
        $(window).on('click', function(e) {
            if ($(e.target).is(modal)) {
                modal.fadeOut(300);
            }
        });
        
        // Reset to defaults
        $('.themoak-reset-defaults').on('click', function(e) {
            e.preventDefault();
            
            // Clear all inputs
            $('#themoak-adjustments-form input[type="number"]').val('');
        });
        
        // Save adjustments
        $('#themoak-adjustments-form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $(this).serialize() + '&action=themoak_save_product_adjustments',
                success: function(response) {
                    if (response.success) {
                        modal.fadeOut(300);
                        // Show success message
                        alert(response.data.message);
                        
                        // Optionally reload the page to see changes
                        // window.location.reload();
                    } else {
                        alert(response.data.message || 'An error occurred while saving.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr.responseText);
                    alert('An error occurred while saving the adjustments.');
                }
            });
        });
    });
</script>
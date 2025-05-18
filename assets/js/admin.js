/**
 * TheMoak Virtual Try-on Admin Script
 */
(function ($) {
  'use strict';

  // Initialize color picker
  $('.color-picker').wpColorPicker();

  // Button icon selection
  $('#button_icon').on('change', function () {
    const iconClass = $(this).val();
    $('.icon-preview .dashicons').attr('class', 'dashicons ' + iconClass);
  });

  // Ensure the themoak_tryon_params is defined with default values
  if (typeof themoak_tryon_params === 'undefined') {
    window.themoak_tryon_params = {
      ajax_url: ajaxurl,
      nonce: '',
      messages: {
        select_action: 'Please select an action to perform.',
        select_products: 'Please select at least one product.',
        confirm_bulk_action:
          'Are you sure you want to perform this action on the selected products?',
      },
    };
  }

  // Handle bulk actions for products
  $('#doaction').on('click', function (e) {
    e.preventDefault();

    const action = $('#bulk-action-selector-top').val();

    if (action === '-1') {
      alert(themoak_tryon_params.messages.select_action);
      return;
    }

    const selectedProducts = $('#the-list input[type="checkbox"]:checked');

    if (selectedProducts.length === 0) {
      alert(themoak_tryon_params.messages.select_products);
      return;
    }

    const productIds = [];
    selectedProducts.each(function () {
      productIds.push($(this).val());
    });

    // Confirm action
    if (!confirm(themoak_tryon_params.messages.confirm_bulk_action)) {
      return;
    }

    const ajaxAction =
      action === 'enable'
        ? 'themoak_tryon_enable_product'
        : 'themoak_tryon_disable_product';

    // Process each product
    selectedProducts.each(function () {
      const productId = $(this).val();
      const row = $('tr[data-id="' + productId + '"]');
      const toggleCheckbox = row.find('.tryon-toggle');

      $.ajax({
        url: themoak_tryon_params.ajax_url,
        type: 'POST',
        data: {
          action: ajaxAction,
          nonce: themoak_tryon_params.nonce,
          product_id: productId,
        },
        beforeSend: function () {
          row.css('opacity', '0.5');
        },
        success: function (response) {
          row.css('opacity', '1');

          if (response.success) {
            if (action === 'enable') {
              row.removeClass('tryon-disabled').addClass('tryon-enabled');
              toggleCheckbox.prop('checked', true);
              // Add adjust button if it doesn't exist
              if (row.find('.adjust-glasses-settings').length === 0) {
                row
                  .find('.column-actions')
                  .append(
                    '<button type="button" class="button adjust-glasses-settings" data-product-id="' +
                      productId +
                      '">Adjust</button>'
                  );
              }
            } else {
              row.removeClass('tryon-enabled').addClass('tryon-disabled');
              toggleCheckbox.prop('checked', false);
              // Remove adjust button
              row.find('.adjust-glasses-settings').remove();
            }
          }
        },
        error: function () {
          row.css('opacity', '1');
        },
      });
    });
  });

  // Select all checkboxes
  $('#cb-select-all').on('change', function () {
    const isChecked = $(this).prop('checked');
    $('#the-list input[type="checkbox"]').prop('checked', isChecked);
  });

  // Search functionality
  $('#product-search').on('keyup', function () {
    const searchTerm = $(this).val().toLowerCase();

    $('#the-list tr').each(function () {
      const productName = $(this).find('.column-name a').text().toLowerCase();
      const productSku = $(this).find('.column-sku').text().toLowerCase();

      if (
        productName.indexOf(searchTerm) > -1 ||
        productSku.indexOf(searchTerm) > -1
      ) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });
})(jQuery);

// Adjustment Modal functionality
jQuery(document).ready(function ($) {
  // Get the modal
  var modal = $('#themoak-adjustment-modal');

  // When user clicks on Adjust button
  $(document).on('click', '.adjust-glasses-settings', function () {
    var productId = $(this).data('product-id');
    $('#adjustment_product_id').val(productId);

    // Get the nonce from the form
    var nonce = window.themoak_tryon_params
      ? window.themoak_tryon_params.nonce
      : '';

    // Load existing values via AJAX
    $.ajax({
      url: ajaxurl,
      type: 'GET',
      data: {
        action: 'themoak_get_product_adjustments',
        product_id: productId,
        nonce: nonce,
      },
      success: function (response) {
        if (response.success) {
          // Fill form with existing values
          $.each(response.data, function (key, value) {
            $('#' + key).val(value);
          });

          // Show the modal
          modal.fadeIn(300);
        } else {
          console.error('Error loading adjustments:', response.data);
          alert('Error loading adjustment values. Please try again.');
        }
      },
      error: function (xhr, status, error) {
        console.error('AJAX error loading adjustments:', xhr.responseText);
        alert(
          'Error loading adjustment values. Please check the console for details.'
        );
      },
    });
  });

  // Close the modal
  $('.themoak-modal-close').on('click', function () {
    modal.fadeOut(300);
  });

  // Close modal on click outside
  $(window).on('click', function (e) {
    if ($(e.target).is(modal)) {
      modal.fadeOut(300);
    }
  });

  // Reset to defaults
  $('.themoak-reset-defaults').on('click', function (e) {
    e.preventDefault();

    // Clear all inputs
    $('#themoak-adjustments-form input[type="number"]').val('');
  });

  // Save adjustments with proper debugging
  $('#themoak-adjustments-form').on('submit', function (e) {
    e.preventDefault();

    // Get form data
    var formData = $(this).serialize();

    // Add action to form data
    var fullData = formData + '&action=themoak_save_product_adjustments';

    // Make AJAX request
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: fullData,
      beforeSend: function () {
        // Disable the submit button to prevent multiple submissions
        $('#themoak-adjustments-form [type="submit"]').prop('disabled', true);
      },
      success: function (response) {
        if (response.success) {
          modal.fadeOut(300);
          alert(response.data.message || 'Settings saved successfully!');

          // Re-enable the submit button
          $('#themoak-adjustments-form [type="submit"]').prop(
            'disabled',
            false
          );

          // Optionally reload the page to see the changes
          // window.location.reload();
        } else {
          console.error('Save failed:', response.data);
          alert(response.data.message || 'An error occurred while saving.');

          // Re-enable the submit button
          $('#themoak-adjustments-form [type="submit"]').prop(
            'disabled',
            false
          );
        }
      },
      error: function (xhr, status, error) {
        console.error('AJAX error:', xhr.responseText);
        console.error('Status:', status);
        console.error('Error:', error);
        alert(
          'An error occurred while saving the adjustments. Please check the console for details.'
        );

        // Re-enable the submit button
        $('#themoak-adjustments-form [type="submit"]').prop('disabled', false);
      },
    });
  });
  // ADD THE DEBUGGING CODE RIGHT HERE, before the final closing bracket
  // Debugging for adjustment modal
  $('#themoak-adjustments-form').on('submit', function () {
    console.log('Submitting adjustment form with data:');
    var formData = $(this).serialize();
    console.log(formData);

    // Log each field value
    $(this)
      .find('input[type="number"]')
      .each(function () {
        console.log($(this).attr('name') + ': ' + $(this).val());
      });
  });
});

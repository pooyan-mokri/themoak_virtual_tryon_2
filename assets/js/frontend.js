/**
 * TheMoak Virtual Try-on Frontend Script
 */
(function ($) {
  'use strict';

  // TheMoak Try-on Main Object
  const TheMoakTryOn = {
    // Settings
    settings: themoak_tryon_params.settings,

    // Elements
    $popup: null,
    $webcam: null,
    $overlay: null,
    $loading: null,
    $instructions: null,

    // State variables
    isInitialized: false,
    currentProductId: 0,
    currentProductName: '',
    currentGlassesUrl: '',
    faceMesh: null,
    camera: null,
    glassesImage: null,
    glassesShadow: null,

    // Smoothing variables
    smooth: {
      x: null,
      y: null,
      width: null,
      height: null,
      angle: null,
      depth: null,
      inFrame: false,
      lightLevel: 0.5,
    },
    smoothingFactor: 0.6,

    // Face detection variables
    faceDetectionCounter: 0,
    lastInstructionChange: 0,
    instructionState: 0,

    /**
     * Initialize the try-on functionality
     */
    init: function () {
      // Cache elements
      this.$popup = $('#themoak-tryon-popup');
      this.$webcam = $('#themoak-tryon-webcam');
      this.$overlay = $('#themoak-tryon-overlay');
      this.$loading = $('.themoak-tryon-loading');
      this.$instructions = $('.themoak-tryon-instruction-text');

      // Set up event listeners
      this.setupEventListeners();

      // Initialize variables
      this.isInitialized = false;

      // Face detection variables
      this.faceDetectionCounter = 0;
      this.lastInstructionChange = Date.now();
      this.instructionState = 0;
    },

    /**
     * Set up event listeners
     */
    setupEventListeners: function () {
      // Try-on button click
      $(document).on(
        'click',
        '.themoak-tryon-button',
        this.onTryOnButtonClick.bind(this)
      );

      // Close popup
      $('.themoak-tryon-close').on('click', this.closePopup.bind(this));

      // Close on ESC key
      $(document).on(
        'keydown',
        function (e) {
          if (e.key === 'Escape' && this.$popup.is(':visible')) {
            this.closePopup();
          }
        }.bind(this)
      );
    },

    /**
     * Handle try-on button click
     */
    onTryOnButtonClick: function (e) {
      e.preventDefault();

      const $button = $(e.currentTarget);
      const productId = $button.data('product-id');

      // Get product data
      this.getProductData(productId);
    },

    /**
     * Get product data via AJAX
     */
    getProductData: function (productId) {
      $.ajax({
        url: themoak_tryon_params.ajax_url,
        type: 'POST',
        data: {
          action: 'themoak_get_glasses_data',
          nonce: themoak_tryon_params.nonce,
          product_id: productId,
        },
        beforeSend: function () {
          // Show loading state
        },
        success: function (response) {
          if (response.success) {
            this.currentProductId = response.data.product_id;
            this.currentProductName = response.data.product_name;
            this.currentGlassesUrl = response.data.image_url;

            // Open popup and start try-on
            this.openPopup();
          } else {
            console.error('Error loading product data:', response.data.message);
          }
        }.bind(this),
        error: function (xhr, status, error) {
          console.error('AJAX error:', error);
        },
      });
    },

    /**
     * Open try-on popup
     */
    openPopup: function () {
      // Set product name
      $('.themoak-tryon-product-name').text(this.currentProductName);

      // Show popup
      this.$popup.show();
      setTimeout(
        function () {
          this.$popup.addClass('active');
        }.bind(this),
        10
      );

      // Initialize try-on if not already
      if (!this.isInitialized) {
        this.initializeTryOn();
      } else {
        // Just update the glasses image
        this.loadGlassesImage();
      }
    },

    /**
     * Close try-on popup
     */
    closePopup: function () {
      this.$popup.removeClass('active');

      setTimeout(
        function () {
          this.$popup.hide();
        }.bind(this),
        300
      );
    },

    /**
     * Initialize the try-on experience
     */
    initializeTryOn: function () {
      // Set the loading state
      this.$loading.show();

      // Initialize canvas context
      this.ctx = this.$overlay[0].getContext('2d');

      // Load glasses image
      this.loadGlassesImage();

      // Initialize FaceMesh
      this.initializeFaceMesh();

      // Start webcam
      this.startWebcam();
    },

    /**
     * Load glasses image
     */
    loadGlassesImage: function () {
      // Create image object
      this.glassesImage = new Image();
      this.glassesImage.src = this.currentGlassesUrl;

      // When image is loaded, create shadow image
      this.glassesImage.onload = function () {
        this.createShadowImage();
      }.bind(this);
    },

    /**
     * Create shadow image for glasses
     */
    createShadowImage: function () {
      const shadowCanvas = document.createElement('canvas');
      shadowCanvas.width = this.glassesImage.width;
      shadowCanvas.height = this.glassesImage.height;
      const shadowCtx = shadowCanvas.getContext('2d');

      // Draw original image
      shadowCtx.drawImage(this.glassesImage, 0, 0);

      // Make it darker
      shadowCtx.fillStyle = 'rgba(0, 0, 0, 0.6)';
      shadowCtx.globalCompositeOperation = 'source-atop';
      shadowCtx.fillRect(0, 0, shadowCanvas.width, shadowCanvas.height);

      // Blur effect
      shadowCtx.globalCompositeOperation = 'source-over';
      shadowCtx.filter = 'blur(3px)';
      shadowCtx.drawImage(shadowCanvas, 0, 0);

      this.glassesShadow = new Image();
      this.glassesShadow.src = shadowCanvas.toDataURL();
    },

    /**
     * Initialize FaceMesh
     */
    initializeFaceMesh: function () {
      this.faceMesh = new FaceMesh({
        locateFile: (file) => {
          return `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`;
        },
      });

      this.faceMesh.setOptions({
        maxNumFaces: 1,
        refineLandmarks: true,
        minDetectionConfidence: 0.5,
        minTrackingConfidence: 0.5,
      });

      this.faceMesh.onResults(this.onFaceMeshResults.bind(this));
    },

    /**
     * Start webcam
     */
    startWebcam: function () {
      navigator.mediaDevices
        .getUserMedia({
          video: {
            width: { ideal: 640 },
            height: { ideal: 480 },
            facingMode: 'user',
          },
        })
        .then(
          function (stream) {
            this.$webcam[0].srcObject = stream;

            // Initialize camera
            this.camera = new Camera(this.$webcam[0], {
              onFrame: async () => {
                await this.faceMesh.send({ image: this.$webcam[0] });
              },
              width: 640,
              height: 480,
            });

            // Start camera
            this.$webcam[0].onloadedmetadata = function () {
              this.camera.start();

              // Set canvas dimensions to match video
              this.$overlay[0].width = this.$webcam[0].videoWidth;
              this.$overlay[0].height = this.$webcam[0].videoHeight;

              // Hide loading
              setTimeout(
                function () {
                  this.$loading.hide();
                  this.isInitialized = true;
                }.bind(this),
                1000
              );
            }.bind(this);
          }.bind(this)
        )
        .catch(
          function (error) {
            console.error('Error accessing webcam:', error);
            this.showError(this.settings.error_message);
          }.bind(this)
        );
    },

    /**
     * Handle FaceMesh results
     */
    onFaceMeshResults: function (results) {
      // Clear canvas
      this.ctx.clearRect(0, 0, this.$overlay[0].width, this.$overlay[0].height);

      // Check if face is detected
      if (results.multiFaceLandmarks.length === 0) {
        this.smooth.inFrame = false;
        this.updateInstructions();
        return;
      }

      this.smooth.inFrame = true;
      this.faceDetectionCounter++;

      // Get environment light level every 30 frames
      if (this.faceDetectionCounter % 30 === 0) {
        const lightLevel = this.detectLightLevel();
        this.smooth.lightLevel =
          this.smoothingFactor * this.smooth.lightLevel +
          (1 - this.smoothingFactor) * lightLevel;
      }

      const landmarks = results.multiFaceLandmarks[0];

      // Using facial landmarks for positioning
      const leftEye = landmarks[33]; // Left eye outer corner
      const rightEye = landmarks[263]; // Right eye outer corner
      const nose = landmarks[4]; // Nose tip

      // Calculate temple width
      const leftTemple = landmarks[234];
      const rightTemple = landmarks[454];
      const templeDistance = Math.hypot(
        (rightTemple.x - leftTemple.x) * this.$overlay[0].width,
        (rightTemple.y - leftTemple.y) * this.$overlay[0].height
      );

      // Calculate face tilt for depth perception
      const noseBottom = landmarks[94];
      const foreheadCenter = landmarks[10];
      const depthIndicator = Math.abs((noseBottom.z - foreheadCenter.z) * 10);

      // Calculate positioning
      const leftX = leftEye.x * this.$overlay[0].width;
      const rightX = rightEye.x * this.$overlay[0].width;
      const leftY = leftEye.y * this.$overlay[0].height;
      const rightY = rightEye.y * this.$overlay[0].height;

      // Get nose position for better vertical placement
      const noseTop = landmarks[168]; // Nose bridge top
      const noseBridge = landmarks[6]; // Mid nose bridge

      const noseTopY = noseTop.y * this.$overlay[0].height;
      const noseBridgeY = noseBridge.y * this.$overlay[0].height;

      let centerX = (leftX + rightX) / 2;
      // Position glasses directly on the nose bridge
      let centerY = noseBridgeY;

      // Calculate proper width based on face dimensions
      let width = Math.hypot(rightX - leftX, rightY - leftY) * 2.6;

      // Adjust height to match glasses aspect ratio
      let height = width * (this.glassesImage.height / this.glassesImage.width);

      // Scale slightly based on z-coordinate (depth) for better fit
      const depthScale = 1 + Math.abs(nose.z) * 0.5;
      width *= depthScale;
      height *= depthScale;

      // Calculate angle between eyes
      let angle = Math.atan2(rightY - leftY, rightX - leftX);

      // Apply exponential smoothing
      if (this.smooth.x !== null) {
        this.smooth.x =
          this.smoothingFactor * this.smooth.x +
          (1 - this.smoothingFactor) * centerX;
        this.smooth.y =
          this.smoothingFactor * this.smooth.y +
          (1 - this.smoothingFactor) * centerY;
        this.smooth.width =
          this.smoothingFactor * this.smooth.width +
          (1 - this.smoothingFactor) * width;
        this.smooth.height =
          this.smoothingFactor * this.smooth.height +
          (1 - this.smoothingFactor) * height;
        this.smooth.angle =
          this.smoothingFactor * this.smooth.angle +
          (1 - this.smoothingFactor) * angle;
        this.smooth.depth =
          this.smoothingFactor * this.smooth.depth +
          (1 - this.smoothingFactor) * depthIndicator;
      } else {
        this.smooth.x = centerX;
        this.smooth.y = centerY;
        this.smooth.width = width;
        this.smooth.height = height;
        this.smooth.angle = angle;
        this.smooth.depth = depthIndicator;
      }

      // Apply optimized settings from admin panel
      const optimizedSettings = this.settings.optimized_settings;
      const adjustedX = this.smooth.x + optimizedSettings.positionX;
      const adjustedY = this.smooth.y + optimizedSettings.positionY;
      const adjustedWidth = this.smooth.width * optimizedSettings.sizeScale;
      const adjustedHeight = this.smooth.height * optimizedSettings.sizeScale;

      // Update instructions
      this.updateInstructions();

      // Draw shadow first
      if (this.glassesShadow && this.glassesShadow.complete) {
        this.ctx.save();
        this.ctx.translate(adjustedX, adjustedY + 2); // Offset shadow slightly
        this.ctx.rotate(this.smooth.angle);

        // Apply shadow based on depth
        const shadowOffset = 5 + this.smooth.depth * 2;

        // Draw shadow with slight offset and transparency
        this.ctx.globalAlpha = 0.4;
        this.ctx.drawImage(
          this.glassesShadow,
          -adjustedWidth / 2,
          -adjustedHeight / 2 + shadowOffset,
          adjustedWidth,
          adjustedHeight
        );
        this.ctx.restore();
      }

      // Draw glasses with lighting effects
      this.ctx.save();
      this.ctx.translate(adjustedX, adjustedY);
      this.ctx.rotate(this.smooth.angle);

      // Apply slightly curved perspective transform based on face angle
      const perspectiveSkew = this.smooth.angle * 2 * (Math.PI / 180);
      this.ctx.transform(1, 0, perspectiveSkew, 1, 0, 0);

      if (this.glassesImage && this.glassesImage.complete) {
        // Draw glasses
        this.ctx.drawImage(
          this.glassesImage,
          -adjustedWidth / 2,
          -adjustedHeight / 2,
          adjustedWidth,
          adjustedHeight
        );

        // Add reflection effect to lenses
        const reflectionOpacity =
          (0.15 + this.smooth.lightLevel * 0.25) *
          optimizedSettings.reflectionOpacity;

        this.ctx.fillStyle = `rgba(255, 255, 255, ${reflectionOpacity})`;
        this.ctx.globalCompositeOperation = 'lighter';

        // Apply reflection position adjustment
        const reflectionYOffset = optimizedSettings.reflectionPos;

        // Identify lens centers more accurately
        const lensSize =
          adjustedWidth * 0.28 * optimizedSettings.reflectionSize;
        const lensYOffset = -adjustedHeight / 20 + reflectionYOffset; // Center in the lens

        // Left lens reflection - smaller, more realistic
        this.ctx.beginPath();
        this.ctx.ellipse(
          -adjustedWidth / 4.5,
          lensYOffset,
          lensSize / 4,
          lensSize / 6,
          Math.PI / 3,
          0,
          Math.PI * 2
        );
        this.ctx.fill();

        // Add second subtle reflection to left lens
        this.ctx.beginPath();
        this.ctx.fillStyle = `rgba(255, 255, 255, ${reflectionOpacity * 0.7})`;
        this.ctx.ellipse(
          -adjustedWidth / 6,
          lensYOffset + lensSize / 10,
          lensSize / 6,
          lensSize / 10,
          Math.PI / 6,
          0,
          Math.PI * 2
        );
        this.ctx.fill();

        // Right lens reflection - smaller, more realistic
        this.ctx.fillStyle = `rgba(255, 255, 255, ${reflectionOpacity})`;
        this.ctx.beginPath();
        this.ctx.ellipse(
          adjustedWidth / 4.5,
          lensYOffset,
          lensSize / 4,
          lensSize / 6,
          Math.PI / 3,
          0,
          Math.PI * 2
        );
        this.ctx.fill();

        // Add second subtle reflection to right lens
        this.ctx.beginPath();
        this.ctx.fillStyle = `rgba(255, 255, 255, ${reflectionOpacity * 0.7})`;
        this.ctx.ellipse(
          adjustedWidth / 6,
          lensYOffset + lensSize / 10,
          lensSize / 6,
          lensSize / 10,
          Math.PI / 6,
          0,
          Math.PI * 2
        );
        this.ctx.fill();
      }

      this.ctx.restore();
    },

    /**
     * Update instructions based on face detection
     */
    updateInstructions: function () {
      const now = Date.now();

      if (!this.smooth.inFrame) {
        this.$instructions.text(this.settings.instruction_1);
        this.$instructions
          .parent()
          .css('background-color', 'rgba(255, 87, 87, 0.7)');
        this.instructionState = 0;
        return;
      }

      // Only change instructions every 3 seconds
      if (now - this.lastInstructionChange > 3000) {
        this.instructionState = (this.instructionState + 1) % 3;

        const instructionText =
          this.instructionState === 0
            ? this.settings.instruction_1
            : this.instructionState === 1
            ? this.settings.instruction_2
            : this.settings.instruction_3;

        this.$instructions.text(instructionText);
        this.$instructions
          .parent()
          .css('background-color', 'rgba(0, 0, 0, 0.6)');
        this.lastInstructionChange = now;
      }

      // After 10 seconds, fade out instructions
      if (this.faceDetectionCounter > 300) {
        this.$instructions.parent().css('opacity', '0.5');
      }
    },

    /**
     * Detect light level from video feed
     */
    detectLightLevel: function () {
      const tempCanvas = document.createElement('canvas');
      tempCanvas.width = 50; // Small sample size for performance
      tempCanvas.height = 50;
      const tempCtx = tempCanvas.getContext('2d');

      tempCtx.drawImage(
        this.$webcam[0],
        0,
        0,
        tempCanvas.width,
        tempCanvas.height
      );
      const imageData = tempCtx.getImageData(
        0,
        0,
        tempCanvas.width,
        tempCanvas.height
      );
      const data = imageData.data;

      let totalBrightness = 0;
      for (let i = 0; i < data.length; i += 4) {
        // Convert RGB to relative luminance
        const r = data[i] / 255;
        const g = data[i + 1] / 255;
        const b = data[i + 2] / 255;
        const brightness = 0.2126 * r + 0.7152 * g + 0.0722 * b;
        totalBrightness += brightness;
      }

      return totalBrightness / (tempCanvas.width * tempCanvas.height);
    },

    /**
     * Show error message
     */
    showError: function (message) {
      // Hide loading
      this.$loading.hide();

      // Create error element if not exists
      if ($('.themoak-tryon-error').length === 0) {
        const errorHtml = `
                    <div class="themoak-tryon-error">
                        <div class="themoak-tryon-error-icon">&#9888;</div>
                        <div class="themoak-tryon-error-message">${message}</div>
                        <button type="button" class="themoak-tryon-retry-button">${
                          this.settings.retry_text || 'Try Again'
                        }</button>
                    </div>
                `;

        this.$popup.find('.themoak-tryon-content').append(errorHtml);

        // Add retry button event
        $('.themoak-tryon-retry-button').on(
          'click',
          function () {
            $('.themoak-tryon-error').remove();
            this.$loading.show();
            this.startWebcam();
          }.bind(this)
        );
      } else {
        $('.themoak-tryon-error-message').text(message);
        $('.themoak-tryon-error').show();
      }
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    TheMoakTryOn.init();
  });
})(jQuery);
[0].width, (rightTemple.y - leftTemple.y) * this.$overlay;

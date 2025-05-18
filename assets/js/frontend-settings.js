/**
 * TheMoak Virtual Try-on Frontend Settings
 * Extends the frontend try-on experience with product-specific settings
 */
(function ($) {
  'use strict';

  $(document).ready(function () {
    // Wait for TheMoakTryOn to be defined
    var checkInterval = setInterval(function () {
      if (typeof TheMoakTryOn !== 'undefined') {
        clearInterval(checkInterval);

        // Override the getProductData method to handle product-specific settings
        var originalGetProductData = TheMoakTryOn.getProductData;
        TheMoakTryOn.getProductData = function (productId) {
          var self = this;

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
                console.error(
                  'Error loading product data:',
                  response.data.message
                );
              }
            },
            error: function (xhr, status, error) {
              console.error('AJAX error:', error);
            },
          });
        };

        // Override the onFaceMeshResults function to use product-specific settings
        var originalOnFaceMeshResults = TheMoakTryOn.onFaceMeshResults;

        TheMoakTryOn.onFaceMeshResults = function (results) {
          // Clear canvas
          this.ctx.clearRect(
            0,
            0,
            this.$overlay[0].width,
            this.$overlay[0].height
          );

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
          const depthIndicator = Math.abs(
            (noseBottom.z - foreheadCenter.z) * 10
          );

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
          let height =
            width * (this.glassesImage.height / this.glassesImage.width);

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

          // Use product-specific settings if available, otherwise use defaults
          const settings =
            this.productSettings || this.settings.optimized_settings;

          // Apply settings to adjust position and size
          const adjustedX = this.smooth.x + parseFloat(settings.positionX);
          const adjustedY = this.smooth.y + parseFloat(settings.positionY);
          const adjustedWidth =
            this.smooth.width * parseFloat(settings.sizeScale);
          const adjustedHeight =
            this.smooth.height * parseFloat(settings.sizeScale);

          // Update instructions
          this.updateInstructions();

          // Draw shadow first
          if (this.glassesShadow && this.glassesShadow.complete) {
            this.ctx.save();

            // Use product-specific shadow offset if available
            const shadowOffset = parseFloat(settings.shadowOffset || 10);
            this.ctx.translate(adjustedX, adjustedY + shadowOffset);
            this.ctx.rotate(this.smooth.angle);

            // Apply shadow based on depth and product-specific settings
            const shadowDepthOffset = 5 + this.smooth.depth * 2;

            // Use product-specific shadow opacity if available
            const shadowOpacity = parseFloat(settings.shadowOpacity || 0.4);

            // Draw shadow with slight offset and transparency
            this.ctx.globalAlpha = shadowOpacity;
            this.ctx.drawImage(
              this.glassesShadow,
              -adjustedWidth / 2,
              -adjustedHeight / 2 + shadowDepthOffset,
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
            const baseReflectionOpacity = 0.15 + this.smooth.lightLevel * 0.25;
            // Use product-specific reflection opacity if available
            const reflectionOpacity =
              baseReflectionOpacity *
              parseFloat(settings.reflectionOpacity || 0.7);

            this.ctx.fillStyle = `rgba(255, 255, 255, ${reflectionOpacity})`;
            this.ctx.globalCompositeOperation = 'lighter';

            // Apply reflection position adjustment from product settings
            const reflectionYOffset = parseFloat(settings.reflectionPos || 8);

            // Use product-specific reflection size if available
            const reflectionSizeFactor = parseFloat(
              settings.reflectionSize || 0.5
            );

            // Identify lens centers more accurately
            const lensSize = adjustedWidth * 0.28 * reflectionSizeFactor;
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
            this.ctx.fillStyle = `rgba(255, 255, 255, ${
              reflectionOpacity * 0.7
            })`;
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
            this.ctx.fillStyle = `rgba(255, 255, 255, ${
              reflectionOpacity * 0.7
            })`;
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
        };
      }
    }, 100);
  });
})(jQuery);

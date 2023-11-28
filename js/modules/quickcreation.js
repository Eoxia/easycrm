/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/quickcreation.js
 * \ingroup easycrm
 * \brief   JavaScript quickcreation file for module EasyCRM
 */

/**
 * Init quickcreation JS
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @type {Object}
 */
window.easycrm.quickcreation = {};

/**
 * Init quickcreation canvas
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.easycrm.quickcreation.canvas;

/**
 * Init rotation value of img on canvas
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.easycrm.quickcreation.rotation = 0;

/**
 * Init img in canvas
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.easycrm.quickcreation.img;

/**
 * QuickCreation init
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @returns {void}
 */
window.easycrm.quickcreation.init = function() {
  window.easycrm.quickcreation.event();
};

/**
 * QuickCreation event
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @returns {void}
 */
window.easycrm.quickcreation.event = function() {
  $(document).on('change', '#upload-image', window.easycrm.quickcreation.uploadImage);
  $(document).on('click', '.image-rotate-left', function() { window.easycrm.quickcreation.rotateImage(-90); });
  $(document).on('click', '.image-rotate-right', function() { window.easycrm.quickcreation.rotateImage(90); });
  $(document).on('click', '.image-undo', window.easycrm.quickcreation.undoLastDraw);
  $(document).on('click', '.image-erase', window.easycrm.quickcreation.clearCanvas);
  $(document).on('click', '.image-validate', window.easycrm.quickcreation.createImg);
};

window.easycrm.quickcreation.uploadImage = function() {
  if (this.files && this.files[0]) {
    var reader = new FileReader();

    reader.onload = function(event) {
      $(document).find('.modal-upload-image').addClass('modal-active');
      window.easycrm.quickcreation.drawImageOnCanvas(event);
    };

    reader.readAsDataURL(this.files[0]);
  }
};

/**
 * Rotate image action
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @returns {void}
 */
window.easycrm.quickcreation.rotateImage = function(degrees) {
  window.easycrm.quickcreation.rotation += degrees;
  $('#canvas').css('transform', 'rotate(' + window.easycrm.quickcreation.rotation + 'deg)');
};

/**
 * Undo last drawing action
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.easycrm.quickcreation.undoLastDraw = function() {
  let canvas = $(this).closest('.modal-upload-image').find('canvas');
  var data   = canvas[0].signaturePad.toData();
  if (data) {
    data.pop(); // remove the last dot or line
    canvas[0].signaturePad.fromData(data);
    // Redraw the image on the canvas
    window.easycrm.quickcreation.drawImageOnCanvas(window.easycrm.quickcreation.img);
  }
};

/**
 * Clear canvas action
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.easycrm.quickcreation.clearCanvas = function() {
  let canvas = $(this).closest('.modal-upload-image').find('canvas');
  canvas[0].signaturePad.clear();
  window.easycrm.quickcreation.drawImageOnCanvas(window.easycrm.quickcreation.img);
};

/**
 * Draw img on canvas action
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.easycrm.quickcreation.drawImageOnCanvas = function(event) {
  let ratio = Math.max(window.devicePixelRatio || 1, 1);
  window.easycrm.quickcreation.canvas = document.querySelector('#modal-upload-image0 canvas');

  window.easycrm.quickcreation.canvas.signaturePad = new SignaturePad(window.easycrm.quickcreation.canvas, {
    penColor: 'rgb(175, 175, 175)'
  });

  window.easycrm.quickcreation.canvas.width = 200 * ratio;
  window.easycrm.quickcreation.canvas.height = 200 * ratio;
  window.easycrm.quickcreation.canvas.getContext('2d');
  let context = window.easycrm.quickcreation.canvas.getContext('2d');
  window.easycrm.quickcreation.canvas.signaturePad.clear();

  // Draw the image on the canvas
  var img = new Image();
  img.src = event.target.result;
  window.easycrm.quickcreation.img = event;

  img.onload = function() {
      context.drawImage(img, 0, 0, window.easycrm.quickcreation.canvas.width, window.easycrm.quickcreation.canvas.height);
  };

  window.easycrm.quickcreation.rotation = 0; // Reset rotation when a new image is selected
};

/**
 * create img action
 *
 * @memberof EasyCRM_QuickCreation
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.easycrm.quickcreation.createImg = function() {
  let canvas = $(this).closest('.wpeo-modal').find('canvas')[0];
  let img    = canvas.toDataURL('image/png', 0.5);

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  let url = document.URL + querySeparator + 'action=add_img&token=' + token;
  $.ajax({
    url: url,
    type: 'POST',
    processData: false,
    contentType: 'application/octet-stream',
    data: JSON.stringify({
      img: img,
    }),
    success: function(resp) {
      $('.wpeo-modal').removeClass('modal-active');
      $('.project-container .linked-medias-list').replaceWith($(resp).find('.project-container .linked-medias-list'));
    },
    error: function () {}
  });
};

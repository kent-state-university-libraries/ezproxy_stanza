(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ezproxyStanzaEditConfig = {
    attach: function (context, settings) {
      $('#edit-file').on('change', function() {
        $('#edit-edit').click();
      })
    }
  };


}(jQuery, Drupal));

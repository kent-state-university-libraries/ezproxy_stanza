(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ezproxyStanzaManageConfig = {
    attach: function (context, settings) {
      var search = $('#views-exposed-form-ezproxy-stanza-search-default input[name="search"]').val();
      if (search.length) {
        var regEx = new RegExp(search, "ig");
        $('.view-header .view-ezproxy-stanza-search .views-field-field-ezproxy-stanza').each(function() {
          if ($(this).attr('hightlight-processed') != search) {
            $(this).attr('hightlight-processed', search)
            var stanza = $(this).find('.field-content');
            var html = stanza.html()

            stanza.html(html.replace(regEx, '<strong style="background-color: yellow">' + search + '</strong>'))
          }
        });
      }
    }
  };

}(jQuery, Drupal));

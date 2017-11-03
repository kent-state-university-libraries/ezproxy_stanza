(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ezproxyStanzaManageConfig = {
    attach: function (context, settings) {
      var search = $('.view-ezproxy-stanza-search input[name="search"]').val();
      if (search.length) {
        var regEx = new RegExp(search, "ig");
        $('.view-ezproxy-stanza-search .views-field-field-ezproxy-stanza').each(function() {
          if ($(this).attr('hightlight-processed') != search) {
            $(this).attr('hightlight-processed', search)
            var stanza = $(this).find('.field-content');
            var html = stanza.html()

            stanza.html(html.replace(regEx, '<strong style="background-color: yellow">' + search + '</strong>'))
            var node_id = $(this).parent().find('.nid');
            var nid = node_id.attr('data-nid');
            node_id.append('<button class="button form-submit add-to-config" data-nid="'+nid+'">'+Drupal.t('Add to')+' config.txt</button>')
          }
        });
      }

      $('.add-to-config').on('mouseup', function() {
        var nid = $(this).attr('data-nid');
        var checkbox = $('#edit-config-' + nid);
        var _td = checkbox.parent().parent();
        $('html, body').animate({
            scrollTop: _td.offset().top - (6*_td.height())
        }, 1000);
        if (!checkbox.is(':checked')) {
          checkbox.click();
        }
      })
    }
  };

}(jQuery, Drupal));

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
            var node_id = $(this).parent().find('.nid');
            var nid = node_id.attr('data-nid');
            node_id.append('<button class="button form-submit add-to-config" data-nid="'+nid+'">'+Drupal.t('Add to')+' config.txt</button>')
          }
        });
      }

      $('.add-to-config').on('mouseup', function() {
        var nid = $(this).attr('data-nid');
        var edit_link = $('a[href^="/node/' + nid + '/edit"]');
        $('html, body').animate({
            scrollTop: edit_link.offset().top - edit_link.height()
        }, 1000);
        var checkbox = edit_link.parent().parent().find('.form-checkbox');
        if (!checkbox.is(':checked')) {
          checkbox.click();
        }
      })
    }
  };

}(jQuery, Drupal));

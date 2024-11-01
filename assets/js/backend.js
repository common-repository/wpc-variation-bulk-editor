(function($) {
  'use strict';

  $(function() {
    // ready
    attributes_init();
    checkbox_init();
    select_init();
  });

  let wpcvb_media = {
    frame: null,
    image: null,
    upload_id: null,
    post_id: wp.media.model.settings.post.id,
  };

  $(document).on('click touch', '.wpcvb-btn-editor', function(e) {
    e.preventDefault();

    // save variations first
    $('#variable_product_options button.save-variation-changes').
        trigger('click');

    $('.wpcvb-popup-editor').dialog({
      minWidth: 640,
      title: wpcvb_vars.editor_title,
      modal: true,
      dialogClass: 'wpc-dialog',
      open: function() {
        $('.ui-widget-overlay').bind('click', function() {
          $('.wpcvb-popup-editor').dialog('close');
        });

        filter_count($('.wpcvb-popup-editor'));
      },
    });
  });

  $(document).on('click touch', '.wpcvb-btn-remove', function(e) {
    e.preventDefault();

    // save variations first
    $('#variable_product_options button.save-variation-changes').
        trigger('click');

    $('.wpcvb-popup-remove').dialog({
      minWidth: 640,
      title: wpcvb_vars.remove_title,
      modal: true,
      dialogClass: 'wpc-dialog',
      open: function() {
        $('.ui-widget-overlay').bind('click', function() {
          $('.wpcvb-popup-remove').dialog('close');
        });

        filter_count($('.wpcvb-popup-remove'));
      },
    });
  });

  $(document).on('click touch', '.wpcvb-btn-generate', function(e) {
    e.preventDefault();

    // save variations first
    $('#variable_product_options button.save-variation-changes').
        trigger('click');

    $('.wpcvb-popup-generate').dialog({
      minWidth: 640,
      title: wpcvb_vars.generate_title,
      modal: true,
      dialogClass: 'wpc-dialog',
      open: function() {
        $('.ui-widget-overlay').bind('click', function() {
          $('.wpcvb-popup-generate').dialog('close');
        });

        filter_count($('.wpcvb-popup-generate'));
      },
    });
  });

  $('.wpcvb-popup').on('change', '.wpcvb_attribute', function() {
    filter_count($(this).closest('.wpcvb-popup'));
  });

  $(document).on(' woocommerce_variations_loaded', function() {
    $('.wpcvb-popup').addClass('wpcvb-loading');

    var data = {
      action: 'wpcvb_filter_form',
      nonce: wpcvb_vars.nonce,
      post_id: woocommerce_admin_meta_boxes.post_id,
    };

    $.post(ajaxurl, data, function(response) {
      $('.wpcvb-filter-form').html(response);
      attributes_init();
      $('.wpcvb-popup').removeClass('wpcvb-loading');
    });
  });

  $('.wpcvb-popup-editor').
      on('click touch', '.wpcvb-submit-update', function() {
        $('.wpcvb-popup-editor').addClass('wpcvb-loading');

        var data = {
          action: 'wpcvb_bulk_update',
          nonce: wpcvb_vars.nonce,
          post_id: woocommerce_admin_meta_boxes.post_id,
          attrs: $('.wpcvb-popup-editor .wpcvb_attribute').serializeArray(),
          fields: $('.wpcvb-popup-editor .woocommerce_variable_attributes').
              find('input, select, button, textarea').
              serialize() || 0,
        };

        $.post(ajaxurl, data, function(response) {
          $('.wpcvb-popup-editor').removeClass('wpcvb-loading');
          $('.wpcvb-popup-editor').dialog('close');
          $('#variable_product_options').trigger('reload');
        });
      });

  $('.wpcvb-popup-remove').
      on('click touch', '.wpcvb-submit-remove', function() {
        if (window.confirm(wpcvb_vars.remove_warning)) {
          $('.wpcvb-popup-remove').addClass('wpcvb-loading');

          var data = {
            action: 'wpcvb_bulk_remove',
            nonce: wpcvb_vars.nonce,
            post_id: woocommerce_admin_meta_boxes.post_id,
            attrs: $('.wpcvb-popup-remove .wpcvb_attribute').serializeArray(),
          };

          $.post(ajaxurl, data, function(response) {
            $('.wpcvb-popup-remove').removeClass('wpcvb-loading');
            $('.wpcvb-popup-remove').dialog('close');
            $('#variable_product_options').trigger('reload');
          });
        }
      });

  $('.wpcvb-popup-generate').
      on('click touch', '.wpcvb-submit-generate', function() {
        if (window.confirm(wpcvb_vars.generate_warning)) {
          $('.wpcvb-popup-generate').addClass('wpcvb-loading');

          var data = {
            action: 'wpcvb_bulk_generate',
            nonce: wpcvb_vars.nonce,
            post_id: woocommerce_admin_meta_boxes.post_id,
            attrs: $('.wpcvb-popup-generate .wpcvb_attribute').serializeArray(),
          };

          $.post(ajaxurl, data, function(response) {
            $('.wpcvb-popup-generate').removeClass('wpcvb-loading');
            $('.wpcvb-popup-generate').dialog('close');
            $('#variable_product_options').trigger('reload');
          });
        }
      });

  $('.wpcvb-popup-editor').on('click touch', '.sale_schedule', function() {
    var t = $(this).closest('div, table');
    return $(this).hide(), t.find('.cancel_sale_schedule').show(), t.find(
        '.sale_price_dates_fields').show(), !1;
  });

  $('.wpcvb-popup-editor').
      on('click touch', '.cancel_sale_schedule', function() {
        var t = $(this).closest('div, table');
        return $(this).hide(), t.find('.sale_schedule').show(), t.find(
            '.sale_price_dates_fields').hide(), t.find(
            '.sale_price_dates_fields').
            find('input').
            val(''), !1;
      });

  $('.wpcvb-popup-editor').
      on('change', 'input.variable_manage_stock', function() {
        $(this).
            closest('.woocommerce_variation').
            find('.show_if_variation_manage_stock').
            hide(), $(this).
            closest('.woocommerce_variation').
            find('.variable_stock_status').
            show(), ($(this).is(':checked') || $(this).val() == '1') &&
        ($(this).
            closest('.woocommerce_variation').
            find('.show_if_variation_manage_stock').
            show(), $(this).
            closest('.woocommerce_variation').
            find('.variable_stock_status').
            hide()), $('input#_manage_stock:checked').length && $(this).
            closest('.woocommerce_variation').
            find('.variable_stock_status').
            hide();
      });

  $('.wpcvb-popup-editor').
      on('change', 'input.variable_is_virtual', function() {
        $(this).
            closest('.woocommerce_variation').
            find('.hide_if_variation_virtual').
            show(), ($(this).is(':checked') || $(this).val() == '1') && $(this).
            closest('.woocommerce_variation').
            find('.hide_if_variation_virtual').
            hide();
      });

  $('.wpcvb-popup-editor').
      on('change', 'input.variable_is_downloadable', function() {
        $(this).
            closest('.woocommerce_variation').
            find('.show_if_variation_downloadable').
            hide(), ($(this).is(':checked') || $(this).val() == '1') && $(this).
            closest('.woocommerce_variation').
            find('.show_if_variation_downloadable').
            show();
      });

  $('.wpcvb-popup-editor').
      on('click touch', '.downloadable_files a.insert', function() {
        $(this).
            closest('.downloadable_files').
            find('tbody').
            append($(this).data('row'));
        return false;
      });

  $('.wpcvb-popup-editor').
      on('click touch', '.downloadable_files a.delete', function() {
        $(this).closest('tr').remove();
        return false;
      });

  $('.wpcvb-popup-editor').
      on('click touch', '.upload_image_button', function(e) {
        var $button = $(this);
        var upload_id = parseInt($button.attr('rel'));

        wpcvb_media.image = $button.closest('.upload_image');

        if (upload_id) {
          console.log('upload_id');
          wpcvb_media.upload_id = upload_id;
        } else {
          console.log('!upload_id');
          wpcvb_media.upload_id = wpcvb_media.post_id;
        }

        e.preventDefault();

        if ($button.is('.remove')) {
          $('.upload_image_id', wpcvb_media.image).
              val('').
              trigger('change');
          wpcvb_media.image.find('img').
              eq(0).
              attr('src',
                  woocommerce_admin_meta_boxes_variations.woocommerce_placeholder_img_src);
          wpcvb_media.image.find('.upload_image_button').
              removeClass('remove');
        } else {
          if (wpcvb_media.frame) {
            wpcvb_media.frame.uploader.uploader.param('post_id',
                wpcvb_media.upload_id);
            wpcvb_media.frame.open();
            return;
          } else {
            wp.media.model.settings.post.id = wpcvb_media.upload_id;
          }

          wpcvb_media.frame = wp.media.frames.variable_image = wp.media({
            title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
            button: {
              text: woocommerce_admin_meta_boxes_variations.i18n_set_image,
            },
            states: [
              new wp.media.controller.Library({
                title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
                filterable: 'all',
              })],
          });

          wpcvb_media.frame.on('select', function() {
            var attachment = wpcvb_media.frame.state().
                get('selection').
                first().
                toJSON(), url = attachment.sizes && attachment.sizes.thumbnail ?
                attachment.sizes.thumbnail.url :
                attachment.url;

            $('.upload_image_id', wpcvb_media.image).
                val(attachment.id).
                trigger('change');
            wpcvb_media.image.find('.upload_image_button').
                addClass('remove');
            wpcvb_media.image.find('img').
                eq(0).
                attr('src', url);

            wp.media.model.settings.post.id = wpcvb_media.post_id;
          });

          wpcvb_media.frame.open();
        }
      });

  function attributes_init() {
    $('.wpcvb-popup .wpcvb_attribute').selectWoo();
  }

  function checkbox_init() {
    var i = 0;

    $('.wpcvb-popup-editor input[type="checkbox"]').each(function() {
      var $this = $(this);

      $this.val('wpcvb_no_change').trigger('change');

      if ($this.attr('id') === undefined) {
        $this.attr('id', 'wpcvb_checkbox_' + i);
      }

      $this.candlestick({
        'default': 'wpcvb_no_change',
        'nc': 'wpcvb_no_change',
        'swipe': false,
        afterSetting: function(el) {
          el.trigger('change');
        },
      });
      i++;
    });
  }

  function select_init() {
    $('.wpcvb-popup-editor select:not(.wpcvb_attribute)').each(function() {
      $(this).
          prepend('<option value="wpcvb_no_change">' + wpcvb_vars.no_change +
              '</option>').val('wpcvb_no_change').trigger('change');
    });
  }

  function filter_count($popup) {
    $popup.find('.wpcvb-filter-count').html('...');

    var data = {
      action: 'wpcvb_filter_count',
      nonce: wpcvb_vars.nonce,
      post_id: woocommerce_admin_meta_boxes.post_id,
      attrs: $popup.find('.wpcvb_attribute').serializeArray(),
    };

    $.post(ajaxurl, data, function(response) {
      $popup.find('.wpcvb-filter-count').html(response);
    });
  }
})(jQuery);

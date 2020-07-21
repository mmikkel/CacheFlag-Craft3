/** global: Craft */

$(function () {

    var $form = $('.cacheFlag-form');
    var $submitRequest = null;

    $form
        .on('submit', onFormSubmit)
        .on('click', '[data-clearflags]', clearCaches);

    function onFormSubmit(e) {
        e.preventDefault();
        submitForm();
    }

    function submitForm() {

        if ($submitRequest) {
            $submitRequest.abort();
        }

        $form.addClass('js-submitting');
        $form.find('.spinner').removeClass('hidden');
        $form.find('input[type="submit"]').prop('disabled', true).addClass('disabled');

        $submitRequest = $.ajax($form.attr('action'), {
            data: $form.serialize(),
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Craft.cp.displayNotice(response.message);
                    $form.find('input[type="text"][name^="cacheflags"]').each(function () {
                        var $input = $(this);
                        var source = $input.attr('name').replace('cacheflags[', '').replace(']', '').split(':');
                        var sourceColumn = source[0] || null;
                        var sourceValue = (source[1] || '').toString();
                        if (!sourceColumn || !sourceValue) {
                            return;
                        }
                        var flags = '';
                        for (var i = 0; i < response.flags.length; ++i) {
                            if ((response.flags[i][sourceColumn] || '').toString() === sourceValue) {
                                flags = response.flags[i].flags || '';
                                break;
                            }
                        }
                        $input.val(flags);
                    });
                } else {
                    Craft.cp.displayError(response.message);
                }
            },
            error: function (response) {
                if (response.statusText !== 'abort') {
                    Craft.cp.displayError(response.statusText);
                }
            },
            complete: function () {
                $submitRequest = null;
                $form.removeClass('js-submitting');
                $form.find('.spinner').addClass('hidden');
                $form.find('input[type="submit"]').prop('disabled', false).removeClass('disabled');
            }
        });

    }

    function clearCaches(e) {

        e.preventDefault();

        var actionUrl = Craft.getActionUrl('cache-flag/default/invalidate-flagged-caches-by-flags'),
            $target = $(e.currentTarget),
            flags = $target.data('clearflags');

        if ($target.hasClass('disabled') || !flags || flags == '') {
            return;
        }

        var data = {
            flags: flags
        };

        data[$form.data('csrf-name')] = $form.data('csrf-token');

        $target.addClass('disabled');

        $.ajax(actionUrl, {
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Craft.cp.displayNotice(response.message);
                } else {
                    Craft.cp.displayError(response.message);
                }
                $target.removeClass('disabled');
            },
            error: function (response) {
                if (response.statusText !== 'abort') {
                    Craft.cp.displayError(response.statusText);
                }
                $target.removeClass('disabled');
            }
        });

    }

});

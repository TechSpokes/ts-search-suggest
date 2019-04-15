// noinspection JSUnresolvedFunction
jQuery(document).ready(function ($) {
    // noinspection JSUnresolvedFunction,JSUnresolvedVariable,JSUnusedGlobalSymbols
    $('#s,[name="s"]').suggest(searchSuggest.suggestionsUrl, {
        minchars: 3,
        onSelect: function () {
            // noinspection JSUnresolvedFunction
            let $form = $(this).parents('form');

            // noinspection JSUnresolvedFunction,JSUnresolvedVariable
            $.post(searchSuggest.selectUrl, {
                action: searchSuggest.selectAction,
                id: searchSuggest.selectId,
                title: $('.ac_over').text()
            }, function (data) {
                //TODO: develop data with checks and log on fails
                if (data) {
                    window.location = data;
                } else {
                    $form.submit();
                }
            }).fail(function () {
                $form.submit();
            });
        }
    });
});

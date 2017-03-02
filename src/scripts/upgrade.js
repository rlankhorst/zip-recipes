/**
 * Plugin main js file
 * 
 * @param {type} $
 * @returns {undefined}
 * php jquery update long pool
 */

(function ($, window, document, undefined) {
    var _ids;
    var initial = 0;
    var found = 0;
    var messageAlert = 'Please ensure you have a working backup of your website. This is something you can check with your website hosting company.If you have a working backup of your website, click the checkbox to indicate that.';
    var scanDeprecated = function () {
        var queryString = {action: 'upgrade_callback', zrdn_action: 'scan'};
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: queryString,
            success: function (data) {
                _ids = data;
                initial = data.length;
                return data;
            }
        });
    };
    scanDeprecated();
    var convertDepr = function () {
        var deferred = new $.Deferred();
        var _queue = function (index) {
            var queryString = {action: 'upgrade_callback', zrdn_action: 'convert', zrdn_id: _ids[index]};
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                cache: false,
                async: true,
                data: queryString,
                success: function (data) {
                    deferred.resolve(index);
                    index++;    // going to next queue entry                    
                    if (data == 1) {
                        found++;
                        $('.progress p:last-child').html('Converted ' + found);
                    }
                    // check if it exists
                    if (_ids[index] != undefined) {
                        _queue(index);
                    } else {
                        if (found == 0) {
                            $('.progress p:last-child').html('Converted 0');
                        }
                        $(".progress").append("<p>Process finished ...</p>");
                        $(".steps-success").show();
                        _doneDepr(); // zrdn_upgrade.settings
                        return deferred.promise();
                    }
                }
            });
        }; // end of execute_queue() {...
        if (undefined !== _ids && initial > 0) {
            var index = 0;
            _queue(index); // go!            
        }
    };

    var _doneDepr = function () {
        var queryString = {action: 'upgrade_callback', zrdn_action: 'done'};
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: queryString,
            success: function (data) {
                return true;
            }
        });
    };

    var _process = function () {
        setTimeout(function () {
            convertDepr();
        }, 100);
    };

    $(".upgrade-wrapper section:not(#step-1)").hide();
    $(document).on("click", "button[name='action_upgrade']", function (e) {
        e.preventDefault();
        if (!$('#accept-upgrade').is(":checked")) {
            if (window.confirm(messageAlert)) {
                $(".upgrade-wrapper section:nth-child(1)").hide("fast", function () {
                    $(this).next().show("fast");
                });
                _process();
            }
        } else {
            $(".upgrade-wrapper section:nth-child(1)").hide("fast", function () {
                $(this).next().show("fast");
            });
            _process();
        }

    });
})(jQuery, window, document);
/**
 * Frontend status-update dismiss handling.
 * Stores dismissed items in localStorage keyed by id+tstamp, so re-edited
 * updates surface again automatically.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'efStatusUpdatesDismissed';

    function readDismissed() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function writeDismissed(data) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (e) {
            /* storage full or disabled — ignore */
        }
    }

    function init() {
        var containers = document.querySelectorAll('[data-status-updates]');
        if (!containers.length) {
            return;
        }

        var dismissed = readDismissed();

        containers.forEach(function (container) {
            var items = container.querySelectorAll('.status-update[data-dismissible="1"]');
            items.forEach(function (item) {
                var id = item.getAttribute('data-id');
                var tstamp = item.getAttribute('data-tstamp');

                if (!id || !tstamp || dismissed[id] !== tstamp) {
                    item.hidden = false;
                }

                var button = item.querySelector('.status-update__close');
                if (!button) {
                    return;
                }

                button.addEventListener('click', function () {
                    item.hidden = true;
                    if (id && tstamp) {
                        var current = readDismissed();
                        current[id] = tstamp;
                        writeDismissed(current);
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

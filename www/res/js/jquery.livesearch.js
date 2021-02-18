var forceShow = false;

jQuery.fn.liveUpdate = function (list) {
    list = jQuery(list);

    if (list.length) {
        var rows = list.children('a'),
            cache = rows.map(function () {
                return jQuery(this).text().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, "");
            });

        this.keyup(filter).keyup().parents('form').submit(function () {
            return false;
        });
    }

    return this;

    function filter() {
        var term = jQuery.trim(
            jQuery(this).val()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, "")
        );
        var scores = [];

        if (!term) {
            rows.hide();
            showWarningTooMuchEntries(false);
        } else {
            rows.hide();
            cache.each(function (i) {
                var score = this.score(term);
                if (score > 0) {
                    scores.push([score, i]);
                }
            });
            if (forceShow) {
                forceShow = false;
            } else {
                showNoEntriesFound(false);
                if (scores.length === 0) {
                    showWarningTooMuchEntries(false);
                    showNoEntriesFound(true);
                } else if (scores.length > 10) {
                    showWarningTooMuchEntries(true, scores.length + 1);
                    return;
                } else {
                    showWarningTooMuchEntries(false);
                }
            }

            jQuery.each(
                scores.sort(function (a, b) {
                    return b[0] - a[0];
                }), function () {
                    jQuery(rows[ this[1] ]).show();
                }
            );
        }
    }
};

function showWarningTooMuchEntries(show, cnt = 0) {
    document.getElementById('warning-entries').style.display = show ? 'block' : 'none';
    document.getElementById('results-cnt').innerText = cnt;
};

function showNoEntriesFound(show) {
    document.getElementById('no-entries').style.display = show ? 'block' : 'none';
};
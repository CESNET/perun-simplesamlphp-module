let forceShow = false;

function setForceShow(val) {
    forceShow = val;
}

$.fn.liveUpdate = function(list) {
    list = $(list);
    if (list.length) {
        var rows = list.children('a'), cache = rows.map(function () {
            return jQuery(this).text().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, "");
        });

        this.keyup(filter).keyup().parents('form').submit(function () {
            return false;
        });
    }
    return this;

    function filter() {
        const searchTerm = $.trim($(this).val().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ""));
        const scores = [];
        rows.hide();
        showNoEntriesFound(false);
        if (!searchTerm) {
            setForceShow(false);
            return;
        }
        cache.each(function (i) {
            const score = this.score(searchTerm);
            if (score > 0) {
                scores.push([score, i]);
            }
        });
        if (scores.length === 0) {
            showNoEntriesFound(true);
        } else if (!forceShow && scores.length > 7) {
            showWarningTooMuchEntries(true, scores.length + 1);
        } else {
            $.each(
                scores.sort(
                    function(a, b) { return b[0] - a[0];}),
                function() {$(rows[this[1]]).show();
                });
        }
    }
};

function showWarningTooMuchEntries(show, cnt = 0) {
    $('#no-entries').hide();
    if (show) {
        $('#warning-entries').show();
    } else {
        $('#warning-entries').hide();
    }
    $('#results-cnt').text(cnt);
}

function showNoEntriesFound(show) {
    $('#warning-entries').hide();
    if (show) {
        $('#no-entries').show();
    } else {
        $('#no-entries').hide();
    }
}

$(document).ready(function() {
    $("#query").liveUpdate("#list");
    $("#showEntries").click(function() {
        $("#last-used-idp-wrap").hide();
        $("#entries").show();
        $("#showEntries").hide();
    });
    $("#showEntriesFromDropdown").click(function() {
        $("#dropdown-entries").toggle();
    });
    if ($("#last-used-idp-wrap").length > 0) {
        $("#last-used-idp .metaentry").focus();
    } else {
        $("#entries").show();
    }
    $('#warning-entries-btn-force-show').click(function() {
        $('#query').trigger('keyup');
        showWarningTooMuchEntries(false);
        showEntries();
    });
});

function showEntries() {
    setForceShow(true);
    $('#query').trigger('keyup');
}

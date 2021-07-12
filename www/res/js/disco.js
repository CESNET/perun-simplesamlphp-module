function hideAll () {
    $("#list").hide();
    $('#no-entries').hide();
    $('#warning-entries').hide();
}

$(document).ready(function() {
    if ($("#last-used-idp-wrap").length > 0) {
        $("#last-used-idp .metaentry").focus();
    } else {
        $("#entries").show();
    }

    $("#showEntries").click(function() {
        $("#last-used-idp-wrap").hide();
        $("#entries").show();
        $("#showEntries").hide();
    });

    let forceShow = false
    $('#query').keyup(function() {
        const filter = $(this).val().trim().toLowerCase().normalize("NFD").replace(/\p{Diacritic}/gu, "")
        if (!filter) {
            hideAll();
            forceShow = false;
        } else {
            let matches = [];
            $('#list a').each(function () {
                if ($(this).attr('data-search').indexOf(filter) >= 0) {
                    matches.push(this);
                } else {
                    $(this).hide();
                }
            });
            if (matches.length <= 0) {
                $('#no-entries').show();
                $('#warning-entries').hide();
            } else {
                $("#list").show();
                $('#results-cnt').text(matches.length);
                $('#no-entries').hide();
                if (matches.length > 10 && !forceShow) {
                    matches.forEach(m => {
                        $(m).hide();
                    });
                    $('#warning-entries').show();
                } else {
                    $('#warning-entries').hide();
                    matches.forEach(m => {
                        $(m).show();
                    });
                }
            }
        }
    });

    $('#warning-entries-btn-force-show').click(function(event) {
        event.preventDefault();
        forceShow = true;
        $('#warning-entries').hide();
        $('#query').trigger('keyup').focus();
    });
});

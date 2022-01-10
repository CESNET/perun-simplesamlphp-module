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
    const noEntries = $('#no-entries');
    const tooManyEntries = $('#warning-entries');
    const displayed = $('#list');
    const hidden = $('#list-hidden');
    const resultsCnt = $('#results-cnt');

    let lastFilterSize = Number.MAX_SAFE_INTEGER;

    const input = $('#query');
    let typingTimer;
    const doneTypingInterval = 300;

    input.on('input propertychange paste', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(filter, doneTypingInterval);
    });

    //on keydown, clear the countdown
    input.on('keydown', function () {
        clearTimeout(typingTimer);
    });

    function checkAndHandleEmptyFilter(filter) {
        if (!filter || filter.trim().length < 3) {
            hideEl(noEntries);
            hideEl(tooManyEntries);
            forceShow = false;
            return true;
        }
        return false;
    }

    //on keyup, start the countdown
    function filter() {
        let filter = input.val();
        hideEl(displayed);

        if (checkAndHandleEmptyFilter(filter)) {
            return;
        }
        filter = filter.toLowerCase().normalize("NFD").replace(/\p{Diacritic}/gu, "");

        if (checkAndHandleEmptyFilter(filter)) {
            return;
        }

        const currentlyDisplayed = $('#list a');
        const currentlyHidden = $('#list-hidden a');

        const newDisplayed = [];
        let newHidden = [];

        if (lastFilterSize <= filter.length) {
            newHidden = newHidden.concat(currentlyHidden);
        } else {
            currentlyHidden.each(function () {
                const el = $(this);
                if (el.attr('data-search').indexOf(filter) >= 0) {
                    newDisplayed.push(el);
                } else {
                    newHidden.push(el);
                }
            });
        }

        lastFilterSize = filter.length;

        currentlyDisplayed.each(function () {
            const el = $(this);
            if (el.attr('data-search').indexOf(filter) >= 0) {
                newDisplayed.push(el);
            } else {
                newHidden.push(el);
            }
        });

        displayed.append(newDisplayed);
        hidden.append(newHidden);

        if (newDisplayed.length === 0) {
            hideEl(tooManyEntries);
            showEl(noEntries);
            return;
        }

        if (newDisplayed.length > 0 && newDisplayed.length <= 10) {
            handleFoundResults();
        } else {
            if (forceShow) {
                handleForcedResults();
            } else {
                handleOverflowResults(newDisplayed.length);
            }
        }
    }

    function handleFoundResults() {
        hideEl(noEntries);
        hideEl(tooManyEntries);
        showEl(displayed);
    }

    function handleForcedResults() {
        hideEl(noEntries);
        hideEl(tooManyEntries);
        showEl(displayed);
    }

    function handleOverflowResults(newDisplayedCnt) {
        hideEl(noEntries);
        resultsCnt.text(newDisplayedCnt);
        showEl(tooManyEntries);
    }

    $('#warning-entries-btn-force-show').click(function(event) {
        event.preventDefault();
        forceShow = true;
        tooManyEntries.hide();
        input.trigger('input').focus();
    });
});

function hideEl(el) {
    if (el.is(':visible')) {
        el.hide();
    }
}

function showEl(el) {
    if (el.is(':hidden')) {
        el.show();
    }
}

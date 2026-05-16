//////////////////////
//
//  copied from CROTOS 
//  see https://github.com/zone47/CROTOS
//  Published under  Creative Commons 0 (CC0) - "No Rights Reserved" license


$(document).ready(function() {
    var trueValues = buildYearScale();
    var maxPosition = trueValues.length - 1;
    persistYearRange(y1, y2);
    var slider = $("#slider-range").slider({
        range: true,
        min:0,
        max:maxPosition,
        values: [idxdate(0, y1), idxdate(1, y2)],
        slide: function(event, ui) {
            var includeLeft  = event.keyCode != $.ui.keyCode.RIGHT;
            var includeRight = event.keyCode != $.ui.keyCode.LEFT;
            var value = findNearest(includeLeft, includeRight, ui.value);
            var movedIndex = ui.value == ui.values[0] ? 0 : 1;
            if (movedIndex === 0)
                slider.slider('values', 0, value);
            else
                slider.slider('values', 1, value);
            if (slider.slider('values', 0) >= slider.slider('values', 1)) {
                if (movedIndex === 0) {
                    slider.slider('values', 1, Math.min(maxPosition, slider.slider('values', 0) + 1));
                    if (slider.slider('values', 0) >= slider.slider('values', 1)) {
                        slider.slider('values', 0, Math.max(0, slider.slider('values', 1) - 1));
                    }
                }
                else {
                    slider.slider('values', 0, Math.max(0, slider.slider('values', 1) - 1));
                }
            }
            $($(this).parent().children('.sliderValue')[0]).val(getRealValue(slider.slider('values', 0)));
            $($(this).parent().children('.sliderValue')[1]).val(getRealValue(slider.slider('values', 1)));
            persistCurrentYearRange();
            return false;
            
        }
    });
    function buildYearScale() {
        var years = [0];
        addYears(years, 1000, 1800, 50);
        addYears(years, 1820, 1880, 20);
        addYears(years, 1890, 1940, 10);
        addYears(years, 1950, new Date().getFullYear(), 10);
        return years;
    }

    function addYears(years, start, end, step) {
        for (var year = start; year <= end; year += step) {
            if (years[years.length - 1] !== year) {
                years.push(year);
            }
        }
        if (years[years.length - 1] !== end) {
            years.push(end);
        }
    }

   function findNearest(includeLeft, includeRight, input) {
        var nearest = null;
        var diff = null;
        for (var i = 0; i <= maxPosition; i++) {
            if ((includeLeft && i <= input) || (includeRight && i >= input)) {
                var newDiff = Math.abs(input - i);
                if (diff == null || newDiff < diff) {
                    nearest = i;
                    diff = newDiff;
                }
            }
        }
        return nearest;
    }
   
    function idxdate(sens,date) {
        date = parseInt(date, 10);
        if (sens==0){
            var key=0;
            for (var i = 0; i <= maxPosition; i++) {
                if (date >= trueValues[i])
                    key=i;
                else
                    break;
            }
        }
        else{
            var key=maxPosition;
            for (var i = maxPosition; i > -1; i--) {
                if (date<=trueValues[i]) 
                    key=i;
                else
                    break;
            }
        }
        return key;
    }
    function getRealValue(sliderValue) {
        for (var i = 0; i <= maxPosition; i++) {
            if (i >= sliderValue) {
                return trueValues[i];
            }
        }
        return 0;
    }

    function previousYear(value) {
        var index = idxdate(0, value);
        return trueValues[Math.max(0, index - 1)];
    }

    function nextYear(value) {
        var index = idxdate(1, value);
        return trueValues[Math.min(maxPosition, index + 1)];
    }

    function setYearCookie(name, value) {
        var expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        document.cookie = name + "=" + encodeURIComponent(value) + "; expires=" + expires.toUTCString() + "; path=/; SameSite=Lax";
    }

    function persistYearRange(from, to) {
        setYearCookie("oratorMatcherY1", from);
        setYearCookie("oratorMatcherY2", to);
        updateYearUrl(from, to);
    }

    function persistCurrentYearRange() {
        persistYearRange($($(".sliderValue")[0]).val(), $($(".sliderValue")[1]).val());
    }

    function updateYearUrl(from, to) {
        if (!window.history || !window.history.replaceState || !window.URL) {
            return;
        }
        var url = new URL(window.location.href);
        url.searchParams.set("y1", from);
        url.searchParams.set("y2", to);
        window.history.replaceState(null, document.title, url.toString());
    }

    function normalizeYearInputs(changedIndex) {
        var $from = $($(".sliderValue")[0]);
        var $to = $($(".sliderValue")[1]);
        var from = parseInt($from.val(), 10);
        var to = parseInt($to.val(), 10);
        if (isNaN(from)) {
            from = trueValues[0];
            $from.val(from);
        }
        if (isNaN(to)) {
            to = trueValues[maxPosition];
            $to.val(to);
        }
        if (from >= to) {
            if (changedIndex === 0) {
                to = nextYear(from);
                $to.val(to);
                if (from >= to) {
                    from = previousYear(to);
                    $from.val(from);
                }
            }
            else {
                from = previousYear(to);
                $from.val(from);
            }
        }
    }

    $(".sliderValue").change(function() {
        var $this = $(this);
        normalizeYearInputs($this.data("index"));
        var fromPos = idxdate(0, $($(".sliderValue")[0]).val());
        var toPos = idxdate(1, $($(".sliderValue")[1]).val());
        $("#slider-range").slider("values", 0, fromPos);
        $("#slider-range").slider("values", 1, toPos);
        $(".sliderValue").each(function() {
            $($('.hiddenSliderValue')[$(this).data("index")]).val($(this).val());
        });
        persistCurrentYearRange();
    });

});

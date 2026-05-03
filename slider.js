//////////////////////
//
//  copied from CROTOS 
//  see https://github.com/zone47/CROTOS
//  Published under  Creative Commons 0 (CC0) - "No Rights Reserved" license


$(document).ready(function() {
    var trueValues = buildYearScale();
    var maxPosition = trueValues.length - 1;
    var slider = $("#slider-range").slider({
        range: true,
        min:0,
        max:maxPosition,
        values: [idxdate(0, y1), idxdate(1, y2)],
        slide: function(event, ui) {
            var includeLeft  = event.keyCode != $.ui.keyCode.RIGHT;
            var includeRight = event.keyCode != $.ui.keyCode.LEFT;
            var value = findNearest(includeLeft, includeRight, ui.value);
            if (ui.value == ui.values[0])
                slider.slider('values', 0, value);
            else
                slider.slider('values', 1, value);
            $($(this).parent().children('.sliderValue')[0]).val(getRealValue(slider.slider('values', 0)));
            $($(this).parent().children('.sliderValue')[1]).val(getRealValue(slider.slider('values', 1)));
            return false;
            
        }
    });
    function buildYearScale() {
        var years = [0];
        addYears(years, 1000, 1800, 50);
        addYears(years, 1820, 1880, 20);
        addYears(years, 1890, 1940, 10);
        addYears(years, 1945, new Date().getFullYear(), 5);
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
    
    $("input.sliderValue").change(function() {
        var $this = $(this);
        var pos=idxdate($this.data("index"),$this.val());
        $("#slider-range").slider("values", $this.data("index"),pos);
        $($('.hiddenSliderValue')[$this.data("index")]).val($this.val());
    });

});

//////////////////////
//
//  copied from CROTOS 
//  see https://github.com/zone47/CROTOS
//  Published under  Creative Commons 0 (CC0) - "No Rights Reserved" license


$(document).ready(function() {
    var trueValues = [0,1030,1040,1050,1060,1070,1080,1090,1100,1110,1120,1130,1140,1150,1160,1170,1180,1190,1200,1210,1220,1230,1240,1250,1260,1270,1280,1290,1300,1310,1320,1330,1340,1350,1360,1370,1380,1390,1400,1410,1420,1430,1440,1450,1460,1470,1480,1490,1500,1510,1520,1530,1540,1550,1560,1570,1580,1590,1600,1610,1620,1630,1640,1650,1660,1670,1680,1690,1700,1710,1720,1730,1740,1750,1760,1770,1780,1790,1800,1810,1820,1830,1840,1850,1860,1870,1880,1890,1900,1910,1920,1930,1940,1950,1960,1970,1980,1990,2000,2010,new Date().getFullYear()];
    var slider = $("#slider-range").slider({
        range: true,
        min:0,
        max:100,
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
   function findNearest(includeLeft, includeRight, input) {
        var nearest = null;
        var diff = null;
        for (var i = 0; i <= 100; i++) {
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
        if (sens==0){
            key=0;
            for (var i = 0; i < 100; i++) {
                if (date >= trueValues[i])
                    key=i;
                else
                    break;
            }
        }
        else{
            key=100;
            for (var i = 100; i > -1; i--) {
                if (date<=trueValues[i]) 
                    key=i;
                else
                    break;
            }
        }
        return key;
    }
    function getRealValue(sliderValue) {
        for (var i = 0; i <= 100; i++) {
            if (i >= sliderValue) {
                return trueValues[i];
            }
        }
        return 0;
    }
    
    $("input.sliderValue").change(function() {
        var $this = $(this);
        pos=idxdate($this.data("index"),$this.val());
        $("#slider-range").slider("values", $this.data("index"),pos);
        $($('.hiddenSliderValue')[$this.data("index")]).val($this.val());
    });

});

<?php

$y1 = 2010;
$y2 = date('Y');

if (isset($_GET['y1']) && isset($_GET['y2'])){
	$y1 = intval($_GET['y1']);
	$y2 = intval($_GET['y2']);
}
elseif(isset($_POST['y1']) && isset($_POST['y2'])){
	$y1 = intval($_POST['y1']);
	$y2 = intval($_POST['y2']);
}
elseif(isset($_COOKIE['oratorMatcherY1']) && isset($_COOKIE['oratorMatcherY2'])){
	$y1 = intval($_COOKIE['oratorMatcherY1']);
	$y2 = intval($_COOKIE['oratorMatcherY2']);
}

if ($y2 <= $y1){
	$y1 = previousSliderYear($y2, array($y1, $y2));
}

function sliderYearScale($selectedYears = array()){
	$years = array(0);
	addSliderYears($years, 1000, 1800, 50);
	addSliderYears($years, 1820, 1880, 20);
	addSliderYears($years, 1890, 1940, 10);
	addSliderYears($years, 1950, intval(date('Y')), 10);
	foreach ($selectedYears as $year){
		$years[] = intval($year);
	}
	$years = array_unique($years);
	sort($years, SORT_NUMERIC);
	return $years;
}

function addSliderYears(&$years, $start, $end, $step){
	for ($year = $start; $year <= $end; $year += $step){
		if (end($years) !== $year){
			$years[] = $year;
		}
	}
	if (end($years) !== $end){
		$years[] = $end;
	}
}

function previousSliderYear($selectedYear, $allSelectedYears = array()){
	$previousYear = 0;
	foreach (sliderYearScale($allSelectedYears) as $year){
		if ($year >= intval($selectedYear)){
			return $previousYear;
		}
		$previousYear = $year;
	}
	return $previousYear;
}

function sliderYearOptions($selectedYear, $allSelectedYears = array()){
	$options = '';
	foreach (sliderYearScale($allSelectedYears) as $year){
		$selected = intval($selectedYear) === $year ? ' selected' : '';
		$options .= '<option value="'.$year.'"'.$selected.'>'.$year.'</option>';
	}
	return $options;
}
?>

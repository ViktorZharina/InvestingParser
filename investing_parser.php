<?php
function post_content($url, $postdata) {
    $uagent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_REFERER, 'http://www.investing.com/economic-calendar/');
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвращать, а не выводить
    curl_setopt($ch, CURLOPT_HEADER, 0); // включать в ответ заголовки
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1); // code > 300 = error
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Requested-With: XMLHttpRequest"
    ));
    
    $content = curl_exec($ch);
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);
    
    $header['errno'] = $err;
    $header['errmsg'] = $errmsg;
    $header['content'] = $content;
    return $header;
}

function getImportance($data) {
	return substr_count($data,'grayFullBullishIcon');
}

function getCountry($data) {
    preg_match("/(([A-Z]{3})<\/td>)/", $data, $country);
    return (isset($country[2])) ? $country[2] : '';
}

function getTimeStamp($data) {
    preg_match("(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})", $data, $a);
    return (empty($a)) ? '' : $a[0];
}

function getEventTime($data) {
    preg_match("/>(\d{2}:\d{2})/", $data, $time_arr);
    return (isset($time_arr[1])) ? $time_arr[1] : '';
}

function getEventName($data) {
    preg_match('/event">(.+)</', $data, $event);
    return (isset($event[1])) ? $event[1] : '';
}

function getActualValue($data) {
    preg_match('/Actual_\d*">(.*)<\/td>/', $data, $act);
    return (isset($act[1])) ? $act[1] : '';
}

function getForecastValue($data) {
    preg_match('/Forecast_\d*">(.*)<\/td>/', $data, $forecast);
    return (isset($forecast[1])) ? $forecast[1] : '';
}

function getPreviousValue($data) {
    preg_match('/Previous_\d*">(.*)<\/td>/', $data, $prev);
    return (isset($prev[1])) ? $prev[1] : '';
}

$postdata = array(
    "dateFrom" => $argv[1], //dateFrom yyyy-mm-dd
    "dateTo" => $argv[2], //dateTo yyyy-mm-dd
    "timeZone" => $argv[3], //time zone int
    "importance[]" => $argv[4], //importnace (1-3)
);

$header = post_content('http://www.investing.com/economic-calendar/filter', $postdata);
$content = json_decode($header['content'], true);
$html_str = $content['renderedFilteredEvents'];
preg_match_all("(<tr.*>(\s*<td.*>)+\s*<\/tr>)", $html_str, $arr);
$data = array();

foreach ($arr[0] as $key => $value) {
    $data[$key]['timestamp'] = getTimeStamp($value);
    $data[$key]['time'] = getEventTime($value);
    $data[$key]['country'] = getCountry($value);
    $data[$key]['event'] = getEventName($value);
    $data[$key]['importance'] = getImportance($value);
    $data[$key]['actual'] = getActualValue($value);
    $data[$key]['forecast'] = getForecastValue($value);
    $data[$key]['prev'] = getPreviousValue($value);
}

file_put_contents('data.out', json_encode($data));
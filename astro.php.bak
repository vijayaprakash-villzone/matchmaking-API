<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL); // Temporarily enable for debugging "empty space"
ini_set('display_errors', 0); // Don't echo errors to output buffer

// Catch fatal errors and return as JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        echo json_encode([
            "status" => "error",
            "message" => "Internal Server Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']
        ]);
        exit;
    }
});

$boystarid = isset($_REQUEST['bs']) ? $_REQUEST['bs'] : 1;
$girlstarid = isset($_REQUEST['gs']) ? $_REQUEST['gs'] : 1;
$boypadaid = isset($_REQUEST['bp']) ? $_REQUEST['bp'] : 1;
$girlpadaid = isset($_REQUEST['gp']) ? $_REQUEST['gp'] : 1;
$lang_code = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 'en';

// Load language
$lang_file = __DIR__ . "/languages/" . $lang_code . ".php";
if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/languages/en.php";
}

ob_start(); // Buffer output to prevent accidental spaces
$lang = include($lang_file);
ob_clean(); // Discard any accidental output from include

if (!is_array($lang)) {
    echo json_encode(["status" => "error", "message" => "Language file not found or invalid: " . $lang_code]);
    exit;
}

$star_array = $lang['stars'];
$rasiArray = $lang['rasis'];
$kanamArray = $lang['kanams'];
$messages = $lang['messages'];
$poruthams = $lang['poruthams'];
$rajjuLabels = $lang['rajjus'];

$starToKanam = [
    1 => 1, 2 => 2, 3 => 3, 4 => 2, 5 => 1, 6 => 2, 7 => 1, 8 => 1, 9 => 3, 10 => 3,
    11 => 2, 12 => 2, 13 => 1, 14 => 3, 15 => 1, 16 => 3, 17 => 1, 18 => 3, 19 => 3, 20 => 2,
    21 => 2, 22 => 1, 23 => 3, 24 => 3, 25 => 2, 26 => 2, 27 => 1
];

$starPadaRasiArray = [
    '1-1' => 1, '1-2' => 1, '1-3' => 1, '1-4' => 1, '2-1' => 1, '2-2' => 1, '2-3' => 1, '2-4' => 1, '3-1' => 1,
    '3-2' => 2, '3-3' => 2, '3-4' => 2, '4-1' => 2, '4-2' => 2, '4-3' => 2, '4-4' => 2, '5-1' => 2, '5-2' => 2,
    '5-3' => 3, '5-4' => 3, '6-1' => 3, '6-2' => 3, '6-3' => 3, '6-4' => 3, '7-1' => 3, '7-2' => 3, '7-3' => 3,
    '7-4' => 4, '8-1' => 4, '8-2' => 4, '8-3' => 4, '8-4' => 4, '9-1' => 4, '9-2' => 4, '9-3' => 4, '9-4' => 4,
    '10-1' => 5, '10-2' => 5, '10-3' => 5, '10-4' => 5, '11-1' => 5, '11-2' => 5, '11-3' => 5, '11-4' => 5,
    '12-1' => 5, '12-2' => 6, '12-3' => 6, '12-4' => 6, '13-1' => 6, '13-2' => 6, '13-3' => 6, '13-4' => 6,
    '14-1' => 6, '14-2' => 6, '14-3' => 7, '14-4' => 7, '15-1' => 7, '15-2' => 7, '15-3' => 7, '15-4' => 7,
    '16-1' => 7, '16-2' => 7, '16-3' => 7, '16-4' => 8, '17-1' => 8, '17-2' => 8, '17-3' => 8, '17-4' => 8,
    '18-1' => 8, '18-2' => 8, '18-3' => 8, '18-4' => 8, '19-1' => 9, '19-2' => 9, '19-3' => 9, '19-4' => 9,
    '20-1' => 9, '20-2' => 9, '20-3' => 9, '20-4' => 9, '21-1' => 9, '21-2' => 10, '21-3' => 10, '21-4' => 10,
    '22-1' => 10, '22-2' => 10, '22-3' => 10, '22-4' => 10, '23-1' => 10, '23-2' => 10, '23-3' => 11, '23-4' => 11,
    '24-1' => 11, '24-2' => 11, '24-3' => 11, '24-4' => 11, '25-1' => 11, '25-2' => 11, '25-3' => 11, '25-4' => 12,
    '26-1' => 12, '26-2' => 12, '26-3' => 12, '26-4' => 12, '27-1' => 12, '27-2' => 12, '27-3' => 12, '27-4' => 12,
];

function StarPosCalc()
{
    global $boystarid, $girlstarid;
    if ($boystarid >= $girlstarid) {
        return ($boystarid - $girlstarid) + 1;
    } else {
        return (27 - $girlstarid + $boystarid) + 1;
    }
}

function rasiPosCalc()
{
    global $starPadaRasiArray, $boypadaid, $girlpadaid, $boystarid, $girlstarid;
    $boyRasiId = $starPadaRasiArray[$boystarid . '-' . $boypadaid];
    $girlRasiId = $starPadaRasiArray[$girlstarid . '-' . $girlpadaid];
    if ($boyRasiId >= $girlRasiId) {
        return ($boyRasiId - $girlRasiId) + 1;
    } else {
        return (12 - $girlRasiId + $boyRasiId) + 1;
    }
}

$startPosfromGirlToBoy = StarPosCalc();

function dinaporuthamCalc()
{
    global $star_array, $startPosfromGirlToBoy, $girlpadaid, $boystarid, $girlstarid, $messages, $poruthams;
    $dinaContitionOne = [2, 4, 6, 8, 9, 11, 13, 15, 17, 18, 20, 22, 24, 26, 27];
    $dinaContitionTwo = [4, 6, 10, 16, 22, 13, 26, 27];
    $dinaContitionThree = [11, 12, 14, 7, 8, 1, 3, 20, 21, 5, 17];

    if ($boystarid == $girlstarid) {
        if (in_array($girlstarid, $dinaContitionTwo)) {
            $msg = $messages['excellent'];
            $points = 1;
        } else if (in_array($girlstarid, $dinaContitionThree)) {
            $msg = $messages['medium'];
            $points = 0.5;
        } else {
            $msg = $messages['matched'];
            $points = 1;
        }
    } else {
        if (($startPosfromGirlToBoy == 12 && in_array($girlpadaid, [2, 3, 4])) || ($startPosfromGirlToBoy == 14 && in_array($girlpadaid, [1, 2, 3])) || ($startPosfromGirlToBoy == 16 && in_array($girlpadaid, [1, 2, 4]))) {
            $msg = $messages['medium'];
            $points = 0.5;
        } else if (in_array($startPosfromGirlToBoy, $dinaContitionOne)) {
            $msg = $messages['excellent'];
            $points = 1;
        } else {
            $msg = $messages['not_matched'];
            $points = 0;
        }
    }
    return ["lable" => $poruthams['dina'], 'points' => $points, "message" => $msg, 'girlLable' => $star_array[$girlstarid], 'boyLable' => $star_array[$boystarid]];
}

function kanaPoruthamCalc()
{
    global $starToKanam, $kanamArray, $startPosfromGirlToBoy, $girlstarid, $boystarid, $messages, $poruthams;
    $girlKanamId = $starToKanam[$girlstarid];
    $boyKanamId = $starToKanam[$boystarid];
    if (($girlKanamId == $boyKanamId) || ($girlKanamId == 2 && $boyKanamId == 1)) {
        $msg = $messages['excellent'];
        $points = 1;
    } else if (($girlKanamId == 1 && in_array($boyKanamId, [2, 3])) || ($girlKanamId == 3 && $startPosfromGirlToBoy > 14)) {
        $msg = $messages['medium'];
        $points = 0.5;
    } else {
        $msg = $messages['not_matched'];
        $points = 0;
    }
    return ["lable" => $poruthams['gana'], 'points' => $points, "message" => $msg, 'girlLable' => $kanamArray[$girlKanamId], 'boyLable' => $kanamArray[$boyKanamId]];
}

function magentharaPoruthamCalc()
{
    global $star_array, $girlstarid, $boystarid, $startPosfromGirlToBoy, $messages, $poruthams;
    if (in_array($startPosfromGirlToBoy, [1, 4, 7, 10, 13, 16, 19, 22, 25])) {
        $msg = $messages['excellent'];
        $points = 1;
    } else {
        $msg = $messages['not_matched'];
        $points = 0;
    }
    return ["lable" => $poruthams['mahendra'], 'points' => $points, "message" => $msg, 'girlLable' => $star_array[$girlstarid], 'boyLable' => $star_array[$boystarid]];
}

function istriPoruthamCalc()
{
    global $star_array, $girlstarid, $boystarid, $startPosfromGirlToBoy, $messages, $poruthams;
    if ($startPosfromGirlToBoy < 7) {
        $msg = $messages['not_matched'];
        $points = 0;
    } else if ($startPosfromGirlToBoy <= 13) {
        $msg = $messages['medium'];
        $points = 0.5;
    } else {
        $msg = $messages['excellent'];
        $points = 1;
    }
    return ["lable" => $poruthams['stree'], 'points' => $points, "message" => $msg, 'girlLable' => $star_array[$girlstarid], 'boyLable' => $star_array[$boystarid]];
}

function yoniPoruthamCalc()
{
    global $boystarid, $girlstarid, $lang, $messages, $poruthams;
    $yoniVal = $lang['yoni_values'];
    $k = $lang['yoni_animals'];
    $tempMale = $yoniVal[$boystarid];
    $tempFemale = $yoniVal[$girlstarid];
    $msg = $messages['medium'];
    $points = 0.5;
    if ($tempMale == $tempFemale) {
        $msg = $messages['excellent'];
        $points = 1;
    } else {
        $maleHasMale = strpos($tempMale, $k['male']) !== false;
        $maleHasFemale = strpos($tempMale, $k['female']) !== false;
        $femaleHasMale = strpos($tempFemale, $k['male']) !== false;
        $femaleHasFemale = strpos($tempFemale, $k['female']) !== false;
        if (($maleHasMale && $femaleHasFemale) || ($femaleHasMale && $maleHasFemale)) {
            $msg = $messages['excellent']; $points = 1;
        } else if (($femaleHasMale && $maleHasMale) || ($femaleHasFemale && $maleHasFemale)) {
            $msg = $messages['matched']; $points = 1;
        }
        $conflicts = [
            [$k['elephant'], $k['lion']], [$k['elephant'], $k['human']], [$k['horse'], $k['cow']],
            [$k['horse'], $k['buffalo']], [$k['horse'], $k['mongoose']], [$k['buffalo'], $k['cow']],
            [$k['tiger'], $k['cow']], [$k['tiger'], $k['buffalo']], [$k['tiger'], $k['deer']],
            [$k['tiger'], $k['dog']], [$k['monkey'], $k['goat']], [$k['rat'], $k['cat']],
            [$k['rat'], $k['snake']], [$k['snake'], $k['mongoose']], [$k['snake'], $k['goat']],
            [$k['cat'], $k['dog']], [$k['cat'], $k['tiger']]
        ];
        foreach ($conflicts as $pair) {
            if ((strpos($tempFemale, $pair[0]) !== false && strpos($tempMale, $pair[1]) !== false) ||
                (strpos($tempMale, $pair[0]) !== false && strpos($tempFemale, $pair[1]) !== false)) {
                $msg = $messages['not_matched']; $points = 0; break;
            }
        }
        if ($points != 0) {
            $friendly = [[$k['deer'], $k['cow']], [$k['goat'], $k['horse']], [$k['dog'], $k['human']]];
            foreach ($friendly as $pair) {
                if ((strpos($tempFemale, $pair[0]) !== false && strpos($tempMale, $pair[1]) !== false) ||
                    (strpos($tempMale, $pair[0]) !== false && strpos($tempFemale, $pair[1]) !== false)) {
                    $msg = $messages['excellent']; $points = 1;
                }
            }
        }
    }
    return ["lable" => $poruthams['yoni'], 'points' => $points, "message" => $msg, 'girlLable' => $tempFemale, 'boyLable' => $tempMale];
}

function rasiPoruthamCalc()
{
    global $rasiArray, $starPadaRasiArray, $boypadaid, $girlpadaid, $boystarid, $girlstarid, $messages, $poruthams;
    $rasiPos = rasiPosCalc();
    $boyRasiId = $starPadaRasiArray[$boystarid . '-' . $boypadaid];
    $girlRasiId = $starPadaRasiArray[$girlstarid . '-' . $girlpadaid];
    $msg = $messages['low']; $points = 0;
    if ($boyRasiId == $girlRasiId) { $msg = $messages['excellent']; $points = 1; }
    else if ($rasiPos == 7) {
        if (($girlRasiId == 4 && $boyRasiId == 10) || ($girlRasiId == 10 && $boyRasiId == 4) || ($girlRasiId == 5 && $boyRasiId == 11) || ($girlRasiId == 11 && $boyRasiId == 5)) {
            $msg = $messages['not_matched']; $points = 0;
        } else { $msg = $messages['excellent']; $points = 1; }
    } else if (in_array($rasiPos, [3, 4, 12, 9, 10, 11])) {
        $msg = ($rasiPos >= 9) ? $messages['excellent'] : $messages['medium']; $points = ($rasiPos >= 9) ? 1 : 0.5;
    }
    return ["lable" => $poruthams['rasi'], 'points' => $points, "message" => $msg, 'girlLable' => $rasiArray[$girlRasiId], 'boyLable' => $rasiArray[$boyRasiId]];
}

function rasiAthipathiPoruthamCalc()
{
    global $starPadaRasiArray, $boypadaid, $girlpadaid, $boystarid, $girlstarid, $lang, $messages, $poruthams;
    $p = $lang['planets'];
    $athipathi = [1 => 3, 2 => 6, 3 => 4, 4 => 2, 5 => 1, 6 => 4, 7 => 6, 8 => 3, 9 => 5, 10 => 7, 11 => 7, 12 => 5];
    $boyRasiId = $starPadaRasiArray[$boystarid . '-' . $boypadaid];
    $girlRasiId = $starPadaRasiArray[$girlstarid . '-' . $girlpadaid];
    $gAth = $athipathi[$girlRasiId]; $bAth = $athipathi[$boyRasiId];
    $natpu = [1 => [2, 3, 5], 2 => [1, 4], 3 => [1, 2, 5], 4 => [1, 6], 5 => [1, 2, 3], 6 => [4, 7], 7 => [4, 6]];
    $samam = [1 => [4], 2 => [3, 5, 6, 7], 3 => [6, 7], 4 => [3, 5, 7], 5 => [7], 6 => [3, 5], 7 => [5]];
    $msg = $messages['low']; $points = 0;
    if ($gAth == $bAth || in_array($bAth, $natpu[$gAth])) { $msg = $messages['excellent']; $points = 1; }
    else if (in_array($bAth, $samam[$gAth])) { $msg = $messages['medium']; $points = 0.5; }
    return ["lable" => $poruthams['rasi_athipathi'], 'points' => $points, "message" => $msg, 'girlLable' => $p[$gAth], 'boyLable' => $p[$bAth]];
}

function vasiyaPoruthamCalc()
{
    global $rasiArray, $starPadaRasiArray, $boypadaid, $girlpadaid, $boystarid, $girlstarid, $messages, $poruthams;
    $vasiya = [1 => [5, 8], 2 => [4, 7], 3 => [6], 4 => [8, 9], 5 => [10], 6 => [2, 12], 7 => [10], 8 => [4, 6], 9 => [12], 10 => [11], 11 => [12], 12 => [10]];
    $boyRasiId = $starPadaRasiArray[$boystarid . '-' . $boypadaid];
    $girlRasiId = $starPadaRasiArray[$girlstarid . '-' . $girlpadaid];
    $msg = $messages['not_matched']; $points = 0;
    if (in_array($boyRasiId, $vasiya[$girlRasiId] ?? [])) { $msg = $messages['excellent']; $points = 1; }
    else if (in_array($girlRasiId, $vasiya[$boyRasiId] ?? [])) { $msg = $messages['medium']; $points = 0.5; }
    return ["lable" => $poruthams['vasiya'], 'points' => $points, "message" => $msg, 'girlLable' => $rasiArray[$girlRasiId], 'boyLable' => $rasiArray[$boyRasiId]];
}

function rajjuPoruthamCalc()
{
    global $boystarid, $girlstarid, $messages, $poruthams, $rajjuLabels;
    $rajju1 = [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 2, 7 => 2, 8 => 2, 9 => 2, 10 => 1, 11 => 1, 12 => 1, 13 => 1, 14 => 1, 15 => 2, 16 => 2, 17 => 2, 18 => 2, 19 => 1, 20 => 1, 21 => 1, 22 => 1, 23 => 1, 24 => 2, 25 => 2, 26 => 2, 27 => 2];
    $rajju2 = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 1, 11 => 2, 12 => 3, 13 => 4, 14 => 5, 15 => 6, 16 => 7, 17 => 8, 18 => 9, 19 => 1, 20 => 2, 21 => 3, 22 => 4, 23 => 5, 24 => 6, 25 => 7, 26 => 8, 27 => 9];
    $rajju3 = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 4, 7 => 3, 8 => 2, 9 => 1, 10 => 1, 11 => 2, 12 => 3, 13 => 4, 14 => 5, 15 => 4, 16 => 3, 17 => 2, 18 => 1, 19 => 1, 20 => 2, 21 => 3, 22 => 4, 23 => 5, 24 => 4, 25 => 3, 26 => 2, 27 => 1];
    $g1 = $rajju1[$girlstarid]; $b1 = $rajju1[$boystarid];
    $g2 = $rajju2[$girlstarid]; $b2 = $rajju2[$boystarid];
    $g3 = $rajju3[$girlstarid]; $b3 = $rajju3[$boystarid];
    $con2Names = [1 => $rajjuLabels['arogana'] . " " . $rajjuLabels[1], 2 => $rajjuLabels['arogana'] . " " . $rajjuLabels[2], 3 => $rajjuLabels['arogana'] . " " . $rajjuLabels[3], 4 => $rajjuLabels['arogana'] . " " . $rajjuLabels[4], 5 => $rajjuLabels[5], 6 => $rajjuLabels['avarogana'] . " " . $rajjuLabels[4], 7 => $rajjuLabels['avarogana'] . " " . $rajjuLabels[3], 8 => $rajjuLabels['avarogana'] . " " . $rajjuLabels[2], 9 => $rajjuLabels['avarogana'] . " " . $rajjuLabels[1]];
    if ($g3 == $b3) { $msg = $messages['not_matched']; $points = 0; }
    else if (($g1 == 1 && $b1 == 2) || ($g1 == 2 && $b1 == 1)) { $msg = $messages['excellent']; $points = 1; }
    else if ($g1 == $b1) { $msg = $messages['medium']; $points = 0.5; }
    else { $msg = $messages['not_matched']; $points = 0; }
    return ["lable" => $poruthams['rajju'], 'points' => $points, "message" => $msg, 'girlLable' => $con2Names[$g2], 'boyLable' => $con2Names[$b2]];
}

function vethaiPoruthamCalc()
{
    global $star_array, $boystarid, $girlstarid, $messages, $poruthams;
    $bad = [[1, 18], [2, 17], [3, 16], [4, 15], [6, 22], [7, 21], [9, 19], [10, 27], [8, 20], [11, 26], [12, 25], [13, 24]];
    $msg = $messages['excellent']; $points = 1;
    foreach ($bad as $pair) { if (($girlstarid == $pair[0] && $boystarid == $pair[1]) || ($girlstarid == $pair[1] && $boystarid == $pair[0])) { $msg = $messages['not_matched']; $points = 0; break; } }
    if ($points != 0 && in_array($girlstarid, [5, 14, 23]) && in_array($boystarid, [5, 14, 23])) { $msg = $messages['not_matched']; $points = 0; }
    return ["lable" => $poruthams['vedhai'], 'points' => $points, "message" => $msg, 'girlLable' => $star_array[$girlstarid], 'boyLable' => $star_array[$boystarid]];
}

$results = [dinaporuthamCalc(), kanaPoruthamCalc(), magentharaPoruthamCalc(), istriPoruthamCalc(), yoniPoruthamCalc(), rasiPoruthamCalc(), rasiAthipathiPoruthamCalc(), vasiyaPoruthamCalc(), rajjuPoruthamCalc(), vethaiPoruthamCalc()];
$total = 0; foreach ($results as $res) $total += $res['points'];
echo json_encode(["status" => $messages['success'], "total_points" => $total, "details" => $results], JSON_UNESCAPED_UNICODE);
exit;

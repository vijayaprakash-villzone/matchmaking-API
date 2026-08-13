<?php
/**
 * NakshaMilan / Marriage Match API
 *
 * 12-Porutham engine aligned to the observed behavior of porutham.co.in's
 * marriage-match result.php and the existing AstroVedham astro.php rules.
 *
 * Request (POST/GET):
 *   bs = groom/boy star id 1..27
 *   bp = groom/boy pada 1..4
 *   gs = bride/girl star id 1..27
 *   gp = bride/girl pada 1..4
 *
 * Response:
 *   total_points: 0..12 (0.5 is intentionally supported where the source
 *                 result uses a medium/partial state)
 *   details: 12 poruthams in the same order as porutham.co.in
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$boystarid = filter_input(INPUT_POST, 'bs', FILTER_VALIDATE_INT);
$boypadaid = filter_input(INPUT_POST, 'bp', FILTER_VALIDATE_INT);
$girlstarid = filter_input(INPUT_POST, 'gs', FILTER_VALIDATE_INT);
$girlpadaid = filter_input(INPUT_POST, 'gp', FILTER_VALIDATE_INT);

// Keep compatibility with the existing Flutter/Postman implementation when
// the values are sent as query/form fields through REQUEST rather than POST.
$boystarid = $boystarid ?: (isset($_REQUEST['bs']) ? (int) $_REQUEST['bs'] : 0);
$boypadaid = $boypadaid ?: (isset($_REQUEST['bp']) ? (int) $_REQUEST['bp'] : 0);
$girlstarid = $girlstarid ?: (isset($_REQUEST['gs']) ? (int) $_REQUEST['gs'] : 0);
$girlpadaid = $girlpadaid ?: (isset($_REQUEST['gp']) ? (int) $_REQUEST['gp'] : 0);

$stars = [
    1 => 'Ashwini', 2 => 'Bharani', 3 => 'Krittika', 4 => 'Rohini',
    5 => 'Mrigashira', 6 => 'Ardra', 7 => 'Punarvasu', 8 => 'Pushya',
    9 => 'Ashlesha', 10 => 'Magha', 11 => 'Purva Phalguni', 12 => 'Uttara Phalguni',
    13 => 'Hasta', 14 => 'Chitra', 15 => 'Swati', 16 => 'Vishakha',
    17 => 'Anuradha', 18 => 'Jyeshtha', 19 => 'Mula', 20 => 'Purva Ashadha',
    21 => 'Uttara Ashadha', 22 => 'Shravana', 23 => 'Dhanishtha', 24 => 'Shatabhisha',
    25 => 'Purva Bhadrapada', 26 => 'Uttara Bhadrapada', 27 => 'Revati'
];

$rasis = [
    1 => 'Aries', 2 => 'Taurus', 3 => 'Gemini', 4 => 'Cancer',
    5 => 'Leo', 6 => 'Virgo', 7 => 'Libra', 8 => 'Scorpio',
    9 => 'Sagittarius', 10 => 'Capricorn', 11 => 'Aquarius', 12 => 'Pisces'
];

// Rasi for each Nakshatra Pada. Index is [star][pada].
$starPadaRasi = [
    1 => [1,1,1,1], 2 => [1,1,1,1], 3 => [1,2,2,2], 4 => [2,2,2,2],
    5 => [2,2,3,3], 6 => [3,3,3,3], 7 => [3,3,3,4], 8 => [4,4,4,4],
    9 => [4,4,4,4], 10 => [5,5,5,5], 11 => [5,5,5,5], 12 => [5,6,6,6],
    13 => [6,6,6,6], 14 => [6,6,7,7], 15 => [7,7,7,7], 16 => [7,7,7,8],
    17 => [8,8,8,8], 18 => [8,8,8,8], 19 => [9,9,9,9], 20 => [9,9,9,9],
    21 => [9,9,10,10], 22 => [10,10,10,10], 23 => [10,10,11,11],
    24 => [11,11,11,11], 25 => [11,11,12,12], 26 => [12,12,12,12],
    27 => [12,12,12,12]
];

$gana = [
    1 => 1, 2 => 2, 3 => 3, 4 => 2, 5 => 1, 6 => 2, 7 => 1, 8 => 1,
    9 => 3, 10 => 3, 11 => 2, 12 => 2, 13 => 1, 14 => 3, 15 => 1,
    16 => 3, 17 => 1, 18 => 3, 19 => 3, 20 => 2, 21 => 2, 22 => 1,
    23 => 3, 24 => 3, 25 => 2, 26 => 2, 27 => 1
];
$ganaNames = [1 => 'Deva Ganam', 2 => 'Manushya Ganam', 3 => 'Rakshasa Ganam'];

function starDistance(int $girl, int $boy): int {
    return (($boy - $girl + 27) % 27) + 1;
}

function rasiDistance(int $girlRasi, int $boyRasi): int {
    return (($boyRasi - $girlRasi + 12) % 12) + 1;
}

function result(string $label, float $points, string $message, string $girlLabel, string $boyLabel): array {
    return [
        'lable' => $label,
        'points' => $points,
        'message' => $message,
        'girlLable' => $girlLabel,
        'boyLable' => $boyLabel,
    ];
}

function poruthamMessage(float $points): string {
    if ($points >= 1.0) return 'Excellent Match';
    if ($points > 0.0) return 'Medium Match';
    return 'Not Matched';
}

if (
    $boystarid < 1 || $boystarid > 27 ||
    $girlstarid < 1 || $girlstarid > 27 ||
    $boypadaid < 1 || $boypadaid > 4 ||
    $girlpadaid < 1 || $girlpadaid > 4
) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input. bs/gs must be 1-27 and bp/gp must be 1-4.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$girlRasi = $starPadaRasi[$girlstarid][$girlpadaid - 1];
$boyRasi = $starPadaRasi[$boystarid][$boypadaid - 1];
$starPos = starDistance($girlstarid, $boystarid);
$rasiPos = rasiDistance($girlRasi, $boyRasi);

$details = [];

/* 1. Dina Porutham
 *
 * Match distances observed from porutham.co.in:
 * 2,4,6,8,9,11,13,15,17,18,20,22,24,26,27
 *
 * Same-star distance = 1 is NOT a Dina match.
 */
$dinaFull = [2, 4, 6, 8, 9, 11, 13, 15, 17, 18, 20, 22, 24, 26, 27];

$dinaPoints = 0.0;

if (in_array($starPos, $dinaFull, true)) {
    $dinaPoints = 1.0;
}

$details[] = result(
    'Dina Porutham',
    $dinaPoints,
    poruthamMessage($dinaPoints),
    $stars[$girlstarid],
    $stars[$boystarid]
);

/* 2. Gana Porutham
 * Website-reference behavior:
 * Same-gana = full match.
 * Deva girl + Manushya boy = full match.
 * Manushya girl + Deva boy = full match.
 * Deva/Manushya bride against Rakshasa groom = fail/medium
 * according to the observed reference cases.
 */
$gg = $gana[$girlstarid];
$bg = $gana[$boystarid];

$ganaPoints = 0.0;

if ($gg === $bg) {
    $ganaPoints = 1.0;
} elseif ($gg === 1 && $bg === 2) {
    $ganaPoints = 1.0;
} elseif ($gg === 2 && $bg === 1) {
    $ganaPoints = 1.0;   // FIX: Case 6
} elseif ($gg === 1 && $bg === 3) {
    $ganaPoints = 0.0;
} elseif ($gg === 2 && $bg === 3) {
    $ganaPoints = 0.5;
} elseif ($gg === 3 && $bg !== 3) {
    $ganaPoints = 0.0;
}

$details[] = result(
    'Gana Porutham',
    $ganaPoints,
    poruthamMessage($ganaPoints),
    $ganaNames[$gg],
    $ganaNames[$bg]
);

/* 3. Mahendra */
$mahendraPoints = in_array($starPos, [4,7,10,13,16,19,22,25], true) ? 1.0 : 0.0;
$details[] = result('Mahendra Porutham', $mahendraPoints, poruthamMessage($mahendraPoints), $stars[$girlstarid], $stars[$boystarid]);

/* 4. Sthree Deergha */
if ($starPos > 13) {
    $streePoints = 1.0;
} elseif ($starPos > 7) {
    $streePoints = 0.5;
} else {
    $streePoints = 0.0;
}

$details[] = result(
    'Stree Porutham',
    $streePoints,
    poruthamMessage($streePoints),
    $stars[$girlstarid],
    $stars[$boystarid]
);

/* 5. Yoni */
$yoni = [
    1=>'Horse', 2=>'Elephant', 3=>'Sheep', 4=>'Snake', 5=>'Snake', 6=>'Dog',
    7=>'Cat', 8=>'Sheep', 9=>'Cat', 10=>'Rat', 11=>'Rat', 12=>'Cow',
    13=>'Buffalo', 14=>'Tiger', 15=>'Buffalo', 16=>'Tiger', 17=>'Deer',
    18=>'Deer', 19=>'Dog', 20=>'Monkey', 21=>'Mongoose', 22=>'Monkey',
    23=>'Lion', 24=>'Horse', 25=>'Lion', 26=>'Cow', 27=>'Elephant'
];
$hostileYoni = [
    'Elephant' => ['Lion'],
    'Lion' => ['Elephant'],
    'Horse' => ['Buffalo', 'Cow'],
    'Buffalo' => ['Horse'],
    'Cow' => ['Horse', 'Buffalo', 'Tiger'],
    'Tiger' => ['Cow', 'Buffalo', 'Deer', 'Dog'],
    'Deer' => ['Tiger', 'Dog'],
    'Dog' => ['Cat', 'Tiger', 'Deer'],
    'Cat' => ['Dog', 'Rat'],
    'Rat' => ['Cat', 'Snake'],
    'Snake' => ['Rat', 'Mongoose', 'Sheep'],
    'Mongoose' => ['Snake', 'Sheep'],
    'Sheep' => ['Monkey', 'Snake', 'Mongoose'],
    'Monkey' => ['Sheep'],
];
$girlYoni = $yoni[$girlstarid];
$boyYoni = $yoni[$boystarid];
$yoniPoints = ($girlYoni === $boyYoni) ? 1.0 : 1.0;
if (isset($hostileYoni[$girlYoni]) && in_array($boyYoni, $hostileYoni[$girlYoni], true)) {
    $yoniPoints = 0.0;
}
$details[] = result('Yoni Porutham', $yoniPoints, poruthamMessage($yoniPoints), $girlYoni, $boyYoni);

/* 6. Rasi */
$rasiPoints = 0.0;
if ($girlRasi === $boyRasi) {
    $rasiPoints = 1.0;
} elseif ($rasiPos === 7) {
    $bad7 = [
        [4,10], [10,4], [5,11], [11,5]
    ];
    $rasiPoints = in_array([$girlRasi,$boyRasi], $bad7, true) ? 0.0 : 1.0;
} elseif (in_array($rasiPos, [3,4,12], true)) {
    $rasiPoints = 0.5;
} elseif (in_array($rasiPos, [2,5,6,8], true)) {
    $rasiPoints = 0.0;
} elseif (in_array($rasiPos, [9,10,11], true)) {
    $rasiPoints = 1.0;
}
$extraRasiBad = [
    [4,9],[6,11],[8,1],[10,3],[12,5]
];
if (in_array([$girlRasi,$boyRasi], $extraRasiBad, true)) {
    $rasiPoints = 0.0;
}
$details[] = result('Rasi Porutham', $rasiPoints, poruthamMessage($rasiPoints), $rasis[$girlRasi], $rasis[$boyRasi]);

/* 7. Rasi Athipathi */
$planetNames = [
    1 => 'Sun',
    2 => 'Moon',
    3 => 'Mars',
    4 => 'Mercury',
    5 => 'Jupiter',
    6 => 'Venus',
    7 => 'Saturn'
];

$rasiLord = [
    1 => 3,
    2 => 6,
    3 => 4,
    4 => 2,
    5 => 1,
    6 => 4,   // Virgo -> Mercury
    7 => 6,   // Libra -> Venus
    8 => 3,
    9 => 5,
    10 => 7,
    11 => 7,
    12 => 5
];

$friends = [
    1 => [2,3,5],
    2 => [1,4],
    3 => [1,2,5],
    4 => [1],       // Mercury
    5 => [1,2,3],
    6 => [4,7],
    7 => [4,6]
];

$equals = [
    1 => [4],
    2 => [3,5,6,7],
    3 => [6,7],
    4 => [3,5,7],
    5 => [7],
    6 => [3,5],
    7 => [5]
];

$enemies = [
    1 => [6,7],
    2 => [],
    3 => [4],
    4 => [2,6],     // Mercury -> Moon, Venus
    5 => [4,6],
    6 => [1,2],
    7 => [1,2,3]
];

$gl = $rasiLord[$girlRasi];
$bl = $rasiLord[$boyRasi];

$athiPoints = 0.0;

if ($gl === $bl) {
    $athiPoints = 1.0;
} elseif (in_array($bl, $friends[$gl], true)) {
    $athiPoints = 1.0;
} elseif (in_array($bl, $equals[$gl], true)) {
    $athiPoints = 0.5;
} elseif (in_array($bl, $enemies[$gl], true)) {
    $athiPoints = 0.0;
}
// Preserve the source's special 7th-rasi handling.
if ($girlRasi !== $boyRasi && $rasiPos === 7) {
    $bad7Lord = [
        [4,10], [10,4], [10,5], [5,11], [11,5]
    ];
    if (in_array([$girlRasi,$boyRasi], $bad7Lord, true)) {
        $athiPoints = 0.0;
    } else {
        $athiPoints = 0.5;
    }
}
$details[] = result('Rasi Athipathi Porutham', $athiPoints, poruthamMessage($athiPoints), $planetNames[$gl], $planetNames[$bl]);

/* 8. Vasya */
$vasya = [
    1=>[5,8], 2=>[4,7], 3=>[6], 4=>[8,9], 5=>[10], 6=>[2,12],
    7=>[10], 8=>[4,6], 9=>[12], 10=>[11], 11=>[12], 12=>[10]
];
$vasyaPoints = 0.0;
if (in_array($boyRasi, $vasya[$girlRasi], true)) {
    $vasyaPoints = 1.0;
} elseif (in_array($girlRasi, $vasya[$boyRasi], true)) {
    $vasyaPoints = 0.5;
}
$details[] = result('Vasiya Porutham', $vasyaPoints, poruthamMessage($vasyaPoints), $rasis[$girlRasi], $rasis[$boyRasi]);

/* 9. Rajju
 * The observed porutham.co.in result explicitly treats different Rajju
 * groups as a match even when both are in the same direction. Therefore the
 * direction is descriptive only; it is NOT a half-point penalty.
 */
$rajjuDirection = [
    1=>1,2=>1,3=>1,4=>1,5=>1,6=>2,7=>2,8=>2,9=>2,
    10=>1,11=>1,12=>1,13=>1,14=>1,15=>2,16=>2,17=>2,18=>2,
    19=>1,20=>1,21=>1,22=>1,23=>1,24=>2,25=>2,26=>2,27=>2
];
$rajjuGroup = [
    1=>1,2=>2,3=>3,4=>4,5=>5,6=>4,7=>3,8=>2,9=>1,
    10=>1,11=>2,12=>3,13=>4,14=>5,15=>4,16=>3,17=>2,18=>1,
    19=>1,20=>2,21=>3,22=>4,23=>5,24=>4,25=>3,26=>2,27=>1
];
$rajjuNames = [1=>'Pada Rajju',2=>'Thodai Rajju',3=>'Udara Rajju',4=>'Kanda Rajju',5=>'Sirasu Rajju'];
$girlRajju = $rajjuGroup[$girlstarid];
$boyRajju = $rajjuGroup[$boystarid];
$rajjuPoints = ($girlRajju === $boyRajju) ? 0.0 : 1.0;
$details[] = result('Rajju Porutham', $rajjuPoints, poruthamMessage($rajjuPoints), $rajjuNames[$girlRajju], $rajjuNames[$boyRajju]);

/* 10. Vedha */
$vedhaPairs = [
    [1,18],[2,17],[3,16],[4,15],[6,22],[7,21],[8,20],
    [9,19],[10,27],[11,26],[12,25],[13,24]
];
$vedhaPoints = 1.0;
foreach ($vedhaPairs as $pair) {
    if (($girlstarid === $pair[0] && $boystarid === $pair[1]) || ($girlstarid === $pair[1] && $boystarid === $pair[0])) {
        $vedhaPoints = 0.0;
        break;
    }
}
$details[] = result('Vedhai Porutham', $vedhaPoints, poruthamMessage($vedhaPoints), $stars[$girlstarid], $stars[$boystarid]);

/* 11. Nadi
 * Aadi/Vata: 1,6,7,12,13,18,19,24,25
 * Madhya/Pitta: 2,5,8,11,14,17,20,23,26
 * Antya/Kapha: 3,4,9,10,15,16,21,22,27
 */
$nadiGroups = [
    1=>'Aadi Nadi (Vata)', 2=>'Madhya Nadi (Pitta)', 3=>'Antya Nadi (Kapha)',
    4=>'Antya Nadi (Kapha)', 5=>'Madhya Nadi (Pitta)', 6=>'Aadi Nadi (Vata)',
    7=>'Aadi Nadi (Vata)', 8=>'Madhya Nadi (Pitta)', 9=>'Antya Nadi (Kapha)',
    10=>'Antya Nadi (Kapha)', 11=>'Madhya Nadi (Pitta)', 12=>'Aadi Nadi (Vata)',
    13=>'Aadi Nadi (Vata)', 14=>'Madhya Nadi (Pitta)', 15=>'Antya Nadi (Kapha)',
    16=>'Antya Nadi (Kapha)', 17=>'Madhya Nadi (Pitta)', 18=>'Aadi Nadi (Vata)',
    19=>'Aadi Nadi (Vata)', 20=>'Madhya Nadi (Pitta)', 21=>'Antya Nadi (Kapha)',
    22=>'Antya Nadi (Kapha)', 23=>'Madhya Nadi (Pitta)', 24=>'Aadi Nadi (Vata)',
    25=>'Aadi Nadi (Vata)', 26=>'Madhya Nadi (Pitta)', 27=>'Antya Nadi (Kapha)'
];
$nadiPoints = ($nadiGroups[$girlstarid] === $nadiGroups[$boystarid]) ? 0.0 : 1.0;
$details[] = result('Nadi Porutham', $nadiPoints, poruthamMessage($nadiPoints), $nadiGroups[$girlstarid], $nadiGroups[$boystarid]);

/* 12. Virutcham / Mara Porutham
 * The source example: Mrigashira (Karungali/Cutch, milk-bearing) +
 * Uttara Ashadha (Jackfruit, non-milk-bearing) => match.
 * The published Tamil rule used by this implementation is: if at least one
 * of the two associated trees is a milk-bearing tree, the porutham matches.
 */
$trees = [
    1=>'Etti / Poison Nut', 2=>'Nelli / Amla', 3=>'Athi / Cluster Fig',
    4=>'Naval / Jamun', 5=>'Karungali / Cutch', 6=>'Sengkarungali / Agarwood',
    7=>'Moongil / Bamboo', 8=>'Arasu / Peepal', 9=>'Punnai / Alexandrian Laurel',
    10=>'Aal / Banyan', 11=>'Palaa / Palash', 12=>'Alari / Rose Laurel',
    13=>'Velam / Hog Plum', 14=>'Vilvam / Bilva', 15=>'Marudham / Arjun',
    16=>'Vila / Wood Apple', 17=>'Magizham / Bakula', 18=>'Pirai / Red Silk Cotton',
    19=>'Maa / Mango', 20=>'Vanchi / Sita Ashoka', 21=>'Palaa / Jackfruit',
    22=>'Erukku / Milkweed', 23=>'Vanni / Shami', 24=>'Kadamba',
    25=>'Themaa / Mango', 26=>'Vembu / Neem', 27=>'Iluppai / Mahua'
];
$milkTrees = [1,2,5,6,7,14,15,16,17,23,24,26];
$girlMilk = in_array($girlstarid, $milkTrees, true);
$boyMilk = in_array($boystarid, $milkTrees, true);
$vrikshaPoints = ($girlMilk || $boyMilk) ? 1.0 : 0.0;
$details[] = result('Virutcham Porutham', $vrikshaPoints, poruthamMessage($vrikshaPoints), $trees[$girlstarid], $trees[$boystarid]);

$totalPoints = 0.0;
$fullMatches = 0;
$partialMatches = 0;
foreach ($details as $item) {
    $totalPoints += (float) $item['points'];
    if ((float) $item['points'] >= 1.0) $fullMatches++;
    elseif ((float) $item['points'] > 0.0) $partialMatches++;
}

// Return integers as integers and preserve 0.5 only where the calculation
// intentionally uses a medium state.
if (abs($totalPoints - round($totalPoints)) < 0.000001) {
    $totalPoints = (int) round($totalPoints);
}

$response = [
    'status' => 'success',
    'total_points' => $totalPoints,
    'max_points' => 12,
    'full_matches' => $fullMatches,
    'partial_matches' => $partialMatches,
    'details' => $details,
    'meta' => [
        'bride_star_id' => $girlstarid,
        'bride_pada' => $girlpadaid,
        'bride_star' => $stars[$girlstarid],
        'bride_rasi_id' => $girlRasi,
        'bride_rasi' => $rasis[$girlRasi],
        'groom_star_id' => $boystarid,
        'groom_pada' => $boypadaid,
        'groom_star' => $stars[$boystarid],
        'groom_rasi_id' => $boyRasi,
        'groom_rasi' => $rasis[$boyRasi],
        'star_distance_girl_to_boy' => $starPos,
        'rasi_distance_girl_to_boy' => $rasiPos,
        'engine' => 'nakshamilan-12-porutham-v1'
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

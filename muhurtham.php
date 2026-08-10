<?php
/**
 * Muhurtham API Wrapper for Prokerala (V2)
 *
 * Aggregates Panchang, Auspicious/Inauspicious periods, and specific Muhurtha suitability.
 * Supported dates: Any date within Prokerala's API range.
 */

header('Content-Type: application/json');

// 1. Verify Environment Variables
$clientId = getenv('PROKERALA_CLIENT_ID');
$clientSecret = getenv('PROKERALA_CLIENT_SECRET');

if (!$clientId || !$clientSecret) {
    http_response_code(500);
    die(json_encode([
        "success" => false,
        "error" => "PROKERALA_CREDENTIALS_MISSING"
    ]));
}

$baseUrl = "https://api.prokerala.com/v2/astrology/";
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

// 2. Parameters & Validation
$rawDatetime = $_GET['datetime'] ?? null;
if ($rawDatetime) {
    try {
        // Prokerala expects a full ISO 8601 string with timezone
        $dt = new DateTimeImmutable($rawDatetime);
        $datetime = $dt->format('Y-m-d\TH:i:sP');
    } catch (Exception $e) {
        $datetime = date('c');
    }
} else {
    $datetime = date('c');
}

$coordinates = $_GET['coordinates'] ?? '13.0827,80.2707'; // Default Chennai
$la = $_GET['la'] ?? 'en';
$selectedCategory = $_GET['category'] ?? 'marriage';

// Normalize Category (API slugs use hyphens)
$prokeralaCategory = str_replace('_', '-', $selectedCategory);

// 3. OAuth Handshake with Token Cache
$tokenFile = __DIR__ . '/token_cache.json';
$accessToken = null;

if (file_exists($tokenFile)) {
    $cache = json_decode(file_get_contents($tokenFile), true);
    if (isset($cache['expiry']) && $cache['expiry'] > time()) {
        $accessToken = $cache['token'];
    }
}

if (!$accessToken) {
    $ch = curl_init('https://api.prokerala.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]));

    $resRaw = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $res = json_decode($resRaw, true);

    if ($httpStatus === 200 && isset($res['access_token'])) {
        $accessToken = $res['access_token'];
        file_put_contents($tokenFile, json_encode([
            'token' => $accessToken,
            'expiry' => time() + ($res['expires_in'] ?? 3600) - 60
        ]));
    } else {
        http_response_code(401);
        die(json_encode([
            "success" => false,
            "error" => "AUTH_FAILED",
            "http_status" => $httpStatus,
            "api_message" => $res['error_description'] ?? $res['message'] ?? $curlError ?? "Unknown Error"
        ]));
    }
}

// 4. Robust Fetch Function
function fetchProkerala($endpoint, $params, $token) {
    global $baseUrl;
    $url = $baseUrl . $endpoint . '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);

    $resRaw = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $httpStatus,
        'data' => json_decode($resRaw, true),
        'error' => $curlError
    ];
}

$commonParams = [
    'datetime' => $datetime,
    'coordinates' => $coordinates,
    'la' => $la,
    'ayanamsa' => 1
];

// 5. Fetch Data Sequentially
// Panchang is critical
$panchangRes = fetchProkerala('panchang/advanced', $commonParams, $accessToken);

if ($panchangRes['status'] !== 200 || !isset($panchangRes['data']['data'])) {
    http_response_code($panchangRes['status']);
    die(json_encode([
        "success" => false,
        "endpoint" => "panchang/advanced",
        "http_status" => $panchangRes['status'],
        "error" => $panchangRes['data']['message'] ?? $panchangRes['error'] ?? "Data Fetch Failed"
    ]));
}

// Optional Endpoints
$auspiciousRes = fetchProkerala('auspicious-period', $commonParams, $accessToken);
$muhurthaRes = fetchProkerala('muhurtha/' . $prokeralaCategory, $commonParams, $accessToken);

// 6. Mapping & Date Verification
$p = $panchangRes['data']['data']['panchang'];
$ip = $panchangRes['data']['data']['inauspicious_period'] ?? [];
$ap = $auspiciousRes['data']['data']['auspicious_period'] ?? [];
$m = $muhurthaRes['data']['data']['muhurtha'] ?? null;

// Normalize response date
$responseDate = explode('T', $datetime)[0];

$mapped = [
    "date" => $responseDate,
    "dayName" => date('l', strtotime($datetime)),
    "tamilMonth" => "N/A", // Placeholder
    "lunarMonth" => "N/A", // Placeholder
    "panchang" => [
        "tithi" => $p['tithi'][0]['name'] ?? 'N/A',
        "nakshatra" => $p['nakshatra'][0]['name'] ?? 'N/A',
        "yoga" => $p['yoga'][0]['name'] ?? 'N/A',
        "karana" => $p['karana'][0]['name'] ?? 'N/A'
    ],
    "timeSlots" => [],
    "eventRecommendations" => [],
    "planetaryInfo" => [
        "sunrise" => $p['sunrise'] ?? 'N/A',
        "sunset" => $p['sunset'] ?? 'N/A',
        "rahuKalam" => 'N/A',
        "yamagandam" => 'N/A',
        "gulikai" => 'N/A',
        "abhijitMuhurta" => 'N/A',
        "durmuhurtham" => 'N/A',
        "amritaKalam" => 'N/A'
    ],
    "astrologyNotes" => []
];

// Map Inauspicious Periods
foreach ($ip as $period) {
    if (!isset($period['period'][0])) continue;
    $time = date('h:i A', strtotime($period['period'][0]['start'])) . " - " . date('h:i A', strtotime($period['period'][0]['end']));
    switch($period['id']) {
        case 'rahu_kalam': $mapped['planetaryInfo']['rahuKalam'] = $time; break;
        case 'yamaganda': $mapped['planetaryInfo']['yamagandam'] = $time; break;
        case 'gulika_kalam': $mapped['planetaryInfo']['gulikai'] = $time; break;
        case 'durmuhurtha': $mapped['planetaryInfo']['durmuhurtham'] = $time; break;
    }
}

// Map Auspicious into TimeSlots and PlanetaryInfo
foreach ($ap as $period) {
    if (!isset($period['period'][0])) continue;
    $start = date('h:i A', strtotime($period['period'][0]['start']));
    $end = date('h:i A', strtotime($period['period'][0]['end']));

    switch($period['id']) {
        case 'abhijit_muhurta': $mapped['planetaryInfo']['abhijitMuhurta'] = "$start - $end"; break;
        case 'amrit_kaalam': $mapped['planetaryInfo']['amritaKalam'] = "$start - $end"; break;
    }

    $mapped['timeSlots'][] = [
        "startTime" => $start,
        "endTime" => $end,
        "quality" => "excellent",
        "suitableEvents" => [$period['name']]
    ];
}

// Map Recommendation for selected category
if ($m) {
    $mapped['eventRecommendations'][] = [
        "eventType" => $selectedCategory,
        "starRating" => 5,
        "explanation" => "Identified as an auspicious window by Vedic calculation."
    ];
}

// 7. Debug Output
if ($debug) {
    echo json_encode([
        "success" => true,
        "requested" => [
            "datetime" => $datetime,
            "coordinates" => $coordinates,
            "language" => $la,
            "category" => $selectedCategory
        ],
        "api" => [
            "panchang" => [ "status" => $panchangRes['status'], "success" => ($panchangRes['status'] === 200) ],
            "auspicious" => [ "status" => $auspiciousRes['status'], "success" => ($auspiciousRes['status'] === 200) ],
            "muhurtha" => [ "status" => $muhurthaRes['status'], "success" => ($muhurthaRes['status'] === 200) ]
        ],
        "mapped" => $mapped
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode($mapped);
}
?>

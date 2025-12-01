<?php
require_once __DIR__ . '/includes/admin_guard.php';
enforceAdminSession(true);

$ip = $_GET['ip'] ?? '';
$ip = trim($ip);
if(!$ip){
    echo json_encode(['status'=>'fail','message'=>'IP girilmedi']);
    exit;
}

// AbuseIPDB API key
$abuseKey = config('api_keys.abuseipdb');

$abuseData = null;
if ($abuseKey) {
    $ch = curl_init("https://api.abuseipdb.com/api/v2/check?ipAddress=$ip&maxAgeInDays=90");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Key: $abuseKey","Accept: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    if($res){
        $json = json_decode($res,true);
        if(isset($json['data'])) $abuseData = $json['data'];
    }
    curl_close($ch);
}
$whoisInfo = file_get_contents("http://ip-api.com/json/$ip?fields=status,message,country,countryCode,regionName,city,zip,lat,lon,isp,org,query");
$whois = json_decode($whoisInfo,true);

echo json_encode([
    'status' => 'ok',
    'whois' => $whois,
    'abuse' => $abuseData,
]);

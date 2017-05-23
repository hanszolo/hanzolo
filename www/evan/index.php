<?
$now = new DateTime();
$then = new DateTime('2014-11-29 17:05:00');
$interval = $now->diff($then);
$hours = ($interval->invert ? -1 : 1) * (24 * $interval->days + $interval->h + ($interval->i / 60) + ($interval->s / 60 / 60));
if (!isset($_GET['statham']) && $hours < 24 && $hours > -24) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ba.com/rest-v1/v1/flights;flightNumber=191;scheduledArrivalDate=2014-11-29.json",
        CURLOPT_HTTPHEADER => array("Client-Key: mg2vtsj2vazrmmrdnc3xrdfy"),
        CURLOPT_RETURNTRANSFER => True
    ));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_status == 200) {
        $result_json = json_decode($result, True);
        $arrival = new DateTime($result_json['FlightsResponse']['Flight']['Sector']['ReportedArrivalDateTime']);
        $interval = $now->diff($arrival);
        $hours = ($interval->invert ? -1 : 1) * (24 * $interval->days + $interval->h + ($interval->i / 60) + ($interval->s / 60 / 60));
    }
}
$hours = isset($_GET['statham']) ? $_GET['statham'] : $hours;
?>
<html>
<head>
<title><?= ($hours > 0) ? "A lame parody of Crank" : "Evan: High Voltage (Coming Soon!)" ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/styles/evan.css">
</head>
<body class="<?= ($hours > 0) ? 'uk' : 'us' ?> flag">
    <div class="content">
        <? if ($hours > 0): ?>
        <div class="subtitle w">To stay continuously energized until his repatriation, Evan needs:</div>
        <br><br>
        <div class="title w"><span class="vcenter w"><? printf("%d", ceil($hours / 5)); ?> more</span><img class="vcenter" style="height:3em;" src="http://imgur.com/rQpBEINm.jpg"></div>
        <? else: ?>
        <div class="title w"><img class="vcenter" style="height:3em;" src="http://imgur.com/rQpBEINm.jpg"><span class="vcenter w">Complete</span></div>
        <br><br>
        <img class="vcenter w" src="http://i.imgur.com/vzDJVgv.png">
        <br><br>
        <? endif; ?>
    </div>
</body>
</html>

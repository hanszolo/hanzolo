<?
define("ETSY_API_KEY", "criutu6fkg9avxuyqd35l1pg");

$stopWords = ["and", "of", "if", "the", "so", "it", "in", "i", "has", "had", "for", "to", "too", "but", "by", "do", "did"];

function safeArrayAccess($array, $path = [], $defaultValue = NULL) {
	if (!is_array($path)) {
		$path = [$path];
	}
	$current = $array;
	foreach ($path as $element) {
		if (is_array($current) && array_key_exists($element, $current)) {
			$current = $current[$element];
		} else {
			return $defaultValue;
		}
	}
	return $current;
}

function returnError($message) {
	header('Content-Type: application/json');
	echo json_encode([
		'success' => FALSE,
		'error' => $message,
	]);
	die;
}

function curl_json($url) {
	$ch = curl_init();
	$curl_opt = [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => 1,
	];
	curl_setopt_array($ch, $curl_opt);
	$result_json = curl_exec($ch);
	curl_close($ch);

	$result =  json_decode($result_json, TRUE);
	return $result;
}

function callEtsyApi($shopName, array $data = []) {
	$base_url = "https://openapi.etsy.com/v2/shops/$shopName/listings/active?api_key=" . ETSY_API_KEY;
	if ($data) {
		foreach ($data as $key => $value) {
			$key = urlencode($key);
			$value = urlencode($value);
			$base_url .= "&$key=$value";
		}
	}
	return curl_json($base_url);
}

function getStoreListings($shopName) {
	$limit = 25;
	$offset = 0;
	$count = 0;
	$listings = [];
	do {
		$results = callEtsyApi($shopName, ['limit' => $limit, 'offset' => $offset]);
		$count = safeArrayAccess($results, 'count');
		if (!$count) {
			returnError("Something went wrong with the API");
		}
		$listings = array_merge($listings, safeArrayAccess($results, 'results', []));
		$offset += $limit;
	} while ($offset < $count);
	return $listings;
}

function getTermHistogram($listings) {
	$histogram = [];
	foreach ($listings as $listing) {
		foreach (['title', 'description'] as $field) {
			$string = trim(urldecode(safeArrayAccess($listing, $field)));
			if (!$string) {
				continue;
			}
			foreach (preg_split('/\s+/', $string) as $token) {
				$histogram[$token] = safeArrayAccess($histogram, $token) + 1;
			}
		}
	}
	uasort($histogram, function ($a, $b) {
		return intval($b) - intval($a);
	});
	return $histogram;
}

$limit = safeArrayAccess($_REQUEST, 'limit');
$limit = intval($limit) ?: 10;

$shop = trim(safeArrayAccess($_REQUEST, 'shop'));
if (!$shop) {
	returnError("No shop provided");
}

$listings = getStoreListings($shop);
$histogram = getTermHistogram($listings);
$topTerms = array_slice($histogram, 0, $limit);

header('Content-Type: application/json');
echo json_encode([
	'success' => TRUE,
	'data' => $topTerms,
]); die;
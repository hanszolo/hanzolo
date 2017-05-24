<?
require_once "wordutils.php";
define("ETSY_API_KEY", "criutu6fkg9avxuyqd35l1pg");

/**
 * This function is here so I don't keep having to check array_key_exists
 * @param array $array
 * @param mixed $path
 * @param mixed $defaultValue
 * @return safeArrayAccess($array, ['a', 'b'], FALSE);
 * 		will return $array['a']['b'] if it exists, otherwise it'll return FALSE
 */
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
	$coefficients = [
		'title' => 3,
		'description' => 1,
	];
	foreach ($listings as $listing) {
		foreach ($coefficients as $field => $weight) {
			$localHistogram = [];
			$string = trim(urldecode(safeArrayAccess($listing, $field)));
			if (!$string) {
				continue;
			}

			foreach (preg_split('/\s+/', $string) as $token) {
				$token = cleanUp($token);
				$canonized = canonize($token);
				if (!$canonized) {
					continue;
				}
				if (stopword($canonized)) {
					continue;
				}

				$localHistogram[$canonized] = [
					'original' => $token,
					'score' => safeArrayAccess($histogram, [$canonized, 'score'], 0) + $weight,
				];
			}

			foreach($localHistogram as $canonized => $values) {
				$values['score'] /= sqrt(count($localHistogram) + 10);
				if (!isset($histogram[$canonized])) {
					$histogram[$canonized] = $values;
				} else {
					$histogram[$canonized]['score'] += $values['score'];
				}
			}
		}
	}
	uasort($histogram, function ($a, $b) {
		return intval($b['score']) - intval($a['score']);
	});
	return $histogram;
}

function formatHistogram($histogram, $limit) {
	$results = [];
	foreach ($histogram as $canonized => $values) {
		$results[$values['original']] = $values['score'];
		$limit--;
		if ($limit <= 0) {
			break;
		}
	}
	return $results;
}

$limit = safeArrayAccess($_REQUEST, 'limit');
$limit = intval($limit) ?: 10;

$shop = trim(safeArrayAccess($_REQUEST, 'shop'));
if (!$shop) {
	returnError("No shop provided");
}

$listings = getStoreListings($shop);
$histogram = getTermHistogram($listings);
$topTerms = formatHistogram($histogram, $limit);

header('Content-Type: application/json');
echo json_encode([
	'success' => TRUE,
	'data' => $topTerms,
]); die;
#!/usr/bin/php
<?php
$baseUrl = 'https://your.external.url/noauth';
$apiToken = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$shortopts = 'h:s:l:o:c:a:t:';
$longopts = array('hostname:', 'hoststate:', 'datetime:', 'hostoutput:', 'notificationcomment:', 'notificationauthor:', 'contactalias:');
$options = getopt($shortopts, $longopts);

foreach ($options as $k => $v) {
	switch ($k) {
		case 'h':
		case 'hostname':
			$hostname = $v;
			break;
		case 's':
		case 'hoststate':
			$hoststate = $v;
			break;
		case 'l':
		case 'datetime':
			$datetime = $v;
			break;
		case 'o':
		case 'hostoutput':
			$hostoutput = $v;
			break;
		case 'c':
		case 'notificationcomment':
			$notificationcomment = $v;
			break;
		case 'a':
		case 'notificationauthor':
			$notificationauthor = $v;
			break;
		case 't':
		case 'contactalias':
			$contactalias = $v;
			break;
	}
}

function shortenUrl($longUrl) {
	global $apiToken;

	$apiUrl = 'http://api.isus.cc/shorten';
	$longUrl = preg_replace('/ /','+', $longUrl);

	$ch = curl_init();

	$fields = array(
		'token' => $apiToken,
		'url' => $longUrl
	);

	$curlopts = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_CONNECTTIMEOUT => 1,
		CURLOPT_POSTFIELDS => http_build_query($fields),
		CURLOPT_TIMEOUT => 2,
		CURLOPT_POST => true,
		CURLOPT_URL => $apiUrl
	);
	curl_setopt_array($ch, $curlopts);

	$shortUrl = curl_exec($ch);

	curl_close($ch);

	if ($shortUrl !== FALSE) {
		$shortUrl = json_decode($shortUrl);

		if ($shortUrl->status_code == 200) {
			return $shortUrl->data->url;
		}
	}

	return $longUrl;
}

if ($notificationcomment && $notificationauthor) {
	$additional = <<<EOM
Comment: {$notificationcomment}
Author: {$notificationauthor}
EOM;
} else {
	$acknowledgeUrl	= shortenUrl(sprintf('%s/?cmd=39&host_name=%s&sticky=0&author=%s&comment=Problem acknowledged', $baseUrl, $hostname, $contactalias));
	$availableUrl = shortenUrl(sprintf('%s/?cmd=134&host_name=%s&options=1&author=%s&comment=Available to help', $baseUrl, $hostname, $contactalias));
	$unavailableUrl = shortenUrl(sprintf('%s/?cmd=134&host_name=%s&options=1&author=%s&comment=Currently unavailable', $baseUrl, $hostname, $contactalias));
	$helpUrl = shortenUrl(sprintf('%s/?cmd=134&host_name=%s&options=1&author=%s&comment=Need help', $baseUrl, $hostname, $contactalias));

	$additional = <<<EOM
Acknowledge: {$acknowledgeUrl}

Available: {$availableUrl}

Unavailable: {$unavailableUrl}

Need Help: {$helpUrl}
EOM;
}

$message = <<<EOM
{$hostname} is {$hoststate}
Time: {$datetime}

Info: {$hostoutput}

{$additional}
EOM;

echo $message;
?>

<?php

require 'PlaylistDecoder.php';
require 'SimpleCurlConnection.php';

function decodePlaylistUrl($url, $contentType)
{
    $conn = new SimpleCurlConnection();
    $content = $conn->file_get_contents_curl($url);//, false, NULL, -1, 4096);
    if ($content !== false){
        $decoder = new PlaylistDecoder();
        return $decoder->decodePlayListContent($url, $contentType, $content);
    }
    return array();
}

function getItemFromDict($dict, $keyWanted)
{
    foreach ($dict as $key => $value) {
        if (strtolower($key) === strtolower($keyWanted)) {
            if (is_array($value)) {
                if (count($value) > 0) {
                    $value = $value[0];
                } else {
                    $value = false;
                }
            }

            return $value;
        }
    }

    return false;
}

function checkStationConnectionById($db, $stationid, $url, &$bitrate, &$codec, &$log){
    $audiofile = checkStation($url, $bitrate, $codec, $log);
    if ($audiofile !== false) {
        $stmt = $db->prepare('UPDATE Station SET LastCheckTime=NOW(), LastCheckOK=TRUE,Bitrate=:bitrate,Codec=:codec,UrlCache=:cacheurl WHERE StationID=:stationid');
        $stmt->execute(['bitrate' => $bitrate, 'codec' => $codec, 'stationid' => $stationid, 'cacheurl' => $audiofile]);
        return true;
    } else {
        $stmt = $db->prepare('UPDATE Station SET LastCheckTime=NOW(), LastCheckOK=FALSE, UrlCache="" WHERE StationID=:stationid');
        $stmt->execute(['stationid' => $stationid]);
        return false;
    }
}

function decodeStatusCode($headers, &$log){
    $log = array();
    if ($headers === false) {
        array_push($log, " - Headers could not be retrieved!");
        return false;
    }

    if (count($headers) == 0){
        array_push($log, " - Empty headers!");
        return false;
    }

    $status = $headers[0];
    $statusArr = explode(" ",$status);
    if (count($statusArr) < 2){
        array_push($log, " - non standard http header! ".$status);
        return false;
    }
    $statusCode = intval($statusArr[1]);
    array_push($log, " - Status:".$statusCode);
    return $statusCode;
}

function checkStation($url, &$bitrate, &$codec, &$log)
{
    ini_set("user_agent","VLC/2.2.2 LibVLC/2.2.2");
    $decoder = new PlaylistDecoder();
    $log = array();
    for ($tries=0;$tries<10;$tries++){
        if (!hasCorrectScheme($url)){
            array_push($log, " - Incorrect url scheme!");
            return false;
        }

        $location = false;

        array_push($log, " - Get headers from: ".$url);
        $headers = @get_headers($url,1);
        $statusCode = decodeStatusCode($headers, $logStatusCode);
        $log = array_merge($log,$logStatusCode);
        if ($statusCode === 400 || $statusCode === 404 || $statusCode === 500 || $statusCode === false) {
          array_push($log, " - try to connect with curl: ".$url);
          $conn = new SimpleCurlConnection();
          $headers = $conn->get_headers_curl($url);
          $statusCode = decodeStatusCode($headers, $logStatusCode);
          $log = array_merge($log,$logStatusCode);
          if ($statusCode === 404 || $statusCode === false) {
              return false;
          }
        }
        // print_r($headers);

        if ($statusCode === 200){
            $contentType = strtolower(getItemFromDict($headers, 'content-type'));

            if ($contentType !== false) {
                $contentTypeArray = explode(";",$contentType);
                if (count($contentTypeArray) > 1){
                    $contentType = $contentTypeArray[0];
                }
                array_push($log, ' - Content: '.$contentType);
                $codec = false;

                if ($contentType === 'audio/mpeg' || $contentType === 'audio/mp3') {
                    $codec = 'MP3';
                } elseif ($contentType === 'audio/aac') {
                    $codec = 'AAC';
                } elseif ($contentType === 'audio/x-aac') {
                    $codec = 'AAC';
                } elseif ($contentType === 'audio/aacp') {
                    $codec = 'AAC+';
                } elseif ($contentType === 'audio/ogg') {
                    $codec = 'OGG';
                } elseif ($contentType === 'application/ogg') {
                    $codec = 'OGG';
                } elseif ($contentType === 'audio/flac') {
                    $codec = 'FLAC';
                } elseif ($contentType === 'application/flv') {
                    $codec = 'FLV';
                } elseif ($contentType === 'application/octet-stream') {
                    $codec = 'UNKNOWN';
                } elseif ($contentType === 'text/html') {
                    $codec = '';
                    return false;
                } elseif ($decoder->isContentTypePlaylist($contentType)) {
                    $urls = decodePlaylistUrl($url,$contentType);
                    if (count($urls) === 0){
                        array_push($log, " - could not decode playlist");
                        return false;
                    }
                    $url = $urls[0];
                    array_push($log, " - Playlist URL: ".$url);
                    continue;
                } else {
                    $codec = 'UNKNOWN';
                    array_push($log, " - Unknown codec for content type");
                }

                array_push($log, ' - Codec: '.$codec);
            } else {
                $codec = '';
            }

            $bitrate = getItemFromDict($headers, 'icy-br');
            if ($bitrate !== false) {
                array_push($log, ' - Bitrate: '.$bitrate);
            } else {
                $bitrate = 0;
            }
            return $url;
        }else if ($statusCode === 301 || $statusCode === 302 || $statusCode === 307){
            $location = getItemFromDict($headers, 'Location');
            if ($location !== false) {
                array_push($log, ' - Redirect:'.$location);
                $url = $location;
            }else{
                array_push($log, " - Location header field needed!");
                return false;
            }
        }else{
            array_push($log, " - http status code != 200");
            return false;
        }
    }

    return false;
}

function hasCorrectScheme($url)
{
    if ((strtolower(substr($url, 0, 7)) === 'http://') || (strtolower(substr($url, 0, 8)) === 'https://')) {
        return true;
    }

    return false;
}

function FixUrl($url, $base = null)
{
    $url = str_replace('file://','',$url);

    if (!hasCorrectScheme($base)){
        $base = null;
    }

    if (!hasCorrectScheme($url)) {
        if ($base === null){
            $url = 'http://'.$url;
        }else{
            $url = $base."/".$url;
        }
    }

    return $url;
}

function getLinkContent($url)
{
    if (!hasCorrectScheme($url)) {
        return null;
    }

    $content = @file_get_contents($url);
    if ($content){
        return $content;
    }
    return null;
}

function getBaseUrl($url)
{
    $parsed_url = parse_url($url);
    if ($parsed_url) {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        return "$scheme$host$port";
    }

    return null;
}

function extractIconLink($html, $base, &$log){
    $images = array();
    $log = array();
    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    foreach($dom->getElementsByTagName('base') as $link) {
        $base_new = $link->getAttribute('href');
        if (hasCorrectScheme($base_new)){
            $base = $base_new;
        }else{
            $base = $base."/".$base_new;
        }
    }

    foreach($dom->getElementsByTagName('meta') as $link) {
        // check microsoft link
        // <meta name="msapplication-TileImage" content="http://..." />
        $name = $link->getAttribute('name');
        if ($name === "msapplication-TileImage"){
            array_push($log, "Found meta-tag msapplication-TileImage");
            array_push($images, FixUrl($link->getAttribute('content'),$base));
        }
        if ($name === "msapplication-square70x70logo"){
            array_push($log, "Found meta-tag msapplication-square70x70logo");
            array_push($images, FixUrl($link->getAttribute('content'),$base));
        }
        if ($name === "msapplication-square150x150logo"){
            array_push($log, "Found meta-tag msapplication-square150x150logo");
            array_push($images, FixUrl($link->getAttribute('content'),$base));
        }
        if ($name === "msapplication-square310x310logo"){
            array_push($log, "Found meta-tag msapplication-square310x310logo");
            array_push($images, FixUrl($link->getAttribute('content'),$base));
        }
        if ($name === "msapplication-wide310x150logo"){
            array_push($log, "Found meta-tag msapplication-wide310x150logo");
            array_push($images, FixUrl($link->getAttribute('content'),$base));
        }

        // support for open graph
        // <meta property="og:image" content="http://..." />
        $property = $link->getAttribute('property');
        if ($property === "og:image"){
            array_push($log, "Found meta-tag property='og:image'");
            array_push($images, FixUrl($link->getAttribute('content'),$base));
        }
    }

    // check apple icon link
    foreach($dom->getElementsByTagName('link') as $link) {
        $rel = $link->getAttribute('rel');

        if ($rel === "apple-touch-icon"){
            array_push($log, "Found link-tag rel='apple-touch-icon'");
            array_push($images, FixUrl($link->getAttribute('href'),$base));
        }
    }

    // check shortcut icon link
    foreach($dom->getElementsByTagName('link') as $link) {
        $rel = $link->getAttribute('rel');

        if ($rel === "shortcut icon"){
            array_push($log, "Found link-tag rel='shortcut icon'");
            array_push($images, FixUrl($link->getAttribute('href'),$base));
        }
        if ($rel === "icon"){
            array_push($log, "Found link-tag rel='icon'");
            array_push($images, FixUrl($link->getAttribute('href'),$base));
        }
    }
    array_push($images, $base."/favicon.ico");
    array_push($log, "Did not find any usable html tags with structured icon information on page");
    return $images;
}

function extractFaviconFromUrl($hp, &$log){
    $log = array();
    if (hasCorrectScheme($hp))
    {
        $base = getBaseUrl($hp);
        if ($base !== null)
        {
            array_push($log, "Extracted base adress: ".$base);
            $hpContent = getLinkContent($hp);
            if ($hpContent !== null){
                $images = extractIconLink($hpContent, $base, $logExtract);
                $log = array_merge($log, $logExtract);
                return $images;
            }
        }else{
          array_push($log, "Could not extract host from link");
        }
    }else{
        array_push($log, "Incorrect link scheme, use only http:// or https://");
    }
    return array();
}

?>

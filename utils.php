<?php

function isContentTypePlaylist($contentType){
    return isContentTypePlaylistM3U($contentType) || isContentTypePlaylistPLS($contentType) || isContentTypePlaylistASX($contentType);
}

function isContentTypePlaylistM3U($contentType){
    $contentType = strtolower($contentType);

    $types = array(
      "application/mpegurl",
      "application/x-mpegurl",
      "audio/mpegurl",
      "audio/x-mpegurl",
      "application/vnd.apple.mpegurl",
      "application/vnd.apple.mpegurl.audio"
    );

    return in_array($contentType, $types);
}

function isContentTypePlaylistPLS($contentType){
    $contentType = strtolower($contentType);

    $types = array(
      "audio/x-scpls",
      "application/pls+xml"
    );

    return in_array($contentType, $types);
}

function isContentTypePlaylistASX($contentType){
    $contentType = strtolower($contentType);

    $types = array(
      "video/x-ms-asf"
    );

    return in_array($contentType, $types);
}

function decodePlaylistUrlM3U($content){
    // replace different kinds of newline with the default
    $content = str_replace(array("\r\n","\n\r","\r"),"\n",$content);
    $lines = explode("\n",$content);

    foreach ($lines as $line) {
        if (substr(trim($line), 0, 1) != '#') {
            if (trim($line) !== '') {
                return trim($line);
            }
        }
    }
    return null;
}

function decodePlaylistUrlPLS($content){
    // replace different kinds of newline with the default
    $content = str_replace(array("\r\n","\n\r","\r"),"\n",$content);
    $lines = explode("\n",$content);

    foreach ($lines as $line) {
        if (substr(trim($line), 0, 4) == 'File') {
            $pos = strpos($line, '=');
            if ($pos !== false) {
                $value = substr($line, $pos + 1);
                return trim($value);
            }
        }
    }
    return null;
}

function decodePlaylistUrlASX($content){
    $xml = @simplexml_load_string(strtolower($content));
    if ($xml !== false) {
        foreach ($xml->entry as $entry) {
            foreach ($entry->ref as $ref) {
                if (isset($ref['href'])) {
                    return $ref['href'];
                }
            }
        }
    }
    return null;
}


function decodePlaylistUrl($url, $contentType)
{
    // read max 4KB
    $content = @file_get_contents($url, false, NULL, -1, 4096);

    if ($content !== false){
        if (isContentTypePlaylistM3U($contentType)){
            return decodePlaylistUrlM3U($content);
        }
        if (isContentTypePlaylistPLS($contentType)){
            return decodePlaylistUrlPLS($content);
        }
        if (isContentTypePlaylistASX($contentType)){
            return decodePlaylistUrlASX($content);
        }
    }

    return false;
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

function checkStationConnectionById($db, $stationid, $url){
    $working = checkStation($url, $bitrate, $codec);
    if ($working === true) {
        echo " - WORKING\n";
        $stmt = $db->prepare('UPDATE Station SET LastCheckTime=NOW(), LastCheckOK=TRUE,Bitrate=:bitrate,Codec=:codec WHERE StationID=:stationid');
        $stmt->execute(['bitrate' => $bitrate, 'codec' => $codec, 'stationid' => $stationid]);
    } else {
        echo " - NOT WORKING\n";
        $stmt = $db->prepare('UPDATE Station SET LastCheckTime=NOW(), LastCheckOK=FALSE WHERE StationID=:stationid');
        $stmt->execute(['stationid' => $stationid]);
    }
}

function checkStation($url, &$bitrate, &$codec)
{
    for ($tries=0;$tries<10;$tries++){
        if (!hasCorrectScheme($url)){
            echo " - Incorrect url scheme!\n";
            return false;
        }

        $location = false;
        $headers = @get_headers($url, 1);
        // print_r($headers);
        if ($headers === false) {
            echo " - Headers could not be retrieved!\n";
            return false;
        }

        if (count($headers) == 0){
            echo " - Empty headers!\n\n";
            return false;
        }

        $status = $headers[0];
        $statusArr = explode(" ",$status);
        if (count($statusArr) < 2){
            echo " - non standard http header! ".$status."\n";
            return false;
        }
        $statusCode = $statusArr[1];
        echo " - Status:".$statusCode."\n";
        if ($statusCode === "200"){
            $contentType = strtolower(getItemFromDict($headers, 'content-type'));

            if ($contentType !== false) {
                $contentTypeArray = explode(";",$contentType);
                if (count($contentTypeArray) > 1){
                    $contentType = $contentTypeArray[0];
                }
                echo ' - Content: '.$contentType."\n";
                $codec = false;

                if ($contentType === 'audio/mpeg' || $contentType === 'audio/mp3') {
                    $codec = 'MP3';
                } elseif ($contentType === 'audio/aac') {
                    $codec = 'AAC';
                } elseif ($contentType === 'audio/aacp') {
                    $codec = 'AAC+';
                } elseif ($contentType === 'audio/ogg') {
                    $codec = 'OGG';
                } elseif ($contentType === 'application/ogg') {
                    $codec = 'OGG';
                } elseif ($contentType === 'audio/flac') {
                    $codec = 'FLAC';
                } elseif ($contentType === 'application/octet-stream') {
                    $codec = 'UNKNOWN';
                } elseif ($contentType === 'text/html') {
                    $codec = '';
                    return false;
                } elseif (isContentTypePlaylist($contentType)) {
                    $url = decodePlaylistUrl($url,$contentType);
                    if ($url === false){
                        echo " - could not decode playlist\n";
                        return false;
                    }
                    echo " - Playlist URL: ".$url."\n";
                    continue;
                } else {
                    echo " - Unknown codec for content type\n";
                }

                if ($codec !== false) {
                    echo ' - Codec: '.$codec."\n";
                } else {
                    $codec = '';
                }
            } else {
                $codec = '';
            }

            $bitrate = getItemFromDict($headers, 'icy-br');
            if ($bitrate !== false) {
                echo ' - Bitrate: '.$bitrate."\n";
            } else {
                $bitrate = 0;
            }
            break;
        }else if ($statusCode === "301" || $statusCode === "302"){
            $location = getItemFromDict($headers, 'Location');
            if ($location !== false) {
                echo ' - Redirect:'.$location."\n";
                $url = $location;
            }else{
                echo " - Location header field needed!\n";
                return false;
            }
        }else{
            echo " - http status code != 200\n";
            return false;
        }
    }

    if ($statusCode !== "200"){
        return false;
    }

    return true;
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

function extractIconLink($html, $base){
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
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-square70x70logo"){
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-square150x150logo"){
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-square310x310logo"){
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-wide310x150logo"){
            return FixUrl($link->getAttribute('content'),$base);
        }

        // support for open graph
        // <meta property="og:image" content="http://..." />
        $property = $link->getAttribute('property');
        if ($property === "og:image"){
            return FixUrl($link->getAttribute('content'),$base);
        }
    }

    // check apple icon link
    foreach($dom->getElementsByTagName('link') as $link) {
        $rel = $link->getAttribute('rel');

        if ($rel === "apple-touch-icon"){
            return FixUrl($link->getAttribute('href'),$base);
        }
    }

    // check shortcut icon link
    foreach($dom->getElementsByTagName('link') as $link) {
        $rel = $link->getAttribute('rel');

        if ($rel === "shortcut icon"){
            return FixUrl($link->getAttribute('href'),$base);
        }
        if ($rel === "icon"){
            return FixUrl($link->getAttribute('href'),$base);
        }
    }
    return null;
}

function extractFaviconFromUrl($hp){
    if (hasCorrectScheme($hp))
    {
        $base = getBaseUrl($hp);
        if ($base !== null)
        {
            $hpContent = getLinkContent($hp);
            if ($hpContent !== null){
                return extractIconLink($hpContent, $base);
            }
        }
    }
    return null;
}

?>

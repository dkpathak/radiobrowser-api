<?php

function fn_CURLOPT_HEADERFUNCTION($ch, $str){
    global $headers;
    $len = strlen($str);
    $itemArr = explode(":",$str,2);
    if (count($headers) == 0){
      $headers[0] = trim($str);
    }else if (count($itemArr) == 2){
      $headers[$itemArr[0]] = trim($itemArr[1]);
    }
    return $len;
  }

function get_headers_curl($url){
    global $headers;
    // erzeuge einen neuen cURL-Handle
    $ch = curl_init();

    // setze die URL und andere Optionen
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, "fn_CURLOPT_HEADERFUNCTION"); // handle received headers
    // curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'fn_CURLOPT_WRITEFUNCTION'); // callad every CURLOPT_BUFFERSIZE
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // more progress info
    // curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($DownloadSize, $Downloaded, $UploadSize, $Uploaded){
    //     return ($Downloaded > (1 * 1024)) ? 1 : 0;
    // });

    // führe die Aktion aus und gib die Daten an den Browser weiter
    $headers = array();
    $result = curl_exec($ch);

    // schließe den cURL-Handle und gib die Systemresourcen frei
    curl_close($ch);

    return $headers;
}

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
      "application/x-scpls",
      "application/pls+xml"
    );

    return in_array($contentType, $types);
}

function isContentTypePlaylistASX($contentType){
    $contentType = strtolower($contentType);

    $types = array(
      "video/x-ms-asf",
      "video/x-ms-asx"
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

function decodePlaylistUrlALL($content){
    $resultXML = decodePlaylistUrlASX($content);
    if ($resultXML != null){
      return $resultXML;
    }
    // replace different kinds of newline with the default
    $content = str_replace(array("\r\n","\n\r","\r"),"\n",$content);
    $lines = explode("\n",$content);

    foreach ($lines as $line) {
        $line = trim($line);
        if (substr($line, 0, 4) == 'File') {
            $pos = strpos($line, '=');
            if ($pos !== false) {
                $value = substr($line, $pos + 1);
                return trim($value);
            }
        }
        if (hasCorrectScheme($line)){
            return $line;
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
            $result = fixPlaylistItem($url,decodePlaylistUrlM3U($content));
            if ($result !== null){
                return $result;
            }
            return decodePlaylistUrlALL($content);
        }
        if (isContentTypePlaylistPLS($contentType)){
            $result = fixPlaylistItem($url, decodePlaylistUrlPLS($content));
            if ($result !== null){
                return $result;
            }
            return decodePlaylistUrlALL($content);
        }
        if (isContentTypePlaylistASX($contentType)){
            $result = fixPlaylistItem($url, decodePlaylistUrlASX($content));
            if ($result !== null){
                return $result;
            }
            return decodePlaylistUrlALL($content);
        }
    }

    return false;
}

function fixPlaylistItem($url, $playlistItem){
    if ($playlistItem !== false){
        if (!hasCorrectScheme($playlistItem)){
            $remoteDir = getRemoteDirUrl($url);
            if ($remoteDir !== false){
                return $remoteDir."/".$playlistItem;
            }
            return false;
        }
    }
    return $playlistItem;
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
        $stmt = $db->prepare('UPDATE Station SET LastCheckTime=NOW(), LastCheckOK=TRUE,Bitrate=:bitrate,Codec=:codec WHERE StationID=:stationid');
        $stmt->execute(['bitrate' => $bitrate, 'codec' => $codec, 'stationid' => $stationid]);
        return true;
    } else {
        $stmt = $db->prepare('UPDATE Station SET LastCheckTime=NOW(), LastCheckOK=FALSE WHERE StationID=:stationid');
        $stmt->execute(['stationid' => $stationid]);
        return true;
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
        if ($statusCode === 404 || $statusCode === false) {
          array_push($log, " - try to connect with curl: ".$url);
          $headers = get_headers_curl($url);
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
                } elseif (isContentTypePlaylist($contentType)) {
                    $url = decodePlaylistUrl($url,$contentType);
                    if ($url === false){
                        array_push($log, " - could not decode playlist");
                        return false;
                    }
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
        }else if ($statusCode === 301 || $statusCode === 302){
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

function getRemoteDirUrl($url)
{
    $parsed_url = parse_url($url);
    if ($parsed_url) {
        print_r($parsed_url);
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        $path = isset($parsed_url['path']) ? dirname($parsed_url['path']) : '';
        return "$scheme$host$port$path";
    }

    return null;
}

function extractIconLink($html, $base, &$log){
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
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-square70x70logo"){
            array_push($log, "Found meta-tag msapplication-square70x70logo");
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-square150x150logo"){
            array_push($log, "Found meta-tag msapplication-square150x150logo");
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-square310x310logo"){
            array_push($log, "Found meta-tag msapplication-square310x310logo");
            return FixUrl($link->getAttribute('content'),$base);
        }
        if ($name === "msapplication-wide310x150logo"){
            array_push($log, "Found meta-tag msapplication-wide310x150logo");
            return FixUrl($link->getAttribute('content'),$base);
        }

        // support for open graph
        // <meta property="og:image" content="http://..." />
        $property = $link->getAttribute('property');
        if ($property === "og:image"){
            array_push($log, "Found meta-tag property='og:image'");
            return FixUrl($link->getAttribute('content'),$base);
        }
    }

    // check apple icon link
    foreach($dom->getElementsByTagName('link') as $link) {
        $rel = $link->getAttribute('rel');

        if ($rel === "apple-touch-icon"){
            array_push($log, "Found link-tag rel='apple-touch-icon'");
            return FixUrl($link->getAttribute('href'),$base);
        }
    }

    // check shortcut icon link
    foreach($dom->getElementsByTagName('link') as $link) {
        $rel = $link->getAttribute('rel');

        if ($rel === "shortcut icon"){
            array_push($log, "Found link-tag rel='shortcut icon'");
            return FixUrl($link->getAttribute('href'),$base);
        }
        if ($rel === "icon"){
            array_push($log, "Found link-tag rel='icon'");
            return FixUrl($link->getAttribute('href'),$base);
        }
    }
    array_push($log, "Did not find any usable html tags with structured icon information on page");
    return null;
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
                $extractOK = extractIconLink($hpContent, $base, $logExtract);
                $log = array_merge($log, $logExtract);
                return $extractOK;
            }
        }else{
          array_push($log, "Could not extract host from link");
        }
    }else{
        array_push($log, "Incorrect link scheme, use only http:// or https://");
    }
    return null;
}

?>

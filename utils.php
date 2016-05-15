<?php

function decodePlaylistUrl($url)
{
    $str_arr = explode("\?", $url);
    if (count($str_arr) > 1) {
        $extension = strtolower(substr($str_arr[0], -4));
    } else {
        $extension = strtolower(substr($url, -4));
    }

    $audiofile = false;

    // resolve playlists
    if ($extension == '.m3u') {
        $handle = @fopen($url, 'r');
        if ($handle !== false) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                if (substr(trim($buffer), 0, 1) != '#') {
                    if (trim($buffer) !== '') {
                        $audiofile = trim($buffer);
                        break;
                    }
                }
            }
            fclose($handle);
        }
    } elseif ($extension == '.pls') {
        $handle = @fopen($url, 'r');
        if ($handle !== false) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                if (substr(trim($buffer), 0, 4) == 'File') {
                    $pos = strpos($buffer, '=');
                    if ($pos !== false) {
                        $value = substr($buffer, $pos + 1);
                        $audiofile = trim($value);
                        break;
                    }
                }
            }
            fclose($handle);
        }
    } elseif ($extension == '.asx') {
        $handle = @fopen($url, 'r');
        if ($handle !== false) {
            $contents = '';
            while (!feof($handle)) {
                $contents .= fread($handle, 8192);
            }
            fclose($handle);

            $xml = @simplexml_load_string(strtolower($contents));
            if ($xml !== false) {
                foreach ($xml->entry as $entry) {
                    foreach ($entry->ref as $ref) {
                        if (isset($ref['href'])) {
                            $audiofile = $ref['href'];
                        }
                    }
                }
            }
        }
    } else {
        $audiofile = $url;
    }

    return $audiofile;
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

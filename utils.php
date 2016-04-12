<?php

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

<?php

class PlaylistDecoder{
    public function isContentTypePlaylist($contentType){
        return $this->isContentTypePlaylistM3U($contentType) || $this->isContentTypePlaylistPLS($contentType) || $this->isContentTypePlaylistASX($contentType);
    }

    private function isContentTypePlaylistM3U($contentType){
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

    private function isContentTypePlaylistPLS($contentType){
        $contentType = strtolower($contentType);

        $types = array(
          "audio/x-scpls",
          "application/x-scpls",
          "application/pls+xml"
        );

        return in_array($contentType, $types);
    }

    private function isContentTypePlaylistASX($contentType){
        $contentType = strtolower($contentType);

        $types = array(
          "video/x-ms-asf",
          "video/x-ms-asx"
        );

        return in_array($contentType, $types);
    }

    private function decodePlaylistUrlM3U($content){
        // replace different kinds of newline with the default
        $content = str_replace(array("\r\n","\n\r","\r"),"\n",$content);
        $lines = explode("\n",$content);
        $urls = array();

        foreach ($lines as $line) {
            if (strtolower(trim($line)) === "[playlist]"){
                break;
            }
            if (substr(trim($line), 0, 1) != '#') {
                if (trim($line) !== '') {
                    array_push($urls, trim($line));
                }
            }
        }
        return $urls;
    }

    private function decodePlaylistUrlPLS($content){
        // replace different kinds of newline with the default
        $content = str_replace(array("\r\n","\n\r","\r"),"\n",$content);
        $lines = explode("\n",$content);
        $urls = array();

        foreach ($lines as $line) {
            if (substr(trim($line), 0, 4) == 'File') {
                $pos = strpos($line, '=');
                if ($pos !== false) {
                    $value = substr($line, $pos + 1);
                    array_push($urls,trim($value));
                }
            }
        }
        return $urls;
    }

    private function decodePlaylistUrlASX($content){
        $urls = array();
        $xml = @simplexml_load_string(strtolower($content));
        if ($xml !== false) {
            foreach ($xml->entry as $entry) {
                foreach ($entry->ref as $ref) {
                    if (isset($ref['href'])) {
                        array_push($urls,$ref['href']);
                    }
                }
            }
        }
        return $urls;
    }

    private function decodePlaylistUrlALL($content){
        $resultXML = $this->decodePlaylistUrlASX($content);
        if (count($resultXML) > 0){
          return $resultXML;
        }
        // replace different kinds of newline with the default
        $urls = array();
        $content = str_replace(array("\r\n","\n\r","\r"),"\n",$content);
        $lines = explode("\n",$content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (substr($line, 0, 4) == 'File') {
                $pos = strpos($line, '=');
                if ($pos !== false) {
                    $value = substr($line, $pos + 1);
                    array_push($urls,trim($value));
                }
            }
            if ($this->hasCorrectScheme($line)){
                array_push($urls,$line);
            }
        }
        return $urls;
    }

    private function getRemoteDirUrl($url)
    {
        $parsed_url = parse_url($url);
        if ($parsed_url) {
            $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
            $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
            $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
            $path = isset($parsed_url['path']) ? dirname($parsed_url['path']) : '';
            return "$scheme$host$port$path";
        }

        return null;
    }

    private function fixPlaylistItem($url, $playlistItem){
        if ($playlistItem !== false && $playlistItem !== null){
            if (!$this->hasCorrectScheme($playlistItem)){
                $remoteDir = $this->getRemoteDirUrl($url);
                if ($remoteDir !== false){
                    return $remoteDir."/".$playlistItem;
                }
                return false;
            }
        }
        return $playlistItem;
    }

    private function fixPlaylistItems($url, $playlistItems){
        $result = array();
        foreach ($playlistItems as $playlistItem)
        {
            $item = $this->fixPlaylistItem($url, $playlistItem);
            if ($item !== false){
                array_push($result, $item);
            }
        }
        return $result;
    }

    private function hasCorrectScheme($url)
    {
        if ((strtolower(substr($url, 0, 7)) === 'http://') || (strtolower(substr($url, 0, 8)) === 'https://')) {
            return true;
        }

        return false;
    }

    public function decodePlayListContent($url, $contentType, $content){
        $result = array();
        if ($content !== false){
            if ($this->isContentTypePlaylistM3U($contentType)){
                $result = $this->decodePlaylistUrlM3U($content);
            }else if ($this->isContentTypePlaylistPLS($contentType)){
                $result = $this->decodePlaylistUrlPLS($content);
            }else if ($this->isContentTypePlaylistASX($contentType)){
                $result = $this->decodePlaylistUrlASX($content);
            }
            $result = $this->fixPlaylistItems($url,$result);
            if (count($result) === 0){
                return $this->decodePlaylistUrlALL($content);
            }
        }
        return $result;
    }
}

?>

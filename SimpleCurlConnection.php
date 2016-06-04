<?php

class SimpleCurlConnection{
    private $data = "";
    private $headers = array();
    const USER_AGENT = "VLC/2.2.2 LibVLC/2.2.2";

    private function fn_CURLOPT_HEADERFUNCTION($ch, $str){
        $len = strlen($str);
        $itemArr = explode(":",$str,2);
        if (count($this->headers) == 0){
          $this->headers[0] = trim($str);
        }else if (count($itemArr) == 2){
          $this->headers[$itemArr[0]] = trim($itemArr[1]);
        }
        return $len;
    }

    private function writefn($ch, $chunk) {
        static $limit = 4096;

        $len = strlen($this->data) + strlen($chunk);
        if ($len >= $limit ) {
          $this->data .= substr($chunk, 0, $limit-strlen($this->data));
          return -1;
        }

        $this->data .= $chunk;
        return strlen($chunk);
    }

    public function get_headers_curl($url){
        // erzeuge einen neuen cURL-Handle
        $ch = curl_init();

        // setze die URL und andere Optionen
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,  array($this, "fn_CURLOPT_HEADERFUNCTION")); // handle received headers
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        // curl_setopt($ch, CURLOPT_WRITEFUNCTION, "writefn");
        // curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'fn_CURLOPT_WRITEFUNCTION'); // callad every CURLOPT_BUFFERSIZE
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        // curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // more progress info

        // führe die Aktion aus und gib die Daten an den Browser weiter
        $this->data = "";
        $this->headers = array();
        $result = curl_exec($ch);

        // schließe den cURL-Handle und gib die Systemresourcen frei
        curl_close($ch);

        return $this->headers;
    }

    public function file_get_contents_curl($url) {
        $this->data = "";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, "writefn"));
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if (!curl_exec($ch)){
          $this->data = false;
        }
        curl_close($ch);

        return $this->data;
    }
}

?>

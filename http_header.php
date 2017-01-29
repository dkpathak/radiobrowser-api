<?php

class HttpHeader{
  public function decode($str){
    $arr = explode("\r\n", $str);
    $protocol_arr = explode(' ',$arr[0]);

    if (count($protocol_arr) !== 3){
      return null;
    }

    $result = array();
    $result["protocol"] = $protocol_arr[0];
    $result["statusCode"] = intval($protocol_arr[1]);
    $result["status"] = $protocol_arr[2];
    $result["headers"] = array();
    for ($i=1;$i<count($arr);$i++){
        $index = strpos($arr[$i], ":");
        if ($index !== false){
          $key = substr($arr[$i],0,$index);
          $value = substr($arr[$i],$index+1);
          $result["headers"][$key] = trim($value);
        }
    }
    return $result;
  }
  public function getHeader($url){
      $url_parts = parse_url($url);
      if (strtolower($url_parts["scheme"]) == 'http'){
        /* Den Port für den WWW-Dienst ermitteln. */
        if (array_key_exists('port', $url_parts)){
          $service_port = $url_parts["port"];
        }else{
          $service_port = 80;
        }

        /* Die  IP-Adresse des Zielrechners ermitteln. */
        $address = gethostbyname($url_parts["host"]);

        /* Einen TCP/IP-Socket erzeugen. */
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
          echo "socket_create() fehlgeschlagen: Grund: " . socket_strerror(socket_last_error()) . "\n";
          return null;
        } else {
          echo "OK.\n";
        }

        echo "Versuche, zu '$address' auf Port '$service_port' zu verbinden ...";
        $result = socket_connect($socket, $address, $service_port);
        if ($result === false) {
          echo "socket_connect() fehlgeschlagen.\nGrund: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
          return null;
        } else {
          echo "OK.\n";
        }

        $path = $url_parts["path"];
        if (array_key_exists('query',$url_parts)){
            $path .= '?'.$url_parts["query"];
        }
        $in = "GET ".$path." HTTP/1.1\r\n";
        $in .= "Host: ".$url_parts["host"]."\r\n";
        $in .= "Connection: Close\r\n\r\n";
        $out = '';

        echo "HTTP HEAD request senden ...";
        socket_write($socket, $in, strlen($in));
        echo "OK.\n";

        echo "Serverantwort lesen:\n\n";
        $data = "";
        while ($out = socket_read($socket, 20)) {
          $data .= $out;
          $index = strpos($data, "\r\n\r\n");
          if ($index !== false){
              return $this->decode(substr($data,0,$index));
          }
        }

        echo "Socket schließen ...";
        socket_close($socket);
        echo "OK.\n\n";

        return null;
      }else{
        return null;
      }
  }
}

// $h = new HttpHeader();
// $data = $h->getHeader('httP://ice1.somafm.com/brfm-128-mp3');
// echo "data:";
// print_r($data);
?>

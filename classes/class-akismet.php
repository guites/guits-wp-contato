<?php
if (!class_exists('Guits_akismet')) {
  class Guits_akismet {

    private $api_key;
    private $option_api_key;
    private $site_url;
    private $akismet_ua;

    function __construct() {
      $this->option_api_key = 'guits_akismet_api_key';
      $this->api_key = $this->getApiKey();
      $this->site_url = 'http://wordpress.omandriao.com.br/';
      $this->akismet_ua = "WordPress/5.7.2 | Akismet/4.1.9";
    }


    /**
    * Retorna a chave da API salva no banco
    */
     public function getApiKey() {
       return get_option($this->option_api_key, false);
     }

    /**
    * Atualiza a option com a chave do akismet no banco de dados
    */
    public function setApiKey($apikey) {
      return update_option($this->option_api_key, $apikey);
    }

    /**
    * Verifica se a chave utilizada é válida
    */
    public function akismet_verify_key($apikey) {
      $blog = urlencode($this->site_url);
      $request = 'key='. $apikey .'&blog='. $blog;
      $host = $http_host = 'rest.akismet.com';
      $path = '/1.1/verify-key';
      $port = 443;
      $akismet_ua = $this->akismet_ua;
      $content_length = strlen( $request );
      $http_request  = "POST $path HTTP/1.0\r\n";
      $http_request .= "Host: $host\r\n";
      $http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
      $http_request .= "Content-Length: {$content_length}\r\n";
      $http_request .= "User-Agent: {$akismet_ua}\r\n";
      $http_request .= "\r\n";
      $http_request .= $request;
      $response = '';
      if( false != ( $fs = @fsockopen( 'ssl://' . $http_host, $port, $errno, $errstr, 10 ) ) ) {
        fwrite( $fs, $http_request );
        while ( !feof( $fs ) )
          $response .= fgets( $fs, 1160 ); // One TCP-IP packet
        fclose( $fs );
        $response = explode( "\r\n\r\n", $response, 2 );
      }
      if ( 'valid' == $response[1] ) {
        return true;
      } else {
        return false;
      }
    }


    /**
    * Envia o comentário para avaliação,
    * retorna true [spam]
    * retorna false [ham]
    */
    public function akismet_comment_check( $data ) {
      $request = 'blog='. urlencode($this->site_url) .
      '&user_ip='. urlencode($data['user_ip']) .
      '&user_agent='. urlencode($data['user_agent']) .
      '&referrer='. urlencode($data['referrer']) .
      '&comment_type='. urlencode($data['comment_type']) .
      '&comment_author='. urlencode($data['comment_author']) .
      '&comment_author_email='. urlencode($data['comment_author_email']) .
      '&comment_content='. urlencode($data['comment_content']);

      $host = $http_host = $this->api_key.'.rest.akismet.com';
      $path = '/1.1/comment-check';
      $port = 443;
      $akismet_ua = $this->akismet_ua;
      $content_length = strlen( $request );
      $http_request  = "POST $path HTTP/1.0\r\n";
      $http_request .= "Host: $host\r\n";
      $http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
      $http_request .= "Content-Length: {$content_length}\r\n";
      $http_request .= "User-Agent: {$akismet_ua}\r\n";
      $http_request .= "\r\n";
      $http_request .= $request;
      $response = '';
      if( false != ( $fs = @fsockopen( 'ssl://' . $http_host, $port, $errno, $errstr, 10 ) ) ) {

        fwrite( $fs, $http_request );

        while ( !feof( $fs ) )
          $response .= fgets( $fs, 1160 ); // One TCP-IP packet
        fclose( $fs );
        $response = explode( "\r\n\r\n", $response, 2 );
      }
      if ( 'true' == $response[1] )
        return true;
      else
        return false;
    }
  }
}

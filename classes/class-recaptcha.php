<?php
/**
* Wrapper para uso do recaptcha do google
* https://developers.google.com/recaptcha/docs/verify
*/

if (!class_exists('Guits_recaptcha')) {
  class Guits_recaptcha {

    private $secret_key;
    private $site_key;
    private $secret_key_option;
    private $site_key_option;
    private $status_option;

    function __construct() {
      $this->status_option = 'guits_contato_recaptcha_status';
      $this->secret_key_option = 'guits_secret_key_recaptcha';
      $this->site_key_option = 'guits_site_key_recaptcha';
    }

    /**
    * método para adicionar a site key via painel adm
    */

    public function setSiteKey($site_key) {
      return update_option($this->site_key_option, $secret_key);
    }

    /**
    * método para acessar valor da secret key, utilizado no __construct
    */

    public function getSiteKey() {
      return get_option($this->site_key_option, false);
    }

    /**
    * método para adicionar a secret key via painel adm
    */

    public function setSecretKey($secret_key) {
      return update_option($this->secret_key_option, $secret_key);
    }

    /**
    * método para acessar valor da secret key, utilizado no __construct
    */

    public function getSecretKey() {
      return get_option($this->secret_key_option, false);
    }

    /**
    * método para verificar se o recaptcha deve ser utilizado
    */

    public function checkStatus() {
      return get_option($this->status_option, false);
    }

    /**
    * método para definir se o recaptcha deve ser utilizado
    */

    public function setStatus($value) {
      return update_option($this->status_option, $value);
    }

    public function verify($submiter_response_token, $submiter_IP) {
      $postdata = array(
        "secret" => $this->getSecretKey(),
        "response" => $submiter_response_token,
        "remoteip" => $submiter_IP
      );
      $crl = curl_init('https://www.google.com/recaptcha/api/siteverify');
      curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($crl, CURLINFO_HEADER_OUT, true);
      curl_setopt($crl, CURLOPT_POST, true);
      curl_setopt($crl, CURLOPT_POSTFIELDS, $postdata);

      $result = json_decode(curl_exec($crl));

      if($result->success === true) {
        return true;
      } else {
        switch ($result->{"error-codes"}[0]) {
          case "missing-input-secret":
            return array("reCAPTCHA configurado incorretamente [chave secreta faltando]. Tente novamente mais tarde.", 503);
            break;
          case "invalid-input-secret":
            return array("reCAPTCHA configurado incorretamente [chave secreta inválida ou incorreta]. Tente novamente mais tarde.", 503);
            break;
          case "missing-input-response":
            return array("Você precisa preencher o desafio reCAPTCHA!", 400);
            break;
          case "invalid-input-response":
            return array("Preencha o desafio reCAPTCHA corretamente!", 400);
            break;
          case "bad-request":
            return array("reCAPTCHA configurado incorretamente [requisição incorreta]. Tente novamente mais tarde.", 503);
            break;
          default:
            return array("Esta chave já foi utilizada ou teve seu tempo de validade expirado. Preencha o desafio recaptcha novamente!", 400);
        }
      }
    }
  }

}

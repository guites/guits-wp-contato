<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) die("No cheating!");

if(!class_exists('Guits_contato_form'))
{

  class Guits_contato_form extends Guits_contato {

    function __construct() {
      parent::__construct();
      add_action('wp', array($this, 'guits_adc_form'));
    }

    public function guits_adc_form() {
      if (is_page($this->guits_get_formulario_page())) {
        add_filter( 'the_content', function( $content ) {
          return $this->guits_frontend_notice() . $content . $this->guits_adc_form_handler_function();
        }, 0);
      }
    }

    public function guits_frontend_notice() {
      $html = "";
      if(isset($_REQUEST['guits_add_notice'])) {
        if ($_REQUEST['guits_add_notice'] === 'success') {
          $html = "<div style='border:2px groove black; padding:15px; background-color:white; border-left:5px solid green;'>";
          $html .= "<p>Sua mensagem foi enviada com <strong>sucesso</strong>!</p>";
          $html .= "<p>Muito obrigado! Entrarei em contato em breve.</p>";
          $html .= "</div>";
        }
      }
      return $html;
    }

    public function guits_adc_form_handler_function(){
      $guits_frontend_form_nonce = wp_create_nonce('guits_frontend_message_nonce');
      $insert = "
        <form id='c-form' action='". esc_url ( admin_url ( 'admin-post.php' ) ) ."' method='post'>
          <fieldset>
            <legend>Envie uma mensagem</legend>
            <ul>
              <li><input type='hidden' name='action' value='guits_form_contato_frontend'></li>
              <li><input type='hidden' name='guits_frontend_message_nonce' value='".$guits_frontend_form_nonce."'></li>
              <li>
                <label for='c-name'>nome/apelido<small><em>(opcional)</em></small></label>
                <input name='c-name' type='text' maxlength='20' placeholder='the fat josé' />
              <li>
                <label for='c-email'>e-mail para contato</label>
                <input name='c-email' required='required' type='email' placeholder='jose@internet.com' />
              </li>
              <li>
                <label for='c-select'>Assunto</label>
                <select id='c-select' name='c-select'>
                  <option value='Error'>problema com o site</option>
                  <option selected='selected' value='Question'>dúvida ou comentário</option>
                  <option value='Other'>Outro</option>
                </select>
              </li>
              <li>
                <label>mensagem</label>
                <textarea name='c-message' cols='30' maxlength='".$this->message_max_length."' required='required' rows='4' placeholder='queria te avisar que o site tá uma merda'></textarea>
              </li>
            </ul>
            <input type='submit' value='Enviar' />
          </fieldset>
        </form>
      ";
      return $insert;
    }

  }

  // inicializa a classe no front end.
  if( !is_admin() ) {
    $guits_contato_form = new Guits_contato_form;
  }

}

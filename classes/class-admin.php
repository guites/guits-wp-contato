<?php
  // If this file is called directly, abort.
  if (!defined('ABSPATH')) die("GTFO");

  if (!class_exists('Guits_contato_admin')) {

    class Guits_contato_admin extends Guits_contato {

      function __construct() {

        parent::__construct();
        // add admin menu
        add_action( 'admin_menu', array( $this, 'mensagens_menu' ), 99 );

        // add scripts and style on the backend
        add_action( 'admin_enqueue_scripts', array( $this,'guits_admin_scripts') );
        add_action( 'wp_ajax_get_job_applications', array( $this, 'get_form_messages') );

        //add post handling on admin-posts.php
          // método para receber a requisição com as configurações do plugin
        add_action('admin_post_guits_form_response', array($this,'the_form_response'));
          // handling do envio de contato
        add_action('admin_post_nopriv_guits_form_contato_frontend', array($this,'the_form_frontend_response'));
        add_action('admin_post_guits_form_contato_frontend', array($this,'the_form_frontend_response'));
          // handling para quando for necessário recaptcha
        add_action('admin_post_nopriv_guits_form_contato_recaptcha', array($this,'the_form_frontend_recaptcha'));
        add_action('admin_post_guits_form_contato_recaptcha', array($this,'the_form_frontend_recaptcha'));
      }

      /**
      * add menu
      */
      public function mensagens_menu() {
        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        add_menu_page('Guits Contato','Guits Mensagens', 'edit_others_posts', $this->plugin_name, array( $this, 'contato_section'), 'dashicons-email-alt' );
      }

      /**
      * carrega javascript e css próprios
      */
      public function guits_admin_scripts() {
        if (isset($_GET['page']) && $_GET['page'] === $this->plugin_name) {
          wp_enqueue_style($this->plugin_name . '-form-admin-style', GUITS_CONTATO_PLUGIN_URL . '/assets/css/admin.css' );
          #wp_enqueue_script( 'mct-reports-datepicker' , MCT_REPORTS_PLUGIN_URL . 'js/jquery-ui.min.js', array('jquery') ,true);
        }
      }

      /**
      * callback para página no painel admin
      */
      public function contato_section() {
        $guits_recaptcha = new Guits_recaptcha();
        $guits_akismet = new Guits_akismet();
        $this->print_plugin_admin_notices();
        include_once(GUITS_CONTATO_PLUGIN_PATH . '/views/admin-form.php');
      }

      /**
      * callback para o post no admin-posts.php - configurações do plugin
      */

      public function the_form_response() {
        if (isset($_POST['guits_select_page_nonce']) && wp_verify_nonce( $_POST['guits_select_page_nonce'], 'guits_select_page_form_nonce')) {

          $guits_recaptcha = new Guits_recaptcha();
          $guits_akismet = new Guits_akismet();

          # validação dos dados recebidos

          ## validação referente à escolha de página

          $guits_page_id = sanitize_key($_POST['guits']['page_select']);
          if($guits_page_id !== 'nenhuma'){
            $wp_post_type = get_post_type($guits_page_id);
            if ($wp_post_type !== 'page') $this->guits_die("Você só pode adicionar o formulário de contato em páginas.", 400);
          }

          ### validação referente ao uso ou não do reCAPTCHA

          $guits_recaptcha_option = sanitize_key($_POST['guits']['recaptcha_option']);

          ### se o checkbox do recaptcha não estiver marcado, desabilitar uso no formulário
          if (empty($guits_recaptcha_option)) {
            ### verifica se já não está desabilitado
            if ($guits_recaptcha->checkStatus()) {
              $guits_recaptcha->setStatus(false);
            }
          } else {
            if ( $guits_recaptcha_option != 'on' ) {
              $this->guits_die("A escolha do captcha é uma opção de sim ou não.", 400);
            }
            ### verifica se já não está habilitado
            if ( !$guits_recaptcha->checkStatus ) {
              $guits_recaptcha->setStatus(true);
            }
          }
          $guits_recaptcha_secret_key = sanitize_text_field($_POST['guits']['recaptcha_secret_key']);
          $guits_recaptcha_site_key = sanitize_text_field($_POST['guits']['recaptcha_site_key']);

          ### caso optar por utilizar o recaptcha, colocar a chave é obrigatório
          if ( $guits_recaptcha_option ) {
            if (empty($guits_recaptcha_secret_key)) $this->guits_die("A chave secreta do reCAPTCHA é obrigatória para utilizar a ferramenta!", 400);
            if (empty($guits_recaptcha_site_key)) $this->guits_die("A chave pública do reCAPTCHA é obrigatória para utilizar a ferramenta!", 400);
          }

          ##### validação da chave do akismet
          $akismet_key = sanitize_text_field($_POST['guits_akismet_api_key']);
          if (empty($akismet_key)) $this->guits_die("É obrigatório uma chave da API do Akismet.", 400);

          # atualização dos dados
          $notice_type = "error";
          $notice_page = $notice_captcha_secret = $notice_captcha_site = $notice_akismet = false;


          ## verifica se a pessoa optou pela página já definida
          if($this->guits_get_formulario_page() != $guits_page_id) {

            ## verifica se salvou a página que vai ter o form de contato
            if($this->guits_set_formulario_page($guits_page_id)) $notice_page = true;

          } else {
            ## se a página continua a mesma, tudo ok
            $notice_page = true;
          }

          ### verifica se a pessoa alterou a chave secreta do recaptcha
          if ($guits_recaptcha->getSecretKey() != $guits_recaptcha_secret_key) {

            ### verifica se atualizou a opção do uso do recaptcha
            if ($guits_recaptcha->setSecretKey($guits_recaptcha_secret_key)) $notice_captcha_secret = true;

          } else {
            ### se a chave continua a mesma, tudo ok
            $notice_captcha_secret = true;
          }

          ### verifica se a pessoa alterou a chave pública do recaptcha
          if ($guits_recaptcha->getSiteKey() != $guits_recaptcha_site_key) {

            ### verifica se atualizou a opção do uso do recaptcha
            if ($guits_recaptcha->setSiteKey($guits_recaptcha_site_key)) $notice_captcha_site = true;

          } else {
            ### se a chave continua a mesma, tudo ok
            $notice_captcha_site = true;
          }

          #### verifica se a chave do akismet utilizada é válida
          if (!$guits_akismet->akismet_verify_key($akismet_key)) {
            $this->guits_die("Chave Akismet inválida ou domínio não habilitado para uso.", 400);
          }
          #### chave akismet válida, verificar se foi alterada ou se trata da mesma
          if ($guits_akismet->getApiKey() != $akismet_key) {
            if ($guits_akismet->setApiKey($akismet_key)) {
              $notice_akismet = true;
            } else {
              $this->guits_die("Erro ao atualizar chave do akismet. Verifique sua instalação do plugin.", 500);
            }
          } else {
            $notice_akismet = true;
          }

          if ( $notice_captcha_secret && $notice_captcha_site && $notice_page && $notice_akismet ) $notice_type = "success";

          # avisa o usuário
          $this->custom_redirect($notice_type, $_POST, true);
        } else {
          wp_die(
            'Erro de confirmação. Você deve acessar este formulário pelo painel administrativo.',
            'Error',
            array(
              'response'  => 403,
              'back_link' => 'admin.php?page=' . $this->plugin_name,
            )
          );
        }
      }

      /**
      * Wrapper para o wp_die
      */

      public function guits_die($message, $http_code) {
        wp_die(
          $message,
          "Erro - " . get_bloginfo( "name" ),
          array(
            "response" => $http_code,
            "back_link" => true
          )
        );
      }

      /**
      * Validação de email
      */

      public function validateEmail($input_email) {
        $mail_rgx = '/[\w\.-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-]+[\.[a-zA-Z0-9-]*/u';
        preg_match($mail_rgx, $input_email, $output);
        if(count($output) <= 0) {
          $email_err = 'E-mail inválido! Verifique o e-mail digitado e tente novamente.';
        } else if ($output[0] !== $input_email) {
          $email_err = 'E-mail contém caracteres inválidos! Verifique o e-mail digitado e tente novamente.';
        } else {
          $email_err = true;
        }
        return $email_err;
      }

      /**
      * callback para validação do recaptcha - usuários pegos pelo antispam do akismet
      */

      public function the_form_frontend_recaptcha() {
        if (isset($_POST['guits_frontend_captcha_nonce']) && wp_verify_nonce($_POST['guits_frontend_captcha_nonce'], 'guits_frontend_captcha_nonce')) {
          if (isset($_POST['g-recaptcha-response'])) {
            $submiter_IP = $_SERVER['REMOTE_ADDR'];
            $submiter_response_token = $_POST['g-recaptcha-response'];
            $guits_recaptcha = new Guits_recaptcha();
            $recaptcha_result = $guits_recaptcha->verify($submiter_response_token, $submiter_IP);
            if ($recaptcha_result === true) {

              # captcha ok, registrar mensagem

              # antes, revalidar os valores
              ## evita que editem o post ao chegar na tela do captcha

              list(
                  $guits_post_id,
                  $guits_frontend_contact_name,
                  $guits_frontend_contact_email,
                  $guits_frontend_contact_assunto,
                  $guits_frontend_contact_message,
                  $guits_frontend_contact_referrer,
                  $guits_frontend_contact_remote_addr,
                  $guits_frontend_contact_user_agent
                  ) = $this->guits_validate_frontend_form();

              $commentdata = array(
                  "comment_author" => $guits_frontend_contact_name,
                  "comment_approved" => 0,
                  "comment_author_email" => $guits_frontend_contact_email,
                  "comment_content" => $guits_frontend_contact_assunto . ": " . $guits_frontend_contact_message,
                  "comment_post_ID" => $guits_post_id,
                  "comment_author_IP" => $guits_frontend_contact_remote_addr,
                  "comment_agent" => $guits_frontend_contact_user_agent,
                  "comment-type" => "contact-form"
                  );

              if($new_comment_id = wp_insert_comment($commentdata)) {

                #mensagem foi salva corretamente. Redirecionar usuário.

                $this->custom_redirect("success","success", false);

              } else {
                $commendata['error'] = " captcha ok wp_insert_comment";
                error_log(print_r($commentdata, true));
                $this->guits_die("Ocorreu um erro ao enviar sua mensagem! Tente novamente mais tarde.", 500);

              }


            } else {

              $this->guits_die($recaptcha_result[0], $recaptcha_result[1]);

            }

          } else {
            $this->guits_die("Você precisa resolver o desafio captcha!", 400);
          }
        } else {
          $this->guits_die("O envio deve ser realizado pelo formulário no site!", 400);
        }
      }

      /**
      * Validação para o $_POST do envio do formulário no front end
      */

      public function guits_validate_frontend_form() {
        # não preciso validar nonce, pois já utilizo um no envio pela pg do formulario
        # e outro pela página do captcha

        $guits_frontend_contact_email = $_POST['c-email'];
        $validate_email = $this->validateEmail($guits_frontend_contact_email);

        # verificar se, editando o $_POST na hora do recaptcha, não caio num loop

        if ($validate_email !== true) {
          $this->guits_die($validate_email, 400);
        }

        $guits_frontend_contact_assunto = sanitize_key($_POST['c-select']);
        $guits_frontend_contact_name = $_POST['c-name'];
        $guits_frontend_contact_message = $_POST['c-message'];
        $guits_frontend_contact_referrer = $_SERVER['HTTP_REFERER'];
        $guits_frontend_contact_remote_addr = $_SERVER['REMOTE_ADDR'];
        $guits_frontend_contact_user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strlen($guits_frontend_contact_user_agent) > 498) {
          $guits_frontend_contact_user_agent = substr($guits_frontend_contact_user_agent, 0, 496) . " ..";
        }

        if(strlen($guits_frontend_contact_name) > 20) {
          $this->guits_die("O campo NOME/APELIDO só pode ter até 20 caracteres.", 400);
        }

        $allowed_subjects = array("error", "question", "other");

        if (empty($guits_frontend_contact_email)
            || empty($guits_frontend_contact_assunto)
            || empty($guits_frontend_contact_message)
            || empty($guits_frontend_contact_assunto)) {
          $this->guits_die("Preencha todos os campos.", 400);
        }

        if ( !in_array($guits_frontend_contact_assunto,$allowed_subjects) ) {
          $this->guits_die("Escolha um assunto da lista!", 400);
        }

        $args = array(
          'numberposts' => 1,
          'post_type' => $this->post_type
        );

        $guits_contact_form_post = get_posts($args);

        #verifica se a instalação do plugin ocorreu corretamente

        if (count($guits_contact_form_post) <= 0) {
          $this->guits_die("O envio de mensagens está temporariamente suspenso.",503);
        }

        if(strlen($guits_frontend_contact_message) > $this->message_max_length) {
          $this->guits_die("O comentário deve ter no máximo 1000 caracteres!", 400);
        }

        $guits_post_id = $guits_contact_form_post[0]->ID;
        return array(
          $guits_post_id,
          $guits_frontend_contact_name,
          $guits_frontend_contact_email,
          $guits_frontend_contact_assunto,
          $guits_frontend_contact_message,
          $guits_frontend_contact_referrer,
          $guits_frontend_contact_remote_addr,
          $guits_frontend_contact_user_agent
        );
      }

      /**
      * callback pra envio de formulário no wp-admin - formulário de contato
      */

      public function the_form_frontend_response() {
        if (isset($_POST['guits_frontend_message_nonce'])
          && wp_verify_nonce($_POST['guits_frontend_message_nonce'], 'guits_frontend_message_nonce')){

          list(
            $guits_post_id,
            $guits_frontend_contact_name,
            $guits_frontend_contact_email,
            $guits_frontend_contact_assunto,
            $guits_frontend_contact_message,
            $guits_frontend_contact_referrer,
            $guits_frontend_contact_remote_addr,
            $guits_frontend_contact_user_agent
           ) = $this->guits_validate_frontend_form();

          # array para verificação AKISMET

          $akismet_data = array(
            'user_ip' => $guits_frontend_contact_remote_addr,
            'user_agent' => $guits_frontend_contact_user_agent,
            'referrer' => $guits_frontend_contact_referrer,
            'comment_type' => 'contact-form',
            'comment_author' => $guits_frontend_contact_name,
            'comment_author_email' => $guits_frontend_contact_email,
            'comment_content' => $guits_frontend_contact_message
          );

          $guits_akismet = new Guits_akismet();
          $guits_akismet_spam_check = $guits_akismet->akismet_comment_check($akismet_data);

          # array pra criação do comentário

          $commentdata = array(
            "comment_author" => $guits_frontend_contact_name,
            "comment_approved" => 0,
            "comment_author_email" => $guits_frontend_contact_email,
            "comment_content" => $guits_frontend_contact_assunto . ": " . $guits_frontend_contact_message,
            "comment_post_ID" => $guits_post_id,
            "comment_author_IP" => $guits_frontend_contact_remote_addr,
            "comment_agent" => $guits_frontend_contact_user_agent,
            "comment_type" => "contact-form"
          );

          if($guits_akismet_spam_check) {

            # caso o akismet considerar como spam, carregar tela com desafio captcha

            ## aqui devo instanciar a classe do captcha e verificar se a pessoa habilitou o captcha pelo painel

            $guits_recaptcha = new Guits_recaptcha();

            if ($guits_recaptcha->checkStatus()) {

              $guits_frontend_captcha_nonce = wp_create_nonce('guits_frontend_captcha_nonce');
              $this->guits_die(
                  "
                  <p>Por favor, resolva o desafio <strong>CAPTCHA</strong> abaixo!</p>
                  <noscript><p style='color:darkblue';>Você precisa habilitar o javascript para resolver o captcha.</p></noscript>
                  <form action='". esc_url ( admin_url ( 'admin-post.php' ) ) ."' method='post'>
                  <input type='hidden' name='guits_frontend_captcha_nonce' value='".$guits_frontend_captcha_nonce."'>
                  <div class='g-recaptcha' data-sitekey='".$guits_recaptcha->getSiteKey()."'></div>
                  <input type='hidden' name='c-name' value='" .$guits_frontend_contact_name. "' />
                  <input type='hidden' name='c-email' value='" .$guits_frontend_contact_email. "' />
                  <input type='hidden' name='c-select' value='" .$guits_frontend_contact_assunto. "' />
                  <input type='hidden' name='c-message' value='" .$guits_frontend_contact_message. "' />
                  <br/>
                  <input type='hidden' name='action' value='guits_form_contato_recaptcha'>
                  <input type='submit' value='Confirmar'>
                  </form>
                  <script src='https://www.google.com/recaptcha/api.js' async defer></script>",
                  "200"
                  );

            } else {

              # optado por não utilizar reCAPTCHA
              ## insere o comentário e marca como spam

              if($new_comment_id = wp_insert_comment($commentdata)) {
                wp_spam_comment( $new_comment_id );
                $this->custom_redirect("success","success", false);
              } else {
                $commendata['error'] = "optou por nao usar o recaptcha wp_insert_comment";
                error_log(print_r($commentdata, true));
                $this->guits_die("Ocorreu um erro ao enviar sua mensagem! Tente novamente mais tarde.", 500);
              }
            }

            # lógica adicional pode ir aqui, como adicionar o IP ou e-mail em uma lista para bloqueio posterior.
          } else {

            # passou pelo akismet, considerar ham

            if($new_comment_id = wp_insert_comment($commentdata)) {

              #mensagem foi salva corretamente. Redirecionar usuário.

              $this->custom_redirect("success","success", false);

            } else {
              $commentdata['error'] = "passou pelo akismet considerar ham wp_insert_comment";
              error_log(print_r($commentdata, true));
              $this->guits_die("Ocorreu um erro ao enviar sua mensagem! Tente novamente mais tarde.", 500);
            }

          }
        } else {
          $this->guits_die("O envio deve ser realizado pelo formulário no site!", 400);
        }
      }

      public function custom_redirect( $admin_notice, $response, $admin ) {
        if ($admin) {
          $url = admin_url("admin.php?page=" . $this->plugin_name);
          $query_args = array(
            'guits_admin_add_notice' => $admin_notice,
            'guits_response' => $response,
          );
        } else {
          $url = get_permalink( $this->guits_get_formulario_page() );
          $query_args = array(
            "guits_add_notice" => $admin_notice,
            "guits_response" => $response
          );
        }
        wp_redirect(
          esc_url_raw(
            add_query_arg(
              $query_args,
              $url
            )
          )
        );
        exit();
      }

  public function print_plugin_admin_notices() {
    if(isset($_REQUEST['guits_admin_add_notice'])) {
      if ($_REQUEST['guits_admin_add_notice'] === 'success') {
        $html = "<div class='notice notice-success is-dismissible'>";
        $html .= "<p>A requisição ocorreu com <strong>sucesso</strong>.</p><br/>";
        $html .= "<pre>" . htmlspecialchars ( print_r($_REQUEST['guits_response'], true ) ) . "</pre></div>";
      } else if ($_REQUEST['guits_admin_add_notice']) {
        $html = "<div class='notice notice-error is-dismissible'>";
        $html .= "<p>Ocorreu um <strong>erro</strong>.</p><br/>";
        $html .= "<pre>" . htmlspecialchars ( print_r($_REQUEST['guits_response'], true ) ) . "</pre></div>";
      }
      echo $html;
    } else {
      return;
    }
  }

    public function guits_set_formulario_page($page_id) {
      return update_option($this->page_option_name, $page_id);
    }

  }// fim da classe

  # inicia a classe no lado admin
  if (is_admin()) {
    $guits_contato = new Guits_contato_admin;
  }

} // fim do class_exists

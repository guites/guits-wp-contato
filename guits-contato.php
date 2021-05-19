<?php

/*
  Plugin Name: Guits Contato
  Description: Adição de formulário de contato com escolha de página, gerenciamente de mensagens como se fossem comentários em um post.
  Version: 1.0
  Author: <a href="http://wordpress.omandriao.com.br">Guits</a>
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

//  declare the constant
define('GUITS_CONTATO_PLUGIN_PATH', dirname(__FILE__));  // this constant uses in the class files
define('GUITS_CONTATO_PLUGIN_URL' , WP_PLUGIN_URL . '/guits-contato/' ); // this constant uses in enqueue file and style

// our custom classes
require_once( dirname(__FILE__) . '/classes/class-recaptcha.php');
require_once( dirname(__FILE__) . '/classes/class-akismet.php');
require_once( dirname(__FILE__) . '/classes/class-parent.php');
require_once( dirname(__FILE__) . '/classes/class-form.php' );
require_once( dirname(__FILE__) . '/classes/class-admin.php' );
require_once( dirname(__FILE__) . '/classes/class-mensagens.php' );

/**
* Register Activation and Deactivation Hooks
* This action is documented in inc/core/class-activator.php
*/

register_activation_hook( __FILE__, 'guits_activation_handler');

function guits_activation_handler() {
  $guits_contato = new Guits_contato();
  $guits_setup_post_id = $guits_contato->createTheSinglePost();

} // end guits_activation_handler;


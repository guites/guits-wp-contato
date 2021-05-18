<?php

if (!defined('ABSPATH')) die("GTFO");

if (!class_exists('Guits_contato')) {
  class Guits_contato {

     protected $plugin_name;
     protected $page_option_name;
     protected $post_type;
     protected $message_max_length;

    function __construct() {
      $this->plugin_name = 'guits-contato';
      $this->page_option_name = 'guits_formulario_page';
      $this->post_type = 'guits_contact_msgs';
      $this->message_max_length = 1000;

       //add messages post type
       add_action('init',array($this,'registerMessagesPostType'));
    }

    public function guits_get_formulario_page() {
      return get_option($this->page_option_name);
    }

    public function registerMessagesPostType() {

      $args = array(
        'labels' => array(
          'name' => 'Mensagens de contato',
          'singular_name' => 'Mensagem de contato'
        ),
        'public' => false,
        'has_archive' => false
      );
      register_post_type($this->post_type,$args);

    }

    public function createTheSinglePost() {
      // Initialize the page ID to -1. This indicates no action has been taken.
      $post_id = -1;

      // Setup the author, slug, and title for the post
      $slug = 'guits-contato-post';
      $title = 'Guits Contato Post';

      // If the page doesn't already exist, then create it
      if( null == get_page_by_title( $title, OBJECT, $this->post_type ) ) {

        // Set the post ID so that we know the post was created successfully
        $post_id = wp_insert_post(
          array(
            'comment_status'  =>  'open',
            'post_name'   =>  $slug,
            'post_title'    =>  $title,
            'post_status'   =>  'private',
            'post_type'   =>  $this->post_type
          )
        );

      } else {

        // Arbitrarily use -2 to indicate that the page with the title already exists
        $post_id = -2;

      } // end if

      return $post_id;
    }


  }
}

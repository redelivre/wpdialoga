<?php
/*
Plugin Name: Import Dialoga Proposals
Depends: Delibera
Description: Plugin for import dialoga proposal's 
Author: Maurilio Atila
Version: 0.0
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import Dialoga Proposals is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version. If not see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html.
 
Import Dialoga Proposals is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Import Dialoga Proposals.
*/

// ao instalar um plugin com dependencia avisar que é necessário o plugin de dependencia, e dar um die no plugin.
// vammos esperar a refatoração do delibera para usar ele aqui...


//TODO: ver o contexto onde ela será usada


if ( ! defined( 'WPINC' ) ) {
    die;
}

if( !function_exists( 'is_pauta' ) ) {
	die;
}


class WPDialoga
{
  protected $pluginPath;
  protected $pluginUrl;
  protected $page;

  public function __construct()
  {
      // Set Plugin Path
      $this->pluginPath = dirname(__FILE__);
     
      // Set Plugin URL
      $this->pluginUrl = WP_PLUGIN_URL . '/WPDialoga';
      
      add_action('init', array($this, 'Init'));
      $page = 1;
  }

  public function Init()
  {
      add_action( 'delibera_menu_itens', array( $this, 'wpdialoga_custom_admin_menu') );
      register_activation_hook( __FILE__, array( $this, 'wpdialoga_activate' ) );
      add_action( 'wp_ajax_add_transfer', array( $this, 'wpdialoga_form_handler' ) );
      add_action( 'wp_ajax_change_page', array( $this, 'wpdialoga_page_handler' ) );
  }

  static function wpdialoga_activate() 
  {
    if (!get_cat_ID( 'dialoga' ))
      wp_create_category('dialoga');
  }
  
  //http://wordpress.stackexchange.com/questions/60758/how-to-handle-form-submission
  public function wpdialoga_page_handler()
  {
    $page = $_POST["page"];
    wp_redirect( admin_url()."admin.php?page=wpdialoga-plugin&api_page=".$page ); exit;
  }

  public function wpdialoga_form_handler()
  {
    $user_ID = get_current_user_id();

    if ( empty($_POST) || !wp_verify_nonce( $_POST['security_code'] , 'add_transfer' ) )
    {
        echo 'A função esta correta mas seu nonce esta errado.';
        die();
    }
    else
    {
      wp_redirect( $redirect_url_for_non_ajax_request );
    }

    foreach($_POST as $key => $value) {
        if (strpos($key, 'propose_') === 0) {
            $propose = $_POST[$key];
            $propose = explode("###", $propose);
            // title
            // echo $propose[0]."<br>";
            // subcatecory
            // echo $propose[1]."<br>";
            // author - insert with title or create a meta for this on pauta?
            // echo $propose[2]."<br>";
            // Initialize the post ID to -1. This indicates no action has been taken.
            $post_id = -1;
            
            // Setup the author, slug, and title for the post
            $author_id = $user_ID;
            $slug = $key;
            $title = $propose[0];
            if(!term_exists($propose[1])) {
              wp_insert_term(
                $propose[1],
                'dialoga'
              );
            } 
            // If the page doesn't already exist, then create it
            if( null == get_page_by_title( $title , 'OBJECT' , 'pauta'  ) ) {
              // Set the page ID so that we know the page was created successfully
              $post_id = wp_insert_post(
              	array(
              		'ping_status'		=>	'closed',
              		'post_author'		=>	$author_id,
              		'post_name'		=>	$slug,
              		'post_title'		=>	$title,
              		'post_status'		=>	'publish',
              		'post_type'		=>	'pauta',
                        'post_category' => get_cat_ID( 'dialoga' ) 
              	     )
              );
            // Otherwise, we'll stop and set a flag
            } 
            else
            {
              // Arbitrarily use -2 to indicate that the page with the title already exists
              $post_id = -2;
            } // end if
        }
    }

    wp_redirect( admin_url()."edit.php?post_type=pauta" ); exit;

  }

  public function wpdialoga_custom_admin_menu()
  {
    //TODO: use delibera menu
       add_submenu_page(
          'delibera-config',
          'Importar Propostas de Pauta do Dialoga',
          'Importar do DialogaGOV',
          'manage_options',
          'wpdialoga-plugin', 
          array($this, 'wpdialoga_options_page') 
       );
  }

  public function wpdialoga_options_page()
  {
    // XXX This need test
    $page = intval($_GET['api_page']);
  
    include 'DialogaAPI.php';
    $dialogaAPI = new DialogaAPI();
    $proposals = $dialogaAPI->getAllProposals($page);
    ?>
    <div class="wrap">
        <h2>Importar Propostas de Pauta do Dialoga</h2>
        <br>
        <label>Selecione quais das propostas de Pauta você deseja importar para o delibera. Clique no botão no final da página e nós vamos inserir a Proposta de Pauta para você, note que se algum usuário já inseriu esta proposta de pauta o link da Pauta no delibera, bem como seu status aparece ao lado da do Texto da Proposta de Pauta. 
        </label>
        <br>
        <!-- show proposals -->
        <form id="" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" class="form" >
        <?php 
              foreach($proposals as $propose){ ?>
                 <h4>
                  <input type="checkbox" 
                  <?php echo get_page_by_title( $propose->abstract , 'OBJECT' , 'pauta'  )?'disabled':""; ?>
                  name="propose_<?php echo $propose->id; ?>" 
                  value="<?php echo $propose->abstract . "###" . $propose->categories[0]->name . "###" . $propose->parent->setting->author_name; ?>" >
                    <?php echo $propose->abstract; ?>
                    <br>
                  </input>
                 </h4>
                 <!-- TODO: what is the best choise. Remove all html tags from text before show? or show with images and organization? -->
                 <label>Categoria: </label>
                     <?php echo $propose->categories[0]->name; ?>
                 <br>
                 <!-- TODO: what, where is the original author name? On articles exist other author_name -->
                 <label>Author: </label>
                     <?php echo $propose->parent->setting->author_name; ?>
                 </input>
                 <br>
                 <br>
                  <?php echo get_page_by_title( $propose->abstract , 'OBJECT' , 'pauta'  )?'<label style="color:red">Conteudo já esta online</label>':""; ?>
                 <br>
                 <a href="<?php echo get_page_by_title( $propose->abstract , 'OBJECT' , 'pauta'  )? post_permalink( get_page_by_title( $propose->abstract , 'OBJECT' , 'pauta'  )->ID):""; ?>">Clique aqui para contribur para contribuir para essa pauta</a>
                 <br>
                 <br>
                 <?php
              }//end foreach
        ?>
              <!-- init pager -->
              <?php wp_nonce_field('add_transfer', 'security_code')?>
              <input name="action" value="add_transfer" type="hidden" >
              <!-- end pager -->
              <?php submit_button("Inserir") ?>
       </form>
       <form id="" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" class="form" >
             <center>
              <!--<a> << </a> -->
             <?php 
                  for($i = 1; $i<10; $i++)
                  { 
                      //TODO: link reaload the function with new parameter for page. see wp callback. See search json or content on wp page;
                     ?>
                     <input name="page" style="background:none!important;
                                   border:none; 
                                   padding:0!important;
                                   font: inherit;
                                   /*border is optional*/
                                   border-bottom:1px solid #444; 
                                   cursor: pointer;"
                                   type="submit" value="<?php echo $i; ?>">
                     </input>
                     <?php wp_nonce_field('change_page', 'security_code')?>
                     <input name="action" value="change_page" type="hidden" >
                     <?php
                  }
             ?>
             <!-- <a> >> </a> -->
             <br>
             <br>
             </center>
      </form>
    </div>
    <?php
  }

}

global $WPDialoga;
$WPDialoga = new WPDialoga();

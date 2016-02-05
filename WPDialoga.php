<?php
/*
Plugin Name: Import Dialoga Proposals
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

class WPDialoga
{
  protected $pluginPath;
  protected $pluginUrl;

  public function __construct()
  {
      // Set Plugin Path
      $this->pluginPath = dirname(__FILE__);
     
      // Set Plugin URL
      $this->pluginUrl = WP_PLUGIN_URL . '/WPDialoga';
      
      add_action('init', array($this, 'Init'));
  }

  public function Init()
  {
      add_action( 'delibera_menu_itens', array( $this, 'wpdialoga_custom_admin_menu') );
      register_activation_hook( __FILE__, array( $this, 'wpdialoga_activate' ) );
      add_action( 'wp_ajax_add_transfer', array( $this, 'wpdialoga_form_handler' ) );
      //add_action( 'wp_ajax_nopriv_add_tranfer', array( $this, 'wpdialoga_form_handler' ) );
      //add_action( 'admin_menu', array($this, 'wpdialoga_custom_admin_menu') );
  }

  // ideia create dialoga category on enable plugin - https://codex.wordpress.org/Function_Reference/register_activation_hook
  static function wpdialoga_activate() 
  {
    if (!get_cat_ID( 'dialoga' ))
      wp_create_category('dialoga');
  }
  
  public function wpdialoga_form_handler()
  {
    $user_ID = get_current_user_id();
    echo "<pre>";
    var_dump($_POST);
    echo "</pre>";

    if ( empty($_POST) || !wp_verify_nonce( $_POST['security_code'] , 'add_transfer' ) )
    {
        echo 'A função esta correta mas seu nonce esta errado.';
        die();
    }
    else
    {
      wp_redirect( $redirect_url_for_non_ajax_request );
    }

 
    // Create post object
    // $my_post = array(
    //'post_title'    => wp_strip_all_tags( $_POST['post_title'] ),
    //'post_content'  => $_POST['pauta'],
    //'post_status'   => 'publish',
    //'post_author'   => $user_ID,
    //'post_category' => get_cat_ID( 'dialoga' ) 
    //);
 
    //// Insert the post into the database
    //wp_insert_post( $my_post );

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
    //$page = $_POST['page'];
    //echo $page;
  
    include 'DialogaAPI.php';
    $dialogaAPI = new DialogaAPI();
    $proposals = $dialogaAPI->getAllProposals();
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
                  <input type="checkbox" name="propose_<?php echo $propose->id; ?>" value="<?php echo $propose->abstract . "###" . $propose->categories[0]->name . "###" . $propose->parent->setting->author_name; ?>" >
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
                 <?php
              }//end foreach
        ?>
              <!-- init pager -->
              <center>
               <!--<a> << </a> -->
        <?php 
             for($i = 1; $i<10; $i++)
             { 
                 //TODO: link reaload the function with new parameter for page. see wp callback. See search json or content on wp page;
                ?>
                <a href="admin.php?page=wpdialoga-plugin&page_number=<?php echo $i ?>"><?php echo $i; ?></a>
                <?php
             }
        ?>
               <!-- <a> >> </a> -->
               <br>
               <br>
              </center>
              <?php wp_nonce_field('add_transfer', 'security_code')?>
              <input name="action" value="add_transfer" type="hidden" >
              <!-- end pager -->
              <!-- TODO: find function for create button on wp -->
              <?php submit_button("Inserir") ?>
       </form>
    </div>
    <?php
  }

}

global $WPDialoga;
$WPDialoga = new WPDialoga();

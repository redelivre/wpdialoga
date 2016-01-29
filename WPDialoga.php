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
      add_action( 'admin_menu', array($this, 'wpdialoga_custom_admin_menu') );
  }

  public function wpdialoga_custom_admin_menu()
  {
    //TODO: use delibera menu
      add_options_page(
          'WP Dialoga Title',
          'WP Dialoga Menu Item',
          'manage_options',
          'wpdialoga-plugin',
          array($this, 'wpdialoga_options_page')
      );
  }

  public function wpdialoga_options_page()
  {
    include 'DialogaAPI.php';
    $dialogaAPI = new DialogaAPI();
    $proposals = $dialogaAPI->getAllProposals();
    ?>
    <div class="wrap">
        <h2>Import Dialoga Proposal's Options</h2>
        <br>
        <br>
        <form >
        <?php 
              foreach($proposals as $propose){ ?>
                 <input type="checkbox" value="<?php echo $propose->id; ?>" >
                     <?php echo $propose->abstract; ?>
                     <br>
                     <label>Categoria: </label><?php echo $propose->categories[0]->name; ?>
                     <br>
                     <!-- TODO: what, where is the original author name? On articles exist other author_name -->
                     <label>Author: </label> <?php echo $propose->parent->setting->author_name; ?>
                 </input>
                 <br>
                 <br>
                 <?php
              }
        ?>
              <input type="button" id="submit" class="button button-primary" name="submit" value="Inserir Nova Proposta de Pauta">
       </form>
    </div>
    <?php
  }

}

global $WPDialoga;
$WPDialoga = new WPDialoga();

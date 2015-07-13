<?php
/*
 Plugin Name: Exporter customer by product purchased
 Plugin URI: judicia.com.br
 Description: Exports to cvs email of customer os specifics products
 Version: 1.0
 Author: Jadson Ribeiro 
*/

//If this files is called directly, abort
if(!defined('ABSPATH'))
	exit;

if(!class_exists('Exporter_emails')) :

define('EXP_PLUGIN_PATH', plugin_dir_path( __FILE__ )); 
define('EXP_PLUGIN_URL', plugins_url('', __FILE__ ));
define('EXP_JS_URL', EXP_PLUGIN_URL.'/js');
define('EXP_INCLUDES_PATH', EXP_PLUGIN_PATH.'includes/');
define('EXP_EXPORTS_PATH', EXP_PLUGIN_PATH.'exports/');
define('EXP_INCLUDES_URL', EXP_PLUGIN_URL.'/exports');

class Exporter_emails {

	/*
	 * Initialize the plugin
	 */
	function __construct() {

		//include required files
		$this->includes();

		add_action('admin_menu', array($this, 'plugin_main_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		
		// Use javascript global 'ajaxurl'
		// On ajax, when a request where data['action'] = 'exp_callback' is called (see js/export_email.js line 12)
		// the function 'export_callback' will be called 
		add_action('wp_ajax_exp_callback', 'export_callback');
	}

	/**
	 * Include required files
	 *
	 * @return void
	 */
	function includes(){
		

		try {
			// include(PATH) CORRECT / include(URL) WRONG
			include(EXP_INCLUDES_PATH.'export_callback.inc.php');
			include(EXP_INCLUDES_PATH.'class.search_email.inc.php');
			include(EXP_INCLUDES_PATH.'class.generator_csv.inc.php');
		} catch (Exception $e) {
			echo $e->message();
		}
	}

	/**
	 * Enqueue Script
	 *
	 * @return void
	 */
	function enqueue_scripts() {
		wp_enqueue_script('export_email_by_product_handle', EXP_JS_URL.'/export_email.js', array('jquery'), '1.0', true);

		// Data to pass to js when loaded
		$data = array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			// 'another_value' => 1345,
		);

		//Pass a set of values to script when it's loaded
		wp_localize_script('export_email_by_product_handle', 'exp_object', $data);
	}

	/**
	 * Add Tab to Admin menu
	 *
	 * @return void
	 */
	function plugin_main_menu(){
		add_options_page('Export Emails', 'Export Emails', 'manage_options', 'export-emails-specific-product', array($this, 'menu_display_form'));
	}

	/**
	 * Show form to select product
	 *
	 * @return void
	 */
	function menu_display_form(){
		?>
		<h1> Formulario </h1>
		<div id='wrap'>
			<form method="POST" action='options.php' class="form_exporter">
				<?php 
					// Display necessary hidden fields
					settings_fields('email_exporter_option_group');

					// Print all sections added to particular page
					do_settings_sections('export-emails-specific-product');
					submit_button();
				?>
				     <input type="button" id="export_button" name="export" class="button button-primary" value="Exportar">
			</form>
			<div id="button_download" style="margin: 10px;"> </div>
		</div>
		<?php
	}

	/**
	 * Register all settings to Setting Page
	 *
	 * @return void
	 */
	function register_settings() {
		// Tells Wordprpess plugin will use Setting API
		register_setting(
			'email_exporter_option_group', //Option Group
			'email_exporter_option_name'   //Option Name (database)
		);

		// Define a section to show fields
		add_settings_section(
			'email_exporter_section',        // Section ID
			'Configurações',				 // Title
			'callback_section', 			 // callback function
			'export-emails-specific-product' // page
		);

		// Define fields will be shown on section
		add_settings_field(
			'email_exporter_field_product',          // Field ID
			'Produto', 							     // Title
			array($this, 'callback_field_product'),  // callback Function 
			'export-emails-specific-product',        // page 
			'email_exporter_section'			     // Section 
		);

		add_settings_field(
			'email_exporter_field_status',
			'Status dos Pedidos',
			array($this, 'callback_field_status'),
			'export-emails-specific-product',
			'email_exporter_section'
		);
	}

	/**
	 * Callback to render Product Field
	 *
	 * @return void
	 */
	function callback_field_product() {
		//Key of $options is defined by "name=option_name[ -> KEY <- ]"
		$options = get_option('email_exporter_option_name');
		echo "<input type='text' class='field_product' name='email_exporter_option_name[id_product]' value=".$options['id_product']."  >";
	}

	/**
	 * Callback to render Status Field
	 *
	 * @return void
	 */
	function callback_field_status() {
		$options = get_option('email_exporter_option_name');
		// echo "<input type='text' class='field_status' name='email_exporter_option_name[id_status]' value=".$options['id_status']." >";
		echo "<select class='field_status' name='email_exporter_option_name[id_status]'>
					<option value='completed' selected> completed </option>
					<option value='on-hold'> on-hold </option>
					<option value='cancelled'> cancelled </option>
					<option value='refunded'> refunded </option>
			</select>";
	}

}


endif;
new Exporter_emails;

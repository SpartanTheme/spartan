<?php

class underConstruction
{
	var $installedFolder = "";
	var $mainOptionsPage = "underConstructionMainOptions";

	function __construct()
	{
		$this->installedFolder = basename(dirname(__FILE__));
	}

	function underConstruction()
	{
		$this->__construct();
	}

	function getMainOptionsPage()
	{
		return $this->mainOptionsPage;
	}

	function underConstructionAdminInit()
	{
		/* Register our script. */
		wp_register_script('underConstructionJS', SPARTAN_PLUGIN_URL . '/assets/js/underconstruction.min.js');
	}

	function uc_changeMessage()
	{
		require_once SPARTAN_PLUGIN_DIR . '/includes/admin-options.php';
	}

	function uc_adminMenu()
	{
		/* Register our plugin page */
		$page = add_options_page('Under Construction Settings', 'Under Construction', 'activate_plugins', $this->mainOptionsPage, array($this, 'uc_changeMessage'));

		/* Using registered $page handle to hook script load */
		add_action('admin_print_scripts-'.$page, array($this, 'underConstructionEnqueueScripts'));

	}

	function underConstructionEnqueueScripts()
	{
		/*
		 * It will be called only on your plugin admin page, enqueue our script here
		 */
		wp_enqueue_script('scriptaculous');
		wp_enqueue_script('underConstructionJS');
	}

	function uc_overrideWP()
	{
		if ($this->pluginIsActive())
		{
			if (!is_user_logged_in())
			{
				$array = get_option('underConstructionIPWhitelist');

				if(!is_array($array)){
					$array = array();
				}

				if(!in_array($_SERVER['REMOTE_ADDR'], $array)){

					//send a 503 if the setting requires it
					if (get_option('underConstructionHTTPStatus') == 503)
					{
						header('HTTP/1.1 503 Service Unavailable');
					}

					//send a 503 if the setting requires it
					if (get_option('underConstructionHTTPStatus') == 301)
					{
						header( "HTTP/1.1 301 Moved Permanently" );
						header( "Location: " . get_option('underConstructionRedirectURL') );
					}

					if ($this->displayStatusCodeIs(0)) //they want the default!
					{
						require_once SPARTAN_PLUGIN_DIR . '/includes/defaultMessage.php';
						displayDefaultComingSoonPage();
						die();
					}

					if ($this->displayStatusCodeIs(1)) //they want the default with custom text!
					{
						require_once SPARTAN_PLUGIN_DIR . '/includes/defaultMessage.php';
						displayComingSoonPage($this->getCustomPageTitle(), $this->getCustomHeaderText(), $this->getCustomBodyText());
						die();
					}

					if ($this->displayStatusCodeIs(2)) //they want custom HTML!
					{
						echo html_entity_decode($this->getCustomHTML(), ENT_QUOTES);
						die();
					}

					if($this->displayStatusCodeIs(3)){
						require_once(get_template_directory() . '/under-construction.php');
						die();
					}
				}
			}
		}
	}

	function uc_admin_override_WP(){

		if(!$this->pluginIsActive()){
			return;
		}

		if(get_option('underConstructionRequiredRole') && is_user_logged_in()){

			global $wp_roles;
			$all_roles = $wp_roles->roles;

			$editable_roles = apply_filters('editable_roles', $all_roles);

			$required_role = $editable_roles[get_option('underConstructionRequiredRole')];

			$new_privs = array();


			foreach($required_role['capabilities'] as $key => $value){
				if($value == true){
					$new_privs[] = $key;
				}
			}

			if(!current_user_can($new_privs[0])){
				wp_logout();
				wp_redirect(get_bloginfo('url'));
			}
		}
	}

	function getCustomHTML()
	{
		return stripslashes(get_option('underConstructionHTML'));
	}


	function uc_activate()
	{
		if (get_option('underConstructionArchive'))
		{
			//get all the options back from the archive
			$options = get_option('underConstructionArchive');

			//put them back where they belong
			update_option('underConstructionHTML', $options['underConstructionHTML']);
			update_option('underConstructionActivationStatus', $options['underConstructionActivationStatus']);
			update_option('underConstructionCustomText', $options['underConstructionCustomText']);
			update_option('underConstructionDisplayOption', $options['underConstructionDisplayOption']);
			update_option('underConstructionHTTPStatus', $options['underConstructionHTTPStatus']);

			delete_option('underConstructionArchive');
		}
	}

	function uc_deactivate()
	{
		//get all the options. store them in an array
		$options = array();
		$options['underConstructionHTML'] = get_option('underConstructionHTML');
		$options['underConstructionActivationStatus'] = get_option('underConstructionActivationStatus');
		$options['underConstructionCustomText'] = get_option('underConstructionCustomText');
		$options['underConstructionDisplayOption'] = get_option('underConstructionDisplayOption');
		$options['underConstructionHTTPStatus'] = get_option('underConstructionHTTPStatus');

		//store the options all in one record, in case we ever reactivate the plugin
		update_option('underConstructionArchive', $options);

		//delete the separate ones
		delete_option('underConstructionHTML');
		delete_option('underConstructionActivationStatus');
		delete_option('underConstructionCustomText');
		delete_option('underConstructionDisplayOption');
		delete_option('underConstructionHTTPStatus');
	}

	function pluginIsActive()
	{

		if (!get_option('underConstructionActivationStatus')) //if it's not set yet
		{
			return false;
		}

		if (get_option('underConstructionActivationStatus') == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function httpStatusCodeIs($status)
	{
		if (!get_option('underConstructionHTTPStatus')) //if it's not set yet
		{
			update_option('underConstructionHTTPStatus', 200); //set it
		}

		if (get_option('underConstructionHTTPStatus') == $status)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	function displayStatusCodeIs($status)
	{
		if (!get_option('underConstructionDisplayOption')) //if it's not set yet
		{
			update_option('underConstructionDisplayOption', 0); //set it
		}

		if (get_option('underConstructionDisplayOption') == $status)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function getCustomPageTitle()
	{
		if (get_option('underConstructionCustomText') != false)
		{
			$fields = get_option('underConstructionCustomText');
			return stripslashes($fields['pageTitle']);
		}
		else
		{
			return '';
		}
	}

	function getCustomHeaderText()
	{
		if (get_option('underConstructionCustomText') != false)
		{
			$fields = get_option('underConstructionCustomText');
			return stripslashes($fields['headerText']);
		}
		else
		{
			return '';
		}
	}

	function getCustomBodyText()
	{
		if (get_option('underConstructionCustomText') != false)
		{
			$fields = get_option('underConstructionCustomText');
			return stripslashes($fields['bodyText']);
		}
		else
		{
			return '';
		}
	}


}

$underConstructionPlugin = new underConstruction();

add_action('template_redirect', array($underConstructionPlugin, 'uc_overrideWP'));
add_action('admin_init', array($underConstructionPlugin, 'uc_admin_override_WP'));
add_action('wp_login', array($underConstructionPlugin, 'uc_admin_override_WP'));


add_action('plugins_loaded', 'underConstructionInitTranslation');

add_action('admin_init', array($underConstructionPlugin, 'underConstructionAdminInit'));
add_action('admin_menu', array($underConstructionPlugin, 'uc_adminMenu'));

register_activation_hook(__FILE__, array($underConstructionPlugin, 'uc_activate'));
register_deactivation_hook(__FILE__, array($underConstructionPlugin, 'uc_deactivate'));
register_uninstall_hook(__FILE__, 'underConstructionPlugin_delete');



function underConstructionPlugin_delete()
{
	delete_option('underConstructionArchive');
}

function uc_get_ip_address(){
	echo $_SERVER['REMOTE_ADDR'];
	die();
}


function underConstructionPluginLinks($links, $file)
{
	global $underConstructionPlugin;
	if ($file == basename(dirname(__FILE__)).'/'.basename(__FILE__) && function_exists("admin_url"))
	{
		//add settings page
		$manage_link = '<a href="'.admin_url('options-general.php?page='.$underConstructionPlugin->getMainOptionsPage()).'">'.__('Settings').'</a>';
		array_unshift($links, $manage_link);


	}
	return $links;
}

function underConstructionInitTranslation() {
  load_plugin_textdomain( 'underconstruction', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


add_filter('plugin_action_links', 'underConstructionPluginLinks', 10, 2);


//ajax

add_action('wp_ajax_uc_get_ip_address', 'uc_get_ip_address');

?>

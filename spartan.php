<?php
/*
Plugin Name: spartan
Plugin URI: https://github.com/SpartanTheme/spartan
Description: Wordpress Starter framework
Version: 1.62
Author: Renzo Johnson
Author URI: http://renzojohnson.com/
License: GPLv2
*/

define( 'SPARTAN_VERSION', '1.62' );

define( 'SPARTAN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( 'SPARTAN_PLUGIN_NAME', trim( dirname( SPARTAN_PLUGIN_BASENAME ), '/' ) );

define( 'SPARTAN_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );

define( 'SPARTAN_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );

define( 'SPARTAN_PLUGIN_MODULES_DIR', SPARTAN_PLUGIN_DIR . '/modules' );

if ( ! defined( 'SPARTAN_LOAD_JS' ) )
	define( 'SPARTAN_LOAD_JS', true );

if ( ! defined( 'SPARTAN_LOAD_CSS' ) )
	define( 'SPARTAN_LOAD_CSS', true );

if ( ! defined( 'SPARTAN_AUTOP' ) )
	define( 'SPARTAN_AUTOP', true );

if ( ! defined( 'SPARTAN_USE_PIPE' ) )
	define( 'SPARTAN_USE_PIPE', true );

if ( ! defined( 'SPARTAN_ADMIN_READ_CAPABILITY' ) )
	define( 'SPARTAN_ADMIN_READ_CAPABILITY', 'edit_posts' );

if ( ! defined( 'SPARTAN_ADMIN_READ_WRITE_CAPABILITY' ) )
	define( 'SPARTAN_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );

if ( ! defined( 'SPARTAN_VERIFY_NONCE' ) )
	define( 'SPARTAN_VERIFY_NONCE', true );

require_once SPARTAN_PLUGIN_DIR . '/includes/admin.php';




if (!defined("SPARTAN_AUTHOR_URI")) define("SPARTAN_AUTHOR_URI", "http://spartantheme.com");
if (!defined("SPARTAN_DOCS_URI")) define("SPARTAN_DOCS_URI", "http://docs.spartantheme.com");

add_action( 'init', 'github_plugin_updater_test_init' );
function github_plugin_updater_test_init() {


	include_once SPARTAN_PLUGIN_DIR . '/updater.php';

	define( 'WP_GITHUB_FORCE_UPDATE', true );

	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin

		$config = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => 'spartan-master',
			'api_url' => 'https://api.github.com/repos/SpartanTheme/spartan',
			'raw_url' => 'https://raw.github.com/SpartanTheme/spartan/master',
			'github_url' => 'https://github.com/SpartanTheme/spartan',
			'zip_url' => 'https://github.com/SpartanTheme/spartan/archive/master.zip',
			'sslverify' => true,
			'requires' => '3.0',
			'tested' => '4.2',
			'readme' => 'README.md',
			'access_token' => '',
		);

		new WP_GitHub_Updater( $config );

	}

}




/**
 * Configuration assistant for updating from private repositories.
 * Do not include this in your plugin once you get your access token.
 *
 * @see /wp-admin/plugins.php?page=github-updater
 */
class WPGitHubUpdaterSetup {

	/**
	 * Full file system path to the main plugin file
	 *
	 * @var string
	 */
	var $plugin_file;

	/**
	 * Path to the main plugin file relative to WP_CONTENT_DIR/plugins
	 *
	 * @var string
	 */
	var $plugin_basename;

	/**
	 * Name of options page hook
	 *
	 * @var string
	 */
	var $options_page_hookname;

	function __construct() {

		// Full path and plugin basename of the main plugin file
		$this->plugin_file = __FILE__;
		$this->plugin_basename = plugin_basename( $this->plugin_file );

		add_action( 'admin_init', array( $this, 'settings_fields' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'network_admin_menu', array( $this, 'add_page' ) );

		add_action( 'wp_ajax_set_github_oauth_key', array( $this, 'ajax_set_github_oauth_key' ) );
	}

	/**
	 * Add the options page
	 *
	 * @return none
	 */
	function add_page() {
		if ( current_user_can ( 'manage_options' ) ) {
			$this->options_page_hookname = add_plugins_page ( __( 'GitHub Updates', 'github_plugin_updater' ), __( 'GitHub Updates', 'github_plugin_updater' ), 'manage_options', 'github-updater', array( $this, 'admin_page' ) );
			add_filter( "network_admin_plugin_action_links_{$this->plugin_basename}", array( $this, 'filter_plugin_actions' ) );
			add_filter( "plugin_action_links_{$this->plugin_basename}", array( $this, 'filter_plugin_actions' ) );
		}
	}

	/**
	 * Add fields and groups to the settings page
	 *
	 * @return none
	 */
	public function settings_fields() {
		register_setting( 'ghupdate', 'ghupdate', array( $this, 'settings_validate' ) );

		// Sections: ID, Label, Description callback, Page ID
		add_settings_section( 'ghupdate_private', 'Private Repositories', array( $this, 'private_description' ), 'github-updater' );

		// Private Repo Fields: ID, Label, Display callback, Menu page slug, Form section, callback arguements
		add_settings_field(
			'client_id', 'Client ID', array( $this, 'input_field' ), 'github-updater', 'ghupdate_private',
			array(
				'id' => 'client_id',
				'type' => 'text',
				'description' => '',
			)
		);
		add_settings_field(
			'client_secret', 'Client Secret', array( $this, 'input_field' ), 'github-updater', 'ghupdate_private',
			array(
				'id' => 'client_secret',
				'type' => 'text',
				'description' => '',
			)
		);
		add_settings_field(
			'access_token', 'Access Token', array( $this, 'token_field' ), 'github-updater', 'ghupdate_private',
			array(
				'id' => 'access_token',
			)
		);
		add_settings_field(
			'gh_authorize', '<p class="submit"><input class="button-primary" type="submit" value="'.__( 'Authorize with GitHub', 'github_plugin_updater' ).'" /></p>', null, 'github-updater', 'ghupdate_private', null
		);

	}

	public function private_description() {
?>
		<p>Updating from private repositories requires a one-time application setup and authorization. These steps will not need to be repeated for other sites once you receive your access token.</p>
		<p>Follow these steps:</p>
		<ol>
			<li><a href="https://github.com/settings/applications/new" target="_blank">Create an application</a> with the <strong>Main URL</strong> and <strong>Callback URL</strong> both set to <code><?php echo bloginfo( 'url' ) ?></code></li>
			<li>Copy the <strong>Client ID</strong> and <strong>Client Secret</strong> from your <a href="https://github.com/settings/applications" target="_blank">application details</a> into the fields below.</li>
			<li><a href="javascript:document.forms['ghupdate'].submit();">Authorize with GitHub</a>.</li>
		</ol>
		<?php
	}

	public function input_field( $args ) {
		extract( $args );
		$gh = get_option( 'ghupdate' );
		$value = $gh[$id];
?>
		<input value="<?php esc_attr_e( $value )?>" name="<?php esc_attr_e( $id ) ?>" id="<?php esc_attr_e( $id ) ?>" type="text" class="regular-text" />
		<?php echo $description ?>
		<?php
	}

	public function token_field( $args ) {
		extract( $args );
		$gh = get_option( 'ghupdate' );
		$value = $gh[$id];

		if ( empty( $value ) ) {
?>
			<p>Input Client ID and Client Secret, then <a href="javascript:document.forms['ghupdate'].submit();">Authorize with GitHub</a>.</p>
			<input value="<?php esc_attr_e( $value )?>" name="<?php esc_attr_e( $id ) ?>" id="<?php esc_attr_e( $id ) ?>" type="hidden" />
			<?php
		}else {
?>
			<input value="<?php esc_attr_e( $value )?>" name="<?php esc_attr_e( $id ) ?>" id="<?php esc_attr_e( $id ) ?>" type="text" class="regular-text" />
			<p>Add to the <strong>$config</strong> array: <code>'access_token' => '<?php echo $value ?>',</code>
			<?php
		}
?>
		<?php
	}

	public function settings_validate( $input ) {
		if ( empty( $input ) ) {
			$input = $_POST;
		}
		if ( !is_array( $input ) ) {
			return false;
		}
		$gh = get_option( 'ghupdate' );
		$valid = array();

		$valid['client_id']     = strip_tags( $input['client_id'] );
		$valid['client_secret'] = strip_tags( $input['client_secret'] );
		$valid['access_token']  = strip_tags( $input['access_token'] );

		if ( empty( $valid['client_id'] ) ) {
			add_settings_error( 'client_id', 'no-client-id', __( 'Please input a Client ID before authorizing.', 'github_plugin_updater' ), 'error' );
		}
		if ( empty( $valid['client_secret'] ) ) {
			add_settings_error( 'client_secret', 'no-client-secret', __( 'Please input a Client Secret before authorizing.', 'github_plugin_updater' ), 'error' );
		}

		return $valid;
	}

	/**
	 * Add a settings link to the plugin actions
	 *
	 * @param array   $links Array of the plugin action links
	 * @return array
	 */
	function filter_plugin_actions( $links ) {
		$settings_link = '<a href="plugins.php?page=github-updater">' . __( 'Setup', 'github_plugin_updater' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Output the setup page
	 *
	 * @return none
	 */
	function admin_page() {
		$this->maybe_authorize();
?>
		<div class="wrap ghupdate-admin">

			<div class="head-wrap">
				<?php screen_icon( 'plugins' ); ?>
				<h2><?php _e( 'Setup GitHub Updates' , 'github_plugin_updater' ); ?></h2>
			</div>

			<div class="postbox-container primary">
				<form method="post" id="ghupdate" action="options.php">
					<?php
		settings_errors();
		settings_fields( 'ghupdate' ); // includes nonce
		do_settings_sections( 'github-updater' );
?>
				</form>
			</div>

		</div>
		<?php
	}

	public function maybe_authorize() {
		$gh = get_option( 'ghupdate' );
		if ( 'false' == $_GET['authorize'] || 'true' != $_GET['settings-updated'] || empty( $gh['client_id'] ) || empty( $gh['client_secret'] ) ) {
			return;
		}

		$redirect_uri = urlencode( admin_url( 'admin-ajax.php?action=set_github_oauth_key' ) );

		// Send user to GitHub for account authorization

		$query = 'https://github.com/login/oauth/authorize';
		$query_args = array(
			'scope' => 'repo',
			'client_id' => $gh['client_id'],
			'redirect_uri' => $redirect_uri,
		);
		$query = add_query_arg( $query_args, $query );
		wp_redirect( $query );

		exit;

	}

	public function ajax_set_github_oauth_key() {
		$gh = get_option( 'ghupdate' );

		$query = admin_url( 'plugins.php' );
		$query = add_query_arg( array( 'page' => 'github-updater' ), $query );

		if ( isset( $_GET['code'] ) ) {
			// Receive authorized token
			$query = 'https://github.com/login/oauth/access_token';
			$query_args = array(
				'client_id' => $gh['client_id'],
				'client_secret' => $gh['client_secret'],
				'code' => $_GET['code'],
			);
			$query = add_query_arg( $query_args, $query );
			$response = wp_remote_get( $query, array( 'sslverify' => false ) );
			parse_str( $response['body'] ); // populates $access_token, $token_type

			if ( !empty( $access_token ) ) {
				$gh['access_token'] = $access_token;
				update_option( 'ghupdate', $gh );
			}

			wp_redirect( admin_url( 'plugins.php?page=github-updater' ) );
			exit;

		}else {
			$query = add_query_arg( array( 'authorize'=>'false' ), $query );
			wp_redirect( $query );
			exit;
		}
	}
}
add_action( 'init', create_function( '', 'global $WPGitHubUpdaterSetup; $WPGitHubUpdaterSetup = new WPGitHubUpdaterSetup();' ) );


/* Custom items to the footer right before </body>
================================================== */
add_filter( 'wp_footer' , 'spartan_footer' , 100 );
function spartan_footer() {

   $spartan_footer_output .= '<h1 style="background-color:blue;color:white;position:absolute; width:100%;">'. "\n";
   $spartan_footer_output .= do_shortcode( '[sf_modal header="Get Service Now!" btn_text="Find Out More"][contact-form-7 id="3" title="contact"][/sf_modal]' );
   $spartan_footer_output .= '</h1>'. "\n";

   echo $spartan_footer_output;
}

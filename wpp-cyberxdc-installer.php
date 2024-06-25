<?php
/*
Plugin Name: CyberXDC Installer
Description: This is an installer plugin for CyberXDC.
Version: 1.0
Author: CyberXDC installer
*/

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}
define('CYBERXDC_INSTALLER_VERSION', '1.0');
define('CYBERXDC_INSTALLER_PATH', plugin_dir_path(__FILE__));
define('CYBERXDC_INSTALLER_URL', plugin_dir_url(__FILE__));
define('CYBERXDC_INSTALLER_FILE', __FILE__);
define('CYBERXDC_INSTALLER_BASENAME', plugin_basename(__FILE__));
define('CYBERXDC_INSTALLER_NAME', 'CyberXDC Installer');
define('CYBERXDC_INSTALLER_PLUGIN_SLUG', 'wpp-cyberxdc');
define('CYBERXDC_INSTALLER_TEXT_DOMAIN', 'wpp-cyberxdc');
define('CYBERXDC_INSTALLER_MAIN_PLUGIN_DOMAIN', 'https://cyberxdc.online');
define('CYBERXDC_INSTALLER_SLUG', 'cyberxdc_installer');

// Activation hook
register_activation_hook(__FILE__, 'cyberxdc_installer_activate');

function cyberxdc_installer_activate()
{
    // Redirect to the CyberXDC description and installation page after activation
    add_option('cyberxdc_installer_activation_redirect', true);
}

// Redirect after activation
add_action('admin_init', 'cyberxdc_installer_redirect');

function cyberxdc_installer_redirect()
{
    if (get_option('cyberxdc_installer_activation_redirect', false)) {
        delete_option('cyberxdc_installer_activation_redirect');
        wp_redirect(admin_url('admin.php?page=cyberxdc_install_page'));
        exit;
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'cyberxdc_installer_deactivate');

function cyberxdc_installer_deactivate()
{
    // Deactivation tasks, if any
}

// Hook to add menu page for CyberXDC description and installation
add_action('admin_menu', 'cyberxdc_add_install_page');

function cyberxdc_add_install_page()
{
    add_menu_page(
        'CyberXDC Installation', // Page title
        'CyberXDC', // Menu title
        'activate_plugins', // Minimum capability required to access the page
        'cyberxdc_install_page', // Menu slug (URL part)
        'cyberxdc_install_page_content', // Callback function to render the page content
        'dashicons-admin-plugins', // Icon URL or dashicon name
        30 // Position in the menu
    );
}

// Callback function to render CyberXDC installation page content
function cyberxdc_install_page_content()
{

?>
    <div class="wrap">
        <div style="display: flex; align-items: center; justify-content: center" class="container">
            <div style="text-align: center;" class="card">
                <div class="cyberxdc-logo">
                    <img class="wpp-cyberxdc-logo" style="max-width: 120px; text-align: center" src="https://placeholder.com/300" alt="CyberXDC Logo">
                </div>
                <h1>CyberXDC Plugin Installation</h1>
                <div class="cyberxdc-description">
                    <p>This is the description of CyberXDC plugin. You can write here about its features, benefits, etc.</p>
                </div>
                <div class="cyberxdc-install-button">
                    <form method="post">
                        <input type="submit" name="install_cyberxdc_plugin" value="Install_CyberXDC_Plugin" class="button button-primary button-large">
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
}

function get_plugin_repository_data()
{

    $plugin_download_url = CYBERXDC_INSTALLER_MAIN_PLUGIN_DOMAIN . '/plugin-download-url';
    $plugin_download_url_response = wp_remote_get($plugin_download_url);

    if (is_wp_error($plugin_download_url_response)) {
        $error_message = $plugin_download_url_response->get_error_message();
        echo "Error: $error_message";
        return;
    }

    $plugin_download_url_data = json_decode($plugin_download_url_response['body'], true);

    if (empty($plugin_download_url_data)) {
        echo "Error: Invalid plugin download URL data";
        return;
    }

    $plugin_repo_name = $plugin_download_url_data['repo_name'];
    $plugin_repo_owner = $plugin_download_url_data['repo_owner'];
    $plugin_repo_tagname = $plugin_download_url_data['tag'];

    if (empty($plugin_repo_name) || empty($plugin_repo_owner) || empty($plugin_repo_tagname)) {
        echo "Error: Invalid plugin repository data";
        return;
    }
    if (get_option('cyberxdc_plugin_repo_name')) {
        update_option('cyberxdc_plugin_repo_name', $plugin_repo_name);
    } else {
        add_option('cyberxdc_plugin_repo_name', $plugin_repo_name);
    }
    if (get_option('cyberxdc_plugin_repo_owner')) {
        update_option('cyberxdc_plugin_repo_owner', $plugin_repo_owner);
    } else {
        add_option('cyberxdc_plugin_repo_owner', $plugin_repo_owner);
    }
    if (get_option('cyberxdc_plugin_repo_tagname')) {
        update_option('cyberxdc_plugin_repo_tagname', $plugin_repo_tagname);
    } else {
        add_option('cyberxdc_plugin_repo_tagname', $plugin_repo_tagname);
    }
}

// Check if update button is clicked
add_action('admin_init', 'cyberxdc_handle_install_request');

function cyberxdc_handle_install_request()
{
    if (isset($_POST['install_cyberxdc_plugin'])) {
        get_plugin_repository_data();
        $update_result = cyberxdc_custom_install_functionality();

        if ($update_result === true) {
            $plugin_dir = plugin_dir_path(__FILE__);
            add_action('admin_notices', 'cyberxdc_install_success_notice');
            error_log('Plugin installed successfully.');
            deactivate_plugins(plugin_basename(__FILE__));
            $plugin_path = plugin_dir_path(__FILE__);
            if (file_exists($plugin_path)) {
                $deleted = cyberxdc_recursive_remove_directory_installer($plugin_path);
                if ($deleted) {
                    error_log('Plugin directory deleted successfully.');
                    wp_redirect(admin_url('plugins.php?cyberxdc_install_status=success'));
                    exit;
                }
            }
        } else {
            // Add admin notice for failure
            add_action('admin_notices', 'cyberxdc_install_failed_notice');
            error_log('Plugin installation failed.');
        }
    }
}

function cyberxdc_recursive_remove_directory_installer($dir)
{
    if (!is_dir($dir)) {
        return false;
    }
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? cyberxdc_recursive_remove_directory("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

// Custom function to perform update functionality
function cyberxdc_custom_install_functionality()
{
    $repo_name = get_option('cyberxdc_plugin_repo_name');
    $repo_owner = get_option('cyberxdc_plugin_repo_owner');
    $tag = get_option('cyberxdc_plugin_repo_tagname');
    $download_url = "https://github.com/{$repo_owner}/{$repo_name}/archive/refs/heads/{$tag}.zip";
    $plugin_temp_zip = WP_PLUGIN_DIR . '/cyberxdc-temp.zip';
    if (!is_writable(WP_PLUGIN_DIR)) {
        error_log('The plugin directory is not writable.');
        return false;
    }
    $response = wp_remote_get($download_url, array('timeout' => 30));
    if (is_wp_error($response)) {
        error_log('Failed to download the plugin ZIP file from GitHub. Error: ' . $response->get_error_message());
        return false;
    }
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        error_log("Failed to download the plugin ZIP file from GitHub. HTTP Response Code: {$response_code}");
        return false;
    }
    $file_saved = file_put_contents($plugin_temp_zip, wp_remote_retrieve_body($response));
    if ($file_saved === false) {
        error_log('Failed to save the plugin ZIP file to the plugin directory.');
        return false;
    }
    if (!function_exists('WP_Filesystem')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    WP_Filesystem();
    global $wp_filesystem;
    if (!$wp_filesystem->exists($plugin_temp_zip)) {
        error_log('The downloaded ZIP file does not exist.');
        return false;
    }
    $unzip_result = unzip_file($plugin_temp_zip, WP_PLUGIN_DIR);
    if (is_wp_error($unzip_result)) {
        error_log('Failed to extract the plugin ZIP file. Error: ' . $unzip_result->get_error_message());
        unlink($plugin_temp_zip);
        return false;
    }
    if (!$wp_filesystem->delete($plugin_temp_zip)) {
        error_log('Failed to delete the temporary plugin ZIP file.');
    }
    if (!$wp_filesystem->exists(WP_PLUGIN_DIR . '/wpp-cyberxdc-main')) {
        return false;
    }
    return true;
}



function cyberxdc_install_success_notice()
{
?>
    <div class="notice notice-success is-dismissible">
        <p>Plugin updated successfully!</p>
    </div>
<?php
}

function cyberxdc_install_failed_notice()
{
?>
    <div class="notice notice-error is-dismissible">
        <p>Failed to update plugin. Please try again later.</p>
    </div>
<?php
}

// Function to add the success notice after redirection
function cyberxdc_display_success_notice() {
    if (isset($_GET['cyberxdc_install_status']) && $_GET['cyberxdc_install_status'] == 'success') {
        echo '<div class="notice notice-success is-dismissible">
                 <p>CyberXDC plugin installed successfully. Activate the plugin to start using it.</p>
              </div>';
    }
}
add_action('admin_notices', 'cyberxdc_display_success_notice');
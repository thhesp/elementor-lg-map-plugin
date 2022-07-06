<?php
/**
 *  Settings  class.
 *
 * @category   Class
 * @package    ElementorLgMapPlugin
 * @subpackage WordPress
 * @author     THS
 * @copyright  2022 THS
 * @license    https://opensource.org/licenses/GPL-3.0 GPL-3.0-only
 * @link       link(https://letztegeneration.de/vortraege/,
 *             Letzte Generation Vortraege)
 * @since      1.0.0
 * php version 7.3.9
 */
if ( ! defined( 'ABSPATH' ) ) {
    // Exit if accessed directly.
    exit;
}

/**
 * Main Elementor MaMeetupBackendApi
 *
 */
final class MeetupSettings {

    protected static $instance = null;

    private $original_meetups = null;
    private $meetup_data = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        // Initialize the plugin.

        $this->addSettingsPage();
        add_action( 'admin_init', array($this, 'registerSettings') );
    }


    function addSettingsPage() {
        add_options_page( 'Letzte Generation Custom Karten', 'LG Custom Karten Einstellungen', 'manage_options', 'elementor-lg-map-plugin_settings_page', array ($this, 'renderPluginSettings') );
    }

    function renderPluginSettings(){
        // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // WordPress will add the "settings-updated" $_GET parameter to the url
        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error( 'elementor-lg-map-plugin', 'elementor-lg-map-plugin_message', __( 'Settings Saved', 'elementor-lg-map-plugin' ), 'updated' );
        }

        // show error/update messages
        settings_errors( 'elementor-lg-map-plugin_messages' );

         ?>
        <h2>Letzte Generation Plugin Konfiguration</h2>
        <form action="options.php" method="post">
            <?php 
                settings_fields( 'elementor-lg-map-plugin_settings' );
                do_settings_sections( 'elementor-lg-map-plugin' ); 
                // output save settings button
                submit_button( 'Save Settings' );
            ?>

        </form>

        <?php
            $optionsForCache = get_option( 'elementor-lg-map-plugin_metrics' );
        ?>
        <div>
            <p>API Requests: <?php echo $optionsForCache['api_requests'] ?></p>
            <p>Cache Hits:  <?php echo $optionsForCache['cache_hits'] ?></p>
            <p>Etag Hits:  <?php echo $optionsForCache['etag_hits'] ?></p>
            <p>CSV Loads: <?php echo $optionsForCache['csv_loads'] ?></p>
            <p>Geocode Calls:  <?php echo $optionsForCache['geocode_calls'] ?></p>
            <p>Querylimit Hits for Geocoding API:  <?php echo $optionsForCache['query_limit_hits'] ?></p>
        </div>
        <?php
    }

    function registerSettings() {
        register_setting( 'elementor-lg-map-plugin_settings', 'elementor-lg-map-plugin_settings');
        register_setting( 'elementor-lg-map-plugin_metrics', 'elementor-lg-map-plugin_metrics');

        add_settings_section( 'lg_meetup_settings', 'Konfiguration', array($this, 'configTextRender'), 'elementor-lg-map-plugin' );

        add_settings_field( 'elementor-lg-map-plugin_api_key', 'Google API Key', array($this, 'apiKeyRender'), 'elementor-lg-map-plugin', 'lg_meetup_settings' );
        add_settings_field( 'elementor-lg-map-plugin_mapbox_key', 'Mapbox API Key', array($this, 'mapboxKeyRender'), 'elementor-lg-map-plugin', 'lg_meetup_settings' );
        add_settings_field( 'elementor-lg-map-plugin_meetups_url', 'Vortraege URL', array($this, 'meetupsUrlRender'), 'elementor-lg-map-plugin', 'lg_meetup_settings' );
        add_settings_field( 'elementor-lg-map-plugin_blockades_url', 'Blockaden URL', array($this, 'blockadesUrlRender'), 'elementor-lg-map-plugin', 'lg_meetup_settings' );
        add_settings_field( 'elementor-lg-map-plugin_cache_duration', 'Frontend Cache Duration', array($this, 'cacheDuration'), 'elementor-lg-map-plugin', 'lg_meetup_settings' );
        add_settings_field( 'elementor-lg-map-plugin_backend_cache_duration', 'Max Backend Cache Duration', array($this, 'backendCacheDuration'), 'elementor-lg-map-plugin', 'lg_meetup_settings' );
    }

    function configTextRender(){
        echo '<p>Die Konfiguration für das Letzte Generation Kartenanzeige</p>';
    }

    function apiKeyRender(){
        $options = get_option( 'elementor-lg-map-plugin_settings' );
        echo "<input id='elementor-lg-map-plugin_settings_api_key' name='elementor-lg-map-plugin_settings[api_key]' type='text' value='" . esc_attr( $options['api_key'] ) . "' />";
    }
    
    function mapboxKeyRender(){
        $options = get_option( 'elementor-lg-map-plugin_settings' );
        echo "<input id='elementor-lg-map-plugin_settings_mapbox_key' name='elementor-lg-map-plugin_settings[mapbox_key]' type='text' value='" . esc_attr( $options['mapbox_key'] ) . "' />";
    }

    function meetupsUrlRender(){
        $options = get_option( 'elementor-lg-map-plugin_settings' );
        echo "<input id='elementor-lg-map-plugin_settings_meetups_url' name='elementor-lg-map-plugin_settings[meetups_url]' type='text' value='" . esc_attr( $options['meetups_url'] ) . "' />";
        echo "<p style='margin-left:10px'> Aktuelle Version geladen: ". $options['meetup_csv_load_time']."</p>
        <input hidden id='elementor-lg-map-plugin_settings_meetup_csv_load_time' name='elementor-lg-map-plugin_settings[meetup_csv_load_time]' type='text' value='" . esc_attr( $options['meetup_csv_load_time'] ) . "' />";
        echo "<p>Aktueller CSV ETag: ".get_transient("elementor-lg-map-plugin_meetups_csv_etag"). "</p>"; 
        echo "<button type='button' onclick='onMeetupReset()''>Reset Vorträge Cache</button>";
        echo "<script>
            function onMeetupReset() {
                fetch( '/wp-json/meetup/v1/reset', {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': '".wp_create_nonce('wp_rest')."'
                    }
                }).then(res => {
                        if(res.ok) {
                            alert('Cache reset');
                        } else {
                            alert('Failed mit status: ' + res.status);
                        }

                    })
                  .catch(err => alert('Failed'));
            }
        </script>";
    }

    function blockadesUrlRender(){
        $options = get_option( 'elementor-lg-map-plugin_settings' );
        echo "<input id='elementor-lg-map-plugin_settings_blockades_url' name='elementor-lg-map-plugin_settings[blockades_url]' type='text' value='" . esc_attr( $options['blockades_url'] ) . "' />";
        echo "<p style='margin-left:10px'> Aktuelle Version geladen: ". $options['blockades_csv_load_time']."</p>
        <input hidden id='elementor-lg-map-plugin_settings_blockades_csv_load_time' name='elementor-lg-map-plugin_settings[blockades_csv_load_time]' type='text' value='" . esc_attr( $options['blockades_csv_load_time'] ) . "' />";
        echo "<p>Aktueller CSV ETag: ".get_transient("elementor-lg-map-plugin_blockades_csv_etag"). "</p>"; 
        echo "<button type='button' onclick='onBlockadesReset()''>Reset Blockaden Cache</button>";
        echo "<script>
            function onBlockadesReset() {
                fetch( '/wp-json/blockades/v1/reset', {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': '".wp_create_nonce('wp_rest')."'
                    }
                }).then(res => {
                        if(res.ok) {
                            alert('Cache reset');
                        } else {
                            alert('Failed mit status: ' + res.status);
                        }

                    })
                  .catch(err => alert('Failed'));
            }
        </script>";
    }

    function cacheDuration(){
        $options = get_option( 'elementor-lg-map-plugin_settings', );
        echo "<input id='elementor-lg-map-plugin_settings_cache_duration' name='elementor-lg-map-plugin_settings[cache_duration]' type='text' value='" . esc_attr( $options['cache_duration'] ) . "' />";
    }

    function backendCacheDuration(){
        $options = get_option( 'elementor-lg-map-plugin_settings', );
        echo "<input id='elementor-lg-map-plugin_settings_backend_cache_duration' name='elementor-lg-map-plugin_settings[backend_cache_duration]' type='text' value='" . esc_attr( $options['backend_cache_duration'] ) . "' />";
    }

    public static function get_instance() {
        if ( ! isset( static::$instance ) ) {
            static::$instance = new static;
        }

        return static::$instance;
    }

   
  
}

add_action( 'admin_menu', 'my_settings_init' );
function my_settings_init() {
    MeetupSettings::get_instance();
}





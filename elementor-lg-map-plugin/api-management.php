<?php
/**
 * Cell Backend API  class.
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
 * Main Elementor ApiManagement
 *
 */
final class ApiManagement {

    protected static $instance = null;
    private $apis = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        $this->apis[] = BlockadesBackendApi::get_instance();
        $this->apis[] = MeetupBackendApi::get_instance();
        $this->apis[] = CellBackendApi::get_instance();

        add_filter( 'cron_schedules', array ($this, 'lg_map_plugin_cron_schedule') );
        $this->scheduleCronIfNecessary();
        $this->apiMgmtRoute();
    }


    // API Routes
    function apiMgmtRoute() {
         register_rest_route( 'apimgmt/v1', '/reschedule', array(
            'methods' => 'GET',
            'callback' => array ($this, 'forceRecheduleCron'),
            'permission_callback' => function () {
                  return current_user_can( 'manage_options' );
                }
          ) );
    }

    function lg_map_plugin_cron_schedule( $schedules ) {
        $schedules['15min'] = array(
                'interval'  => (60*15), // time in seconds
                'display'   => 'Every 15 Minutes'
        );
    return $schedules;
}

    public function init(){
        $this->updateRefreshTimer();
        foreach($this->apis as $api){
            if(!$api->dataExists()){
                $api->refresh();
            }
        }
    }

    public function refresh(){
        $this->updateRefreshTimer();
        foreach($this->apis as $api){
            $api->refresh();
        }
    }

    function updateRefreshTimer(){
        $options = get_option(  'elementor-lg-map-plugin_metrics' );

        $current_date = new DateTime(null, new DateTimeZone('Europe/Stockholm'));
        $options['api_management_refresh'] =  $current_date->format("H:i:s d.m.Y");

        update_option('elementor-lg-map-plugin_metrics' , $options);
    }

    public function forceRecheduleCron(){
        $this->unscheduleCron();
        $this->scheduleCron();
    }

    public function scheduleCronIfNecessary(){
        if( !$this->isCronScheduled())
        {
            $this->scheduleCron();
        }
    }

    public function isCronScheduled(){
        return wp_next_scheduled( 'lg-map-plugin-api-mgmt-refresh' );
    }

    public function scheduleCron(){
        wp_schedule_event( (time()+ 15*60), '15min', 'lg-map-plugin-api-mgmt-refresh' );
    }

    public function unscheduleCron(){
        wp_clear_scheduled_hook('lg-map-plugin-api-mgmt-refresh');
    }

    public static function get_instance() {
        if ( ! isset( static::$instance ) ) {
            static::$instance = new static;
        }

        return static::$instance;
    }
}

add_action( 'rest_api_init', 'api_management_init' );
function api_management_init() {
    ApiManagement::get_instance();
}


add_action ('lg-map-plugin-api-mgmt-refresh', 'api_management_refresh');
function api_management_refresh() {
    error_log('Refresh API mgmt');
    ApiManagement::get_instance()->refresh();
}



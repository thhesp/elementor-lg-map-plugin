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
        $options = get_option(  'elementor-lg-map-plugin_settings'  );

        $current_date = new DateTime(null, new DateTimeZone('Europe/Stockholm'));
        $options['api_management_refresh'] =  $current_date->format("H:i:s d.m.Y");

        update_option('elementor-lg-map-plugin_settings' , $options);
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
    ApiManagement::get_instance()->refresh();
}
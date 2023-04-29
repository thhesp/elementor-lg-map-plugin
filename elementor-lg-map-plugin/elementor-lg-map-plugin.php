<?php
/**
 * Letzte Generation Vorträge Plugin
 *
 * @package ElementorLgMapPlugin
 *
 * Plugin Name: Letzte Generation Vorträge Plugin
 * Description: Anzeigekarte für Letzte Generation Vorträge
 * Plugin URI:  https://letztegeneration.de/vortraege/
 * Version:     1.8.0
 * Author:      THS
 * Author URI:  https://letztegeneration.de/
 * Text Domain: elementor-lg-meetup-map
 */
define( 'ELEMENTOR_MAP_PLUGIN', __FILE__ );
/**
 * Include the different main files.
 */
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'class-elementor-lg-map-plugin.php';
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'meetup-api.php';
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'blockades-api.php';
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'cell-api.php';
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'training-api.php';
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'merge-csv-endpoint.php';
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'api-management.php';
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'settings.php';


register_activation_hook( __FILE__, 'api_management_scheduled');
register_deactivation_hook( __FILE__, 'api_management_unscheduled');


function api_management_scheduled() {
    ApiManagement::get_instance()->scheduleCronIfNecessary();
    ApiManagement::get_instance()->refresh();
}

function api_management_unscheduled() {
     ApiManagement::get_instance()->unscheduleCron();
}
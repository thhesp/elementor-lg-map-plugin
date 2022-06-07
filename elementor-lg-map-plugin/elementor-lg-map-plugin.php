<?php
/**
 * Letzte Generation Vorträge Plugin
 *
 * @package ElementorLgMapPlugin
 *
 * Plugin Name: Letzte Generation Vorträge Plugin
 * Description: Anzeigekarte für Letzte Generation Vorträge
 * Plugin URI:  https://letztegeneration.de/vortraege/
 * Version:     1.0.0
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
require plugin_dir_path( ELEMENTOR_MAP_PLUGIN ) . 'settings.php';
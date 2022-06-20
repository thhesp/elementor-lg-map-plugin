<?php
/**
 * Map Plugin class.
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

namespace ElementorLgMapPlugin\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
// Security Note: Blocks direct access to the plugin PHP files.
defined( 'ABSPATH' ) || die();
/**
 * Map Plugin widget class.
 *
 * @since 1.0.0
 */
class LgMapPlugin extends Widget_Base {
	/**
	 * Class constructor.
	 *
	 * @param array $data Widget data.
	 * @param array $args Widget arguments.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		wp_register_style( 'lg-map-plugin-css', plugins_url( '/assets/css/lg-map-plugin.css', ELEMENTOR_MAP_PLUGIN ), array(), '1.0.0' );
	
    wp_register_script( 'lg-map-plugin-js', plugins_url( '/assets/js/lg-map-plugin.js', ELEMENTOR_MAP_PLUGIN ), array(), '1.0.0' );
    wp_register_script( 'lg-map-plugin-meetups-js', plugins_url( '/assets/js/lg-map-plugin-meetups.js', ELEMENTOR_MAP_PLUGIN ), array(), '1.0.0' );
    wp_register_script( 'lg-map-plugin-blockades-js', plugins_url( '/assets/js/lg-map-plugin-blockades.js', ELEMENTOR_MAP_PLUGIN ), array(), '1.0.0' );
  }
    
	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'lg-map-plugin';
	}
	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Letzte Generation Meetups', 'elementor-lg-map-plugin' );
	}
	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-google-maps';
	}
	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'general' );
	}
	
	/**
	 * Enqueue styles.
	 */
	public function get_style_depends() {
		return array( 'lg-map-plugin-css' );
	}
        
        /**
	 * Enqueue scripts.
	 */
	public function get_script_depends() {
		return array( 'lg-map-plugin-js', 'lg-map-plugin-meetups-js', 'lg-map-plugin-blockades-js');
	}
        
	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function _register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'elementor-lg-map-plugin' ),
			)
		);
		$this->add_control(
			'load_meetup',
			array(
				'label'   => __( 'Vorträge anzeigen', 'elementor-lg-map-plugin' ),
				'type'    => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementor-lg-map-plugin' ),
				'label_off' => esc_html__( 'Hide', 'elementor-lg-map-plugin' ),
				'return_value' => 'yes',
				'default' => 'yes',
			)
		);
		$this->add_control(
			'load_blockades',
			array(
				'label'   => __( 'Blockaden anzeigen', 'elementor-lg-map-plugin' ),
				'type'    => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementor-lg-map-plugin' ),
				'label_off' => esc_html__( 'Hide', 'elementor-lg-map-plugin' ),
				'return_value' => 'yes',
				'default' => 'yes',
			)
		);
		$this->end_controls_section();
	}
	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
              <div id="vortraege-map"></div>
              	<script src='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.js'></script>
								<link href='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.css' rel='stylesheet' />
								<div onclick="makeScrollable()" id='zoomOverlay' style='width:100%; height: 500px;'><p>&#x1F446; interagieren</p></div>
								<div id='map' style='width:100%; height: 500px;'></div>
								<div id="legende-map" class="legende-map">
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="blockade" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/blockade-icon.svg">Blockade<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="soli" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/soli-icon.svg">Container-Aktion<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="farbe" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/farbaktion-icon.svg">Farbaktion<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="gesa" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/gesa-icon.svg">Gewahrsam<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="knast" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/knast-icon.svg">Gefängnis<br/>
								</div>
									<script>initMapboxMap();

									<?php
										if ( 'yes' === $settings['load_meetup'] ) {
												echo 'initMeetups();';
										} 
									?>


									<?php
										if ( 'yes' === $settings['load_blockades'] ) {
												echo 'initBlockades();';
										}
									?>

									</script>
									
    <?php
	}
	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function _content_template() {
    		?>
              <div id="vortraege-map"></div>
              	<script src='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.js'></script>
								<link href='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.css' rel='stylesheet' />
								<div onclick="makeScrollable()" id='zoomOverlay' style='width:100%; height: 500px;'><p>&#x1F446; interagieren</p></div>
								<div id='map' style='width:100%; height: 500px;'></div>
								<div id="legende-map" class="legende-map">
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="blockade" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/blockade-icon.svg">Blockade<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="soli" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/soli-icon.svg">Container-Aktion<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="farbe" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/farbaktion-icon.svg">Farbaktion<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="gesa" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/gesa-icon.svg">Gewahrsam<br/>
									<input type="checkbox" onchange="toggleCheckboxPins(this)" id="knast" checked><img src="https://letztegeneration.de/wp-content/themes/sydney-child/mapbox/icons/knast-icon.svg">Gefängnis<br/>
									</div>
									<script>initMapboxMap();

									<?php
										if ( 'yes' === get_option( 'elementor-lg-map-plugin_settings' )['load_meetup'] ) {
												echo 'initMeetups();';
										}
									?>


									<?php
										if ( 'yes' === get_option( 'elementor-lg-map-plugin_settings' )['load_blockades'] ) {
												echo 'initBlockades();';
										}
									?>

									</script>
									
    <?php
	}
}
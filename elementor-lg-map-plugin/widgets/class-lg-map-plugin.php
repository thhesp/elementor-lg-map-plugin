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
		wp_register_style( 'lg-map-plugin-css', plugins_url( '/assets/css/lg-map-plugin.css', ELEMENTOR_MAP_PLUGIN ), array(), '1.4.0' );
	
	    wp_register_script( 'lg-map-plugin-js', plugins_url( '/assets/js/lg-map-plugin.js', ELEMENTOR_MAP_PLUGIN ), array(), '1.4.0' );
	    wp_register_script( 'lg-map-plugin-meetups-js', plugins_url( '/assets/js/lg-map-plugin-meetups.js', ELEMENTOR_MAP_PLUGIN ), array(), '1.2.0' );
	    wp_register_script( 'lg-map-plugin-blockades-js', plugins_url( '/assets/js/lg-map-plugin-blockades.js', ELEMENTOR_MAP_PLUGIN ), array(), '1.2.0' );
	    wp_register_script( 'lg-map-plugin-cells-js', plugins_url( '/assets/js/lg-map-plugin-cells.js', ELEMENTOR_MAP_PLUGIN ), array(), '1.4.0' );
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
		return array( 'lg-map-plugin-js', 'lg-map-plugin-meetups-js', 'lg-map-plugin-blockades-js', 'lg-map-plugin-cells-js');
	}
        
	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.9
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
		$this->add_control(
			'load_cells',
			array(
				'label'   => __( 'Keimzellen anzeigen', 'elementor-lg-map-plugin' ),
				'type'    => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementor-lg-map-plugin' ),
				'label_off' => esc_html__( 'Hide', 'elementor-lg-map-plugin' ),
				'return_value' => 'yes',
				'default' => 'no',
			)
		);
		$this->add_control(
			'custom_focus',
			array(
				'label'   => __( 'Kartenfokus anpassen', 'elementor-lg-map-plugin' ),
				'type'    => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Custom', 'elementor-lg-map-plugin' ),
				'label_off' => esc_html__( 'Default', 'elementor-lg-map-plugin' ),
				'return_value' => 'yes',
				'default' => 'no',
			)
		);
		$this->add_control(
			'focus_latitude',
			array(
				'label' => esc_html__( 'Kartenfokus Latitude', 'elementor-lg-map-plugin' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( '51.33364994162832', 'elementor-lg-map-plugin' ),
				'condition' => [
					'custom_focus' => 'yes'
				],
			
			)
		);
		$this->add_control(
			'focus_longitude',
			array(
				'label' => esc_html__( 'Kartenfokus Longitude', 'elementor-lg-map-plugin' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( '10.70519290565511', 'elementor-lg-map-plugin' ),
				'condition' => [
					'custom_focus' => 'yes'
				],
			)
		);
		$this->add_control(
			'focus_zoom',
			array(
				'label' => esc_html__( 'Kartenfokus Zoom', 'elementor-lg-map-plugin' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'zoom'],
				'range' => [
					'zoom' => [
						'min' => -2,
						'max' => 22,
						'step' => 0.2,
					],
				],
				'default' => [
					'unit' => 'zoom',
					'size' => 5,
				],
				'condition' => [
					'custom_focus' => 'yes'
				],

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
			$mapboxKey = get_option( 'elementor-lg-map-plugin_settings' )['mapbox_key'];
			$settings = $this->get_settings_for_display();
			$mapUniqueId =  uniqid();
		?>
          	<script src='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.js'></script>
			<link href='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.css' rel='stylesheet' />
			<script>window.jQuery || document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js">\x3C/script>')</script>
			<div class='zoomOverlay' onclick="makeScrollable('zoomOverlay-<?php echo $mapUniqueId ?>' )" id='zoomOverlay-<?php echo $mapUniqueId ?>' style='width:100%; height: 500px;'><p>&#x1F446; interagieren</p></div>
			<div id='lg-map-plugin-map-<?php echo $mapUniqueId ?>' style='width:100%; height: 500px;'></div>
			<div class="legende-map" legend-for="lg-map-plugin-map-<?php echo $mapUniqueId; ?>"></div>
			<script>
				jQuery( window ).on( 'load', () => {
					<?php if('yes' === $settings['custom_focus']) { ?>
						var map<?php echo $mapUniqueId ?> = initMapboxMapWithFokus("lg-map-plugin-map-<?php echo $mapUniqueId ?>", "<?php echo $mapboxKey ?>", "<?php echo $settings['focus_latitude'] ?>", "<?php echo $settings['focus_longitude'] ?>", "<?php echo $settings['focus_zoom']['size'] ?>");
					<?php } else { ?>
						var map<?php echo $mapUniqueId ?> = initMapboxMap("lg-map-plugin-map-<?php echo $mapUniqueId ?>", "<?php echo $mapboxKey ?>");
					<?php } ?>

					

					<?php
						if ( 'yes' === $settings['load_meetup'] ) {
								echo 'initMeetups(map' .  $mapUniqueId . ');';
						} 
					?>


					<?php
						if ( 'yes' === $settings['load_blockades'] ) {
								echo 'initBlockades(map' .  $mapUniqueId . ');';
						}
					?>

					<?php
						if ( 'yes' === $settings['load_cells'] ) {
								echo 'initCells(map' .  $mapUniqueId . ');';
						}
					?>
				});
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
				$mapboxKey = get_option( 'elementor-lg-map-plugin_settings' )['mapbox_key'];
				$mapUniqueId =  uniqid();
    		?>
              	<script src='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.js'></script>
				<link href='https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.css' rel='stylesheet' />
				<script>window.jQuery || document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js">\x3C/script>')</script>
				<div class='zoomOverlay' onclick="makeScrollable('zoomOverlay-<?php echo $mapUniqueId ?>' )" id='zoomOverlay-<?php echo $mapUniqueId ?>' style='width:100%; height: 500px;'><p>&#x1F446; interagieren</p></div>
				<div id='lg-map-plugin-map-<?php echo $mapUniqueId ?>' style='width:100%; height: 500px;'></div>
				<div class="legende-map" legend-for="lg-map-plugin-map-<?php echo $mapUniqueId; ?>"></div>
				<script>
					jQuery( window ).on( 'frontend/element_ready/global', () => {

						<?php if('yes' === get_option( 'elementor-lg-map-plugin_settings' )['custom_focus']) { ?>
							var map<?php echo $mapUniqueId ?> = initMapboxMapWithFokus("lg-map-plugin-map-<?php echo $mapUniqueId ?>", "<?php echo $mapboxKey ?>", "<?php echo get_option( 'elementor-lg-map-plugin_settings' )['focus_latitude'] ?>", "<?php echo get_option( 'elementor-lg-map-plugin_settings' )['focus_longitude'] ?>", "<?php echo get_option( 'elementor-lg-map-plugin_settings' )['focus_zoom']['size'] ?>");
						<?php } else { ?>
							var map<?php echo $mapUniqueId ?> = initMapboxMap("lg-map-plugin-map-<?php echo $mapUniqueId ?>", "<?php echo $mapboxKey ?>");

						<?php } ?>


						<?php
							if ( 'yes' === get_option( 'elementor-lg-map-plugin_settings' )['load_meetup'] ) {
									echo 'initMeetups(map' .  $mapUniqueId . ');';
							}
						?>


						<?php
							if ( 'yes' === get_option( 'elementor-lg-map-plugin_settings' )['load_blockades'] ) {
									echo 'initBlockades(map' .  $mapUniqueId . ');';
							}
						?>

						<?php
							if ( 'yes' === get_option( 'elementor-lg-map-plugin_settings' )['load_cells'] ) {
									echo 'initCells(map' .  $mapUniqueId . ');';
							}
						?>
					});
				</script>
									
    	<?php
	}
}
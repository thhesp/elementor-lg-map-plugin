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
		return array( 'lg-map-plugin-js' );
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
			'url',
			array(
				'label'   => __( 'CSV URL to retrieve the data', 'elementor-lg-map-plugin' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( '', 'elementor-lg-map-plugin' ),
			)
		);
                $this->add_control(
			'apikey',
			array(
				'label'   => __( 'Google Maps API Key', 'elementor-lg-map-plugin' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( '', 'elementor-lg-map-plugin' ),
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
              <div id="map"></div>
                <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>  
                <script
                  src="https://maps.googleapis.com/maps/api/js?key=<?php echo $settings['apikey'] ?>&callback=initMap&v=weekly"
                  defer
                ></script>
                <script>
                		let apikey = '<?php echo $settings['apikey'] ?>';
                		let dataLoc = '<?php echo $settings['url'] ?>'; 
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
              <div id="map"></div>
                <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
                <script
                  src="https://maps.googleapis.com/maps/api/js?key={{{ settings.apikey }}}&callback=initMap&v=weekly"
                  defer
                ></script>
                <script>
                		let apikey = '{{{ settings.apikey }}}';
                		let dataLoc = '{{{ settings.url }}}'; 
                </script>
    <?php
	}
}
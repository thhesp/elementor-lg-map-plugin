<?php
/**
 * Blockades Backend API  class.
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
 * Main Elementor BlockadesBackendApi
 *
 */
final class BlockadesBackendApi {

    protected static $instance = null;

    private $original_blockades = null;
    private $blockades_data = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        // Initialize the plugin.
        $this->blockadesRoutes();
        $this->init();
    }

    // API Routes
    function blockadesRoutes() {
      register_rest_route( 'blockades/v1', '/all', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getAllBlockades')
      ) );

     register_rest_route( 'blockades/v1', '/original', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getOriginalData')
      ) );
    }

    function loadCSV($csvUrl){
        $data = $this->restRequestCSV($csvUrl);
        $rows = explode("\n",$data);

        foreach($rows as $row) {
            //skip empty lines
            $trimmedRow = trim($row);
            if(strlen($trimmedRow) > 0){
                $this->original_blockades[] = str_getcsv($trimmedRow);
            }
        }

    }

    function restRequestCSV($csvUrl){
        $data = file_get_contents($csvUrl);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $csvUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            if (true === WP_DEBUG) {
                error_log('Could not request CSV Data ' . curl_error($curl));
            }
            curl_close($curl);
            return false;
        }

        curl_close($curl);


        return $curl_response;
    }

    function prepareData($apikey){

        foreach($this->original_blockades as $row){
            $this->blockades_data[] = $this->buildApiData($row);
        }
    }

    function buildApiData($entry){
        return array(
                 'type' => 'farbe',
                 'live' => false,
                 'title' => 'Kanzleramt',
                 'description' => '<a target="_blank" href="https://goo.gl/maps/FiE6225d8RkW8Q5S8" style="color: #EE2F04" target="_blank">Willy-Brandt-Straße 1, Berlin</a><br>14.12.21 - Bundeskanzleramt-Fassade mit Forderungen bemalt<br>5 Gewahrsamnahmen.',
                 'pressebericht' => 'https://www.tagesspiegel.de/politik/gruppe-will-gesetz-gegen-lebensmittelverschwendung-klimaaktivisten-pinseln-forderungen-ans-kanzleramt/27892498.html',
                 'livestream' => '',
                 'geodata' => array(
                     'lat' => 13.369593489186151,
                     'lng' => 52.52017164399691
                 )
             );
    }

    function init() {
        $apikey = get_option( 'elementor-lg-map-plugin_settings' )['api_key'];
        $csvUrl = get_option( 'elementor-lg-map-plugin_settings' )['blockades_url'];

        $this->loadCSV($csvUrl);
        //$this->prepareData($apikey);
        $this->createTestData();
    }

    // API Endpoints
    function getAllBlockades() {
        $result = new WP_REST_Response($this->blockades_data, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age=1800'));

        return $result;
    }

    function getOriginalData(WP_REST_Request $request) {
        $result = new WP_REST_Response($this->original_blockades, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age=1800'));

        return $result;
    }
    

    public static function get_instance() {
        if ( ! isset( static::$instance ) ) {
            static::$instance = new static;
        }

        return static::$instance;
    }

   
       function createTestData(){
        $this->blockades_data[] = array(
                 'type' => 'farbe',
                 'live' => false,
                 'title' => 'Kanzleramt',
                 'description' => '<a target="_blank" href="https://goo.gl/maps/FiE6225d8RkW8Q5S8" style="color: #EE2F04" target="_blank">Willy-Brandt-Straße 1, Berlin</a><br>14.12.21 - Bundeskanzleramt-Fassade mit Forderungen bemalt<br>5 Gewahrsamnahmen.',
                 'pressebericht' => 'https://www.tagesspiegel.de/politik/gruppe-will-gesetz-gegen-lebensmittelverschwendung-klimaaktivisten-pinseln-forderungen-ans-kanzleramt/27892498.html',
                 'geodata' => array(
                     'lat' => 52.52017164399691,
                     'lng' => 13.369593489186151
                 )
             );


        $this->blockades_data[] = array(
                 'type' => 'blockade',
                 'live' => false,
                 'title' => 'A114 Blockade',
                 'description' => '<a href="goo.gl/maps/QJiesdzKeCf8XcES6" target="_blank" style="color: #EE2F04">A114 durch Blockade an der Kreuzung B109 mit Granitzstraße</a><br><b>24.01.:</b> 13 Bürger:innen blockieren.<br><b>26.01.:</b> 14 Bürger:innen blockieren.<br><b>28.01.:</b> 5 Bürger:innen blockieren zum vierten Mal.',
                 'geodata' => array(
                     'lat' => 52.57214688666855,
                     'lng' => 13.428687620761622
                 )
             );


        $this->blockades_data[] = array(
                 'type' => 'blockade',
                 'live' => false,
                 'title' => 'Flughafen Berlin',
                 'description' => '<a href="https://goo.gl/maps/2Wj784NYM9RvidGv8" target="_blank" style="color: #EE2F04">Hugo-Junkers-Ring unter Melli-Beese-Ring bei der Vereinigung der 3 Spuren zwischen Parkhäusern P7 und P8</a><br><b>23.02.:</b> 4 Bürger:innen blockieren.',
                 'geodata' => array(
                     'lat' => 52.366775,
                     'lng' => 13.511816
                 )
             );


        $this->blockades_data[] = array(
                 'type' => 'gesa',
                 'live' => false,
                 'title' => 'Gesa City',
                 'description' => '<a href="https://goo.gl/maps/tDuWZ6oxnYURSTQf9" target="_blank" style="color: #EE2F04">Polizei, Perleberger Str. 61A, Berlin</a><br><u>Gewahrsamnahmen nach Aktion</u><br>Essen Retten Berlin: 1 Bürgerin<br/>A114 (28.01.): 5 Bürger:innen<br><b>Aktiv: 0</b>',
                 'geodata' => array(
                     'lat' => 52.53361809370352,
                     'lng' => 13.353269788904822
                 )
             );


        $this->blockades_data[] = array(
                 'type' => 'gesa',
                 'live' => true,
                 'title' => 'Gesa Zentrale',
                 'description' => '<a href="https://goo.gl/maps/VeiQS1XiGgwhsL8i7" target="_blank" style="color: #EE2F04">LKA, Tempelhofer Damm 12, Berlin</a><br><u>Gewahrsamnahmen nach Aktion</u><br><b>Aktiv: 3<br>Insg.: 107</b><br>Kanzleramt: 5 Bürgerinnen<br/>A103,A114 24.1.: 24 Personen<br/>A103,A114 26.1.: 15 Personen<br/>A100: Seestr.,Beusselstr. 31.1.: 17 Pers.<br/>A100: Beusselstr. 4.2.: 5 Pers.<br/>A100: Spandauer Damm, Kurfürstendamm,Messedamm 7.2.: 20 Pers.<br/>A100 Tempelhofer Damm, Alboinstr.,Sachsendamm 8.2.: 13 Pers.<br/>A100/Tegeler Weg 9.2.: 8 Pers.',
                 'livestream' => 'Teststream',
                 'geodata' => array(
                     'lat' => 52.48230798703001,
                     'lng' => 13.385333698413097
                 )
            );

        $this->blockades_data[] = array(
                 'type' => 'soli',
                 'live' => false,
                 'title' => 'Essen retten',
                 'description' => '23.1.22 - Bürger:innen verschenken in Bayreuth containertes Essen im Rahmen des Aufstands der Letzten Generation.',
                 'pressebericht' => 'https://www.wiesentbote.de/2022/01/22/aktion-in-bayreuth-ziviler-widerstand-gegen-staatlich-tolerierte-lebensmittelverschwendung/',
                 'geodata' => array(
                     'lat' => 49.94386295584918,
                     'lng' => 11.575303298312033
                 )
            );
    }
  
}

add_action( 'rest_api_init', 'my_blockades_api_init' );
function my_blockades_api_init() {
    BlockadesBackendApi::get_instance();
}





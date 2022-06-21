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
 * @since      1.0.9
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
        $data = $this->restRequest($csvUrl);
        $rows = explode("\n",$data);

        foreach($rows as $row) {
            $trimmedRow = trim($row);
            //skip columns row
            if(str_starts_with($trimmedRow, "type,live,")){
                continue;
            }


            //skip empty lines
            if(strlen($trimmedRow) > 0){
                $this->original_blockades[] = str_getcsv($trimmedRow);
            }
        }

    }

    function restRequest($url){
        $data = file_get_contents($url);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            if (true === WP_DEBUG) {
                error_log('Could not request Data ' . curl_error($curl));
            }
            curl_close($curl);
            return false;
        }

        curl_close($curl);


        return $curl_response;
    }

    function prepareData(){

        foreach($this->original_blockades as $row){
            $this->blockades_data[] = $this->buildApiData($row,);
        }
    }


    function buildApiData($entry){
        $element = array(
            'type' => $entry[0],
            'live' => $this->isLive($entry[1]),
            'title' => $entry[2],
            'description' => $entry[3]
        );

        if($entry[6]){
            $element['pressebericht'] = $entry[5];
        }

        if($entry[7]){
            $element['livestream'] = $entry[6];
        }

        $element['geodata']['lat'] = floatval(trim(explode(',', $entry[4], )[1]));
        $element['geodata']['lng'] = floatval(trim(explode(',', $entry[4])[0]));

        return $element;
    }

    function isLive($liveString){
        return $liveString && $liveString == 'Ja';

    }

    function init() {
        $csvUrl = get_option( 'elementor-lg-map-plugin_settings' )['blockades_url'];

        $this->loadCSV($csvUrl);
        $this->prepareData();
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
  
}

add_action( 'rest_api_init', 'my_blockades_api_init' );
function my_blockades_api_init() {
    BlockadesBackendApi::get_instance();
}





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

      register_rest_route( 'blockades/v1', '/cachereset', array(
        'methods' => 'GET',
        'callback' => array ($this, 'resetCache')
      ) );
    }


    function resetCache(){
         wp_cache_delete("elementor-lg-map-plugin_blockades_csv", '');
         wp_cache_delete("elementor-lg-map-plugin_blockades_api", '');
    }

    function loadCSV($csvUrl){

        if(!get_transient("elementor-lg-map-plugin_blockades_csv", '')) {
            $data = $this->restRequest($csvUrl);
            $this->increaseMetrics('csv_loads');

            if($data){
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

                set_transient("elementor-lg-map-plugin_blockades_csv", $this->original_blockades, $this->getCacheDuration());
            }
        } else {
            $this->increaseMetrics('cache_hits');
            $this->original_blockades = get_transient("elementor-lg-map-plugin_blockades_csv", '');
        }
    }

    function restRequest($url){
        $data = file_get_contents($url);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $curl_response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            if (true === WP_DEBUG) {
                error_log('Could not request Data ' . curl_error($curl));
            }
            curl_close($curl);
            return false;
        }

        curl_close($curl);

        if($httpcode != 200){
            error_log('Could not retrieve data '. $httpcode);
            return false;
        }


        return $curl_response;
    }

    function prepareData(){
        if(!get_transient("elementor-lg-map-plugin_blockades_api", '')) {
            foreach($this->original_blockades as $row){
                $this->blockades_data[] = $this->buildApiData($row,);
            }

            set_transient("elementor-lg-map-plugin_blockades_api", $this->blockades_data,  $this->getCacheDuration());
        } else {
            $this->increaseMetrics('cache_hits');
            $this->blockades_data = get_transient("elementor-lg-map-plugin_blockades_csv", '');
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
        $this->increaseMetrics('api_requests');

        $csvUrl = get_option( 'elementor-lg-map-plugin_settings' )['blockades_url'];

        $this->loadCSV($csvUrl);
        $this->prepareData();
    }

    function increaseMetrics($identifier){
        $options = get_option(  'elementor-lg-map-plugin_metrics'  );
   
        if($options && array_key_exists($identifier, $options))  {
            $options[$identifier] = $options[$identifier]+1;
        } else {
            $options[$identifier] = 1;
        }

        update_option('elementor-lg-map-plugin_metrics' , $options);
    }

    // API Endpoints
    function getAllBlockades() {
        $this->init();
        $result = new WP_REST_Response($this->blockades_data, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getCacheDuration()));

        return $result;
    }

    function getOriginalData(WP_REST_Request $request) {
        $this->init();
        $result = new WP_REST_Response($this->original_blockades, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getCacheDuration()));

        return $result;
    }

    function getCacheDuration(){
        return get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] ? get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] : 1800;
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





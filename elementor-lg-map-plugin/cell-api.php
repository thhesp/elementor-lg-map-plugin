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
 * Main Elementor CellBackendApi
 *
 */
final class CellBackendApi {

    protected static $instance = null;

    private $original_cells = null;
    private $cell_data = null;
    private $geocode_addresses = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        // Initialize the plugin.
        $this->cellRoutes();
    }

    // API Routes
    function cellRoutes() {
      register_rest_route( 'cell/v1', '/all', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getAllCells')
      ) );

     register_rest_route( 'cell/v1', '/original', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getOriginalData')
      ) );

    register_rest_route( 'cell/v1', '/reset', array(
        'methods' => 'GET',
        'callback' => array ($this, 'resetCache'),
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        }
      ) );
    }

    function resetCache(){
        delete_transient("elementor-lg-map-plugin_cells_csv_etag");
        delete_transient("elementor-lg-map-plugin_cells_csv");
        delete_transient("elementor-lg-map-plugin_cells_api");
        $this->resetMetrics();
        $this->resetLoadTimer();

        return new WP_REST_Response("Cache reset", 200);
    }



    function loadCSV($csvUrl){
        $etag = get_transient("elementor-lg-map-plugin_cells_csv_etag");

        $data = $this->restRequestCSV($csvUrl, $etag);

        if(array_key_exists('csv', $data)) {
            if($data['csv']){
                $rows = explode("\n",$data['csv']);

                foreach($rows as $row) {
                    //skip empty lines
                    $trimmedRow = trim($row);

                    if(str_starts_with($trimmedRow, "ORT,KONTAKT")){
                        continue;
                    }

                    if (strlen(ltrim($trimmedRow, ',')) == 0) {
                        # empty entry
                        continue;
                    }

                    if(strlen($trimmedRow) > 0){
                        $this->original_cells[] = str_getcsv($trimmedRow);
                    }
                }

                set_transient("elementor-lg-map-plugin_cells_csv", $this->original_cells, $this->getBackendCacheDuration());
                delete_transient("elementor-lg-map-plugin_cells_api");
            }
        } else if(array_key_exists('cache', $data)){
            $this->increaseMetrics('cache_hits');
            $this->original_cells = get_transient("elementor-lg-map-plugin_cells_csv");
        }
    }


    function restRequestCSV($csvUrl, $etag){
        $data = file_get_contents($csvUrl);
        $curl = curl_init();
        $headers = [];

        curl_setopt($curl, CURLOPT_URL, $csvUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        if($etag){
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('If-None-Match:'.$etag));
        }

        $curl_response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            error_log('Could not request CSV Data ' . curl_error($curl));
            curl_close($curl);
            return false;
        }

        $etagResponse = $this->getEtag($headers);

        curl_close($curl);

        if($httpcode == 304 && get_transient("elementor-lg-map-plugin_cells_csv")){
            $this->increaseMetrics('etag_hits');
            return array('cache' => true);
        }

        $this->increaseMetrics('csv_loads');

        if($httpcode != 200){
            error_log('Could not retrieve data '. $httpcode);
            return false;
        }

        set_transient("elementor-lg-map-plugin_cells_csv_etag", $etagResponse, $this->getBackendCacheDuration());
        $this->updateLoadTimer();

        return array('csv' => $curl_response);
    }

    function getEtag($headers) {
        $etagOriginal = $headers['etag'][0];

        return str_replace("W/", "", $etagOriginal);
    }

    function prepareData($apikey){
        if(!get_transient("elementor-lg-map-plugin_cells_api")) {
            foreach($this->original_cells as $row){
                $address = $row[0];
                if(strlen($address) > 0){
                    $geocodeData = $this->geocodeCacheWrapper($apikey, $address);

                    if($geocodeData){
                        $this->cell_data[] = $this->buildApiData($row, $address, $geocodeData);
                    } 
                }
            }


            set_transient("elementor-lg-map-plugin_cells_api", $this->cell_data, $this->getBackendCacheDuration());
        } else {
            $this->increaseMetrics('cache_hits');
            $this->cell_data = get_transient("elementor-lg-map-plugin_cells_api");
        }
    }

    function buildApiData($entry, $usedAddress, $geocodeData){
        return array(
                 'city' => trim($entry[0]),
                 'contact' => trim($entry[1]),
                 'formatted_address' => $geocodeData[2],
                 'geodata' => array(
                     'lat' => $geocodeData[0],
                     'lng' => $geocodeData[1]
                 )
             );
    }

    function geocodeCacheWrapper($apikey, $address){
        if(in_array($address, $this->geocode_addresses)){
            return $this->geocode_addresses[$address];
        }

        $response = $this->geocode($apikey, $address);

        if($response){
            $this->geocode_addresses[$address] = $response;
        }

        return $response;
    }

    function geocode($apikey, $address) {
        $this->increaseMetrics('geocode_calls');
        $curl = curl_init();

        $escapedAddress = curl_escape($curl, $address);

        // google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?key={$apikey}&address={$escapedAddress}";

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $curl_response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            error_log('Geocoding failed with status code: ' . $httpcode);
            error_log('Could not geocode the following entry: ' . curl_error($curl));
            curl_close($curl);
            return false;
        }

        curl_close($curl);

        $resp = json_decode($curl_response, true);

        // response status will be 'OK', if able to geocode given address 
        if($resp['status'] == 'OK'){
     
            // get the important data
            $lati = isset($resp['results'][0]['geometry']['location']['lat']) ? $resp['results'][0]['geometry']['location']['lat'] : "";
            $longi = isset($resp['results'][0]['geometry']['location']['lng']) ? $resp['results'][0]['geometry']['location']['lng'] : "";
            $formatted_address = isset($resp['results'][0]['formatted_address']) ? $resp['results'][0]['formatted_address'] : "";
             
            // verify if data is complete
            if($lati && $longi && $formatted_address){
                return array($lati, 
                        $longi, 
                        $formatted_address);            
                 
            } else{
                error_log("Could not find lat&long for address: ". $address." with information: ".print_r($resp, true));
                return false;
            }
             
        } else if(strtolower($resp['status']) === strtolower("OVER_QUERY_LIMIT")){
            error_log("Reached query limit ". $address ." with information: ".print_r($resp, true));
            $this->increaseMetrics('query_limit_hits');
            return false;

        } else{
            error_log("Error during geocoding ". $address ." with information: ".print_r($resp, true));
            return false;
        }
    }

    function init() {
        $this->increaseMetrics('api_requests');
        $apikey = get_option( 'elementor-lg-map-plugin_settings' )['api_key'];
        $csvUrl = get_option( 'elementor-lg-map-plugin_settings' )['cells_url'];

        $this->loadCSV($csvUrl);
        $this->prepareData($apikey);
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

    function resetMetrics(){
        $options = get_option(  'elementor-lg-map-plugin_metrics'  );
        foreach ($options as $key => $value){
            $options[$key] = 0;
        }
        update_option('elementor-lg-map-plugin_metrics' , $options);
    }


    function updateLoadTimer(){
        $options = get_option(  'elementor-lg-map-plugin_settings'  );

        $current_date = new DateTime(null, new DateTimeZone('Europe/Stockholm'));
        $options['cell_csv_load_time'] =  $current_date->format("H:i:s d.m.Y");

        update_option('elementor-lg-map-plugin_settings' , $options);
    }

    function resetLoadTimer(){
        $options = get_option(  'elementor-lg-map-plugin_settings'  );

        $options['cell_csv_load_time'] =  null;

        update_option('elementor-lg-map-plugin_settings' , $options);
    }

    function getFrontendCacheDuration(){
        return get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] ? get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] : 1800;
    }

    function getBackendCacheDuration(){
        return get_option( 'elementor-lg-map-plugin_settings' )['backend_cache_duration'] ? get_option( 'elementor-lg-map-plugin_settings' )['backend_cache_duration'] : 86400;
    }


    // API Endpoints
    function getAllCells(WP_REST_Request $request) {
        $this->init();

        $result = new WP_REST_Response($this->cell_data, 200);
        
        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration()));

        return $result;
    }

    function getOriginalData(WP_REST_Request $request) {
        $this->init();
        $result = new WP_REST_Response($this->original_cells, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration()));

        return $result;
    }
    

    public static function get_instance() {
        if ( ! isset( static::$instance ) ) {
            static::$instance = new static;
        }

        return static::$instance;
    }

   
  
}

add_action( 'rest_api_init', 'my_cell_api_init' );
function my_cell_api_init() {
    CellBackendApi::get_instance();
}





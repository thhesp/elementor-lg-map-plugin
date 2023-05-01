<?php
/**
 * Training Backend API  class.
 *
 * @category   Class
 * @package    ElementorLgMapPlugin
 * @subpackage WordPress
 * @author     THS
 * @copyright  2022 THS
 * @license    https://opensource.org/licenses/GPL-3.0 GPL-3.0-only
 * @link       link(https://letztegeneration.de/vortraege/,
 *             Letzte Generation Vortraege)
 * @since      1.8.0
 * php version 7.3.9
 */
if ( ! defined( 'ABSPATH' ) ) {
    // Exit if accessed directly.
    exit;
}

/**
 * Main Elementor TrainingBackendApi
 *
 */
final class TrainingBackendApi {

    protected static $instance = null;

    private $original_trainings = null;
    private $training_data = null;
    private $geocode_addresses = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        $this->original_trainings = get_transient("elementor-lg-map-plugin_trainings_csv");
        $this->training_data = get_transient("elementor-lg-map-plugin_trainings_api");
        // Initialize the plugin.
        $this->trainingRoutes();
    }

    // API Routes
    function trainingRoutes() {
      register_rest_route( 'training/v1', '/all', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getAlltrainings')
      ) );

     register_rest_route( 'training/v1', '/original', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getOriginalData')
      ) );

    register_rest_route( 'training/v1', '/refresh', array(
        'methods' => 'GET',
        'callback' => array ($this, 'refreshCache'),
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        }
      ) );
    }

    function refreshCache(){
        $this->refresh();

        return new WP_REST_Response("Cache refresh", 200);
    }


    function loadCSV($csvUrl){
        $etag = get_transient("elementor-lg-map-plugin_trainings_csv_etag");

        $data = $this->restRequestCSV($csvUrl, $etag);

        if($data && array_key_exists('csv', $data)) {
            if($data['csv']){
                $this->original_trainings = array();
                $rows = explode("\n",$data['csv']);

                foreach($rows as $row) {
                    //skip empty lines
                    $trimmedRow = trim($row);

                    if(str_starts_with($trimmedRow, ",")){
                        continue;
                    }

                    if(str_starts_with($trimmedRow, "DATUM,UHRZEIT,")){
                        continue;
                    }

                    if (strlen(ltrim($trimmedRow, ',')) == 0) {
                        # empty entry
                        continue;
                    }

                    if(strlen($trimmedRow) > 0){
                        $this->original_trainings[] = str_getcsv($trimmedRow);
                    }
                }

                set_transient("elementor-lg-map-plugin_trainings_csv", $this->original_trainings);
                delete_transient("elementor-lg-map-plugin_trainings_api");
            } else {
                error_log('Did not get CSV data in the response' . print_r($data));
            }
        } else if($data && array_key_exists('cache', $data)){
            $this->increaseMetrics('cache_hits');
            $this->original_trainings = get_transient("elementor-lg-map-plugin_trainings_csv");
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

        if($httpcode == 304 && get_transient("elementor-lg-map-plugin_trainings_csv")){
            $this->increaseMetrics('etag_hits');
            return array('cache' => true);
        }

        $this->increaseMetrics('csv_loads');

        if($httpcode != 200){
            error_log('Could not retrieve data '. $httpcode);
            return false;
        }

        set_transient("elementor-lg-map-plugin_trainings_csv_etag", $etagResponse);
        $this->updateLoadTimer();

        return array('csv' => $curl_response);
    }

    function getEtag($headers) {
        if(array_key_exists("etag", $headers)){
            $etagOriginal = $headers['etag'][0];
            return str_replace("W/", "", $etagOriginal);
        }

        return "";
    }


    function prepareData($apikey){
        if(!get_transient("elementor-lg-map-plugin_trainings_api")) {
            $this->training_data = array();
            foreach($this->original_trainings as $row){
                $city = $row[2];
                if(strlen($city) > 0){
                    // retry with only city
                    $geocodeData = $this->geocodeCacheWrapper($apikey, $city);

                    if($geocodeData){
                        $this->training_data[] = $this->buildApiData($row, $geocodeData);
                    } else {
                        //write to error log
                        error_log('Failed while geodecoding following entry, skipping it: ' . print_r($row, true));
                    }
                } else {
                        //write to error log
                        error_log('Failed while geodecoding following entry, skipping it: ' . print_r($row, true));
                    }
            }
            set_transient("elementor-lg-map-plugin_trainings_api", $this->training_data);
        } else {
            $this->increaseMetrics('cache_hits');
            $this->training_data = get_transient("elementor-lg-map-plugin_trainings_api");
        }
    }


    function buildApiData($entry, $geocodeData){
        return array(
                 'city' => trim($entry[2]),
                 'type' => trim($entry[3]),
                 'date' => trim($entry[0]),
                 'time' => trim($entry[1]),
                 'formatted_address' => $geocodeData[2],
                 'geodata' => array(
                     'lat' => $geocodeData[0],
                     'lng' => $geocodeData[1]
                 )
             );
    }

    function extractMail($entry){
        if(str_contains ($entry,'href')){
            // extract mail from html

            //find mailto
            $searchString = "mailto:";
            $mailToPos = strpos($entry, $searchString);
            $endOfMail = strpos($entry, '"', $mailToPos);

            $mailLength = $endOfMail - $mailToPos - strlen ($searchString);
            
            return substr($entry, ($mailToPos + strlen($searchString)), $mailLength);
        } else {
            return $entry;
        }

    }

    function geocodeCacheWrapper($apikey, $city){
        if(in_array($city, $this->geocode_addresses)){
            return $this->geocode_addresses[$city];
        }

        $response = $this->geocode($apikey, $city);

        if($response){
            $this->geocode_addresses[$city] = $response;
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

    public function dataExists(){
        return $this->training_data && $this->original_trainings;
    }

    public function refresh() {
        $this->increaseMetrics('api_requests');
        $apikey = get_option( 'elementor-lg-map-plugin_settings' )['api_key'];
        $csvUrl = get_option( 'elementor-lg-map-plugin_settings' )['trainings_url'];

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
        $options['training_csv_load_time'] =  $current_date->format("H:i:s d.m.Y");

        update_option('elementor-lg-map-plugin_settings' , $options);
    }

    function resetLoadTimer(){
        $options = get_option(  'elementor-lg-map-plugin_settings'  );

        $options['training_csv_load_time'] =  null;

        update_option('elementor-lg-map-plugin_settings' , $options);
    }

    function getFrontendCacheDuration(){
        return get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] ? get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] : 1800;
    }

    function getTrainingsByLocation(){
        $trainingDataByLocation = array();
        foreach($this->training_data as $row){
            if(!array_key_exists($row['city'], $trainingDataByLocation)) {
                $trainingDataByLocation[$row['city']] = array(
                         'city' =>  $row['city'],
                         'contact' => $row['contact'],
                         'formatted_address' => $row['formatted_address'],
                         'geodata' => $row['geodata'],
                         'trainings' => array(
                                array(
                                 'date' => $row['date'],
                                 'time' => $row['time'],
                                 'type' => $row['type']
                             )
                            )
                     );
            } else {
                $trainingDataByLocation[$row['city']]['trainings'][] = array(
                                         'date' => $row['date'],
                                         'time' => $row['time'],
                                         'type' => $row['type']
                                     );
            }
        }

        return $trainingDataByLocation;
    }

    // API Endpoints
    function getAlltrainings(WP_REST_Request $request) {
        $groupByLocation = $request->get_param( 'groupByLocation' );

        $result = null;

        if($groupByLocation){
            $result = new WP_REST_Response($this->getTrainingsByLocation(), 200);
        } else {
            $result = new WP_REST_Response($this->training_data, 200);
        }
        

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration()));

        return $result;
    }

    function getOriginalData(WP_REST_Request $request) {
        $result = new WP_REST_Response($this->original_trainings, 200);

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





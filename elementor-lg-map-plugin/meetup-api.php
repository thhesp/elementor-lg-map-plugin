<?php
/**
 * Meetup Backend API  class.
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
 * Main Elementor MaMeetupBackendApi
 *
 */
final class MeetupBackendApi {

    protected static $instance = null;

    private $original_meetups = null;
    private $meetup_data = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        // Initialize the plugin.
        $this->meetupRoutes();
    }

    // API Routes
    function meetupRoutes() {
      register_rest_route( 'meetup/v1', '/all', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getAllMeetups')
      ) );

     register_rest_route( 'meetup/v1', '/original', array(
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
                $this->original_meetups[] = str_getcsv($trimmedRow);
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

        foreach($this->original_meetups as $row){
            $address = $this->extractAddress($row);
            if(strlen($address) > 0){
                $geocodeData = $this->geocode($apikey, $address);

                if($geocodeData){
                    $this->meetup_data[] = $this->buildApiData($row, $address, $geocodeData);
                } else {
                    // retry with only city
                    $geocodeData = $this->geocode($apikey, $row[2]);

                    if($geocodeData){
                        $this->meetup_data[] = $this->buildApiData($row, $address, $geocodeData);
                    } else {
                        //write to error log
                        if (true === WP_DEBUG) {
                            error_log('Could not geocode the following entry: ' . print_r($row, true));
                        }
                    }
                }
            }
        }
    }

    function extractAddress($entry){
        $address = "";
        if(isset($entry[3]) && strlen($entry[3])
            && !str_contains($entry[3],"<a")){
            $address = $entry[3];
        }

        if(isset($entry[2]) && strlen($entry[2])){
            $address .= " " . $entry[2];
        }

        return trim($address);
    }

    function buildApiData($entry, $usedAddress, $geocodeData){
        return array(
                 'lecturer' => trim($entry[4]),
                 'location' => trim($entry[3]),
                 'city' => trim($entry[2]),
                 'date' => trim($entry[1]),
                 'time' => trim($entry[0]),
                 'usedAddress' => $usedAddress,
                 'formatted_address' => $geocodeData[2],
                 'geodata' => array(
                     'lat' => $geocodeData[0],
                     'lng' => $geocodeData[1]
                 )
             );
    }

    function geocode($apikey, $address) {
        $curl = curl_init();

        $escapedAddress = curl_escape($curl, $address);

        // google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?key={$apikey}&address={$escapedAddress}";

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            if (true === WP_DEBUG) {
                error_log('Could not geocode the following entry: ' . curl_error($curl));
            }
            curl_close($curl);
            return false;
        }

        curl_close($curl);

        $resp = json_decode($curl_response, true);

        // response status will be 'OK', if able to geocode given address 
        if($resp['status']=='OK'){
     
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
                if (true === WP_DEBUG) {
                    error_log("Could not find lat&long for address: ". $address);
                }
                return false;
            }
             
        } else{
            if (true === WP_DEBUG) {
                error_log("Error during geocoding ". $address);
            }
            return false;
        }
    }

    function init(WP_REST_REQUEST $request) {
        $apikey = $request['key'];
        $csvUrl = $request['data'];

        $this->loadCSV($csvUrl);
        $this->prepareData($apikey);
    }

    // API Endpoints
    function getAllMeetups(WP_REST_Request $request) {
        if(!isset($this->original_meetups) || empty($this->original_meetups)){
            $this->init($request);
        }

        $result = new WP_REST_Response($this->meetup_data, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age=1800'));

        return $result;
    }

    function getOriginalData(WP_REST_Request $request) {
        if(!isset($this->original_meetups) || empty($this->original_meetups)){
            $this->init($request);
        }

        $result = new WP_REST_Response($this->original_meetups, 200);

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

add_action( 'rest_api_init', 'my_api_init' );
function my_api_init() {
    MeetupBackendApi::get_instance();
}





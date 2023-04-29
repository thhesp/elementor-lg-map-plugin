<?php
/**
 * Merged CSV Classes class.
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
final class MergedCSVsApi {

    protected static $instance = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        // Initialize the plugin.
        $this->csvRoutes();
    }

    // API Routes
    function csvRoutes() {
      register_rest_route( 'csv/v1', '/trainings', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getMergedTrainings')
      ) );

     register_rest_route( 'csv/v1', '/meetups', array(
        'methods' => 'GET',
        'callback' => array ($this, 'getMergedMeetups')
      ) );
    }


    function restRequestCSV($csvUrl){
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

        $curl_response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            error_log('Could not request CSV Data ' . curl_error($curl));
            curl_close($curl);
            return false;
        }

        curl_close($curl);

        if($httpcode != 200){
            error_log('Could not retrieve data '. $httpcode);
            return false;
        }

        return array('csv' => $curl_response);
    }


    // API Endpoints
    function getMergedTrainings() {
        $data = $this->loadMergedTrainings();
        $result = new WP_REST_Response("", 200);

        $etag = md5($data); 
        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration(), 'Etag' => $etag, 'Content-Type' => "text/csv"));

        return $result;
    }

    function loadMergedTrainings(){
        $csvURLBudibase = get_option( 'elementor-lg-map-plugin_settings' )['budibase_trainings_url'];
        $csvUrlEthercalc = get_option( 'elementor-lg-map-plugin_settings' )['ethercalc_trainings_url'];


        return $this->mergeCSVs($csvURLBudibase,$csvUrlEthercalc);
    }


    // API Endpoints
    function getMergedMeetups() {
        $data = $this->loadMergedMeetups();
        $result = new WP_REST_Response("", 200);

        $etag = md5($data); 
        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration(), 'Etag' => $etag, 'Content-Type' => "text/csv"));

        return $result;
    }

    function loadMergedMeetups(){
        $csvURLBudibase = get_option( 'elementor-lg-map-plugin_settings' )['budibase_meetups_url'];
        $csvUrlEthercalc = get_option( 'elementor-lg-map-plugin_settings' )['ethercalc_meetups_url'];


        return $this->mergeCSVs($csvURLBudibase,$csvUrlEthercalc);
    }

    function mergeCSVs($budibaseUrl, $ethercalcUrl){
        $csvBudibase = $this->restRequestCSV($budibaseUrl);
        $csvEthercalc = $this->restRequestCSV($ethercalcUrl);

        $fullCSV;

        if($csvBudibase && array_key_exists('csv', $csvBudibase)
            && $csvEthercalc && array_key_exists('csv', $csvEthercalc)) {
                $ethercalcArr = str_getcsv($csvEthercalc['csv'], "\n");

                $budibaseArr = str_getcsv($csvBudibase['csv'], "\n");

                unset($budibaseArr[0]);
                $fullCSV = array_merge($ethercalcArr, $budibaseArr);

                foreach($fullCSV as $row){
                    echo $row ."\n";
                }
        } else {
                error_log('Did not get CSV data in the response' . print_r($csvBudibase) . print_r($csvEthercalc));
        }


        return implode($fullCSV);
    }

    function getFrontendCacheDuration(){
        return get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] ? get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] : 1800;
    }

    public static function get_instance() {
        if ( ! isset( static::$instance ) ) {
            static::$instance = new static;
        }

        return static::$instance;
    }
  
}







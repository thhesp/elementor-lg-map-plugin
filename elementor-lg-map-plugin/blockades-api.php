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
        $this->original_blockades = get_transient("elementor-lg-map-plugin_blockades_csv");
        $this->blockades_data = get_transient("elementor-lg-map-plugin_blockades_api");
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

     register_rest_route( 'blockades/v1', '/refresh', array(
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
        $etag = get_transient("elementor-lg-map-plugin_blockades_csv_etag");

        $data = $this->restRequestCSV($csvUrl, $etag);
        if($data && array_key_exists('csv', $data)) {
            if($data['csv']){
                $this->original_blockades = array();
                $rows = explode("\n",$data['csv']);

                foreach($rows as $row) {
                    $trimmedRow = trim($row);
                    //skip columns row
                    if(str_starts_with($trimmedRow, "type,live,")){
                        continue;
                    }

                    if (strlen(ltrim($trimmedRow, ',')) == 0) {
                        # empty entry
                        continue;
                    }


                    //skip empty lines
                    if(strlen($trimmedRow) > 0){
                        $this->original_blockades[] = str_getcsv($trimmedRow);
                    }
                }

                set_transient("elementor-lg-map-plugin_blockades_csv", $this->original_blockades, $this->getBackendCacheDuration());
                delete_transient("elementor-lg-map-plugin_blockades_api");
            }
        } else if($data && array_key_exists('cache', $data)) {
            $this->increaseMetrics('cache_hits');
            $this->original_blockades = get_transient("elementor-lg-map-plugin_blockades_csv");
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

        if($httpcode == 304 && get_transient("elementor-lg-map-plugin_blockades_csv")){
            $this->increaseMetrics('etag_hits');
            return array('cache' => true);
        }

        $this->increaseMetrics('csv_loads');

        if($httpcode != 200){
            error_log('Could not retrieve data '. $httpcode);
            return false;
        }

        set_transient("elementor-lg-map-plugin_blockades_csv_etag", $etagResponse, $this->getBackendCacheDuration());
        $this->updateLoadTimer();

        return array('csv' => $curl_response);
    }

    function getEtag($headers) {
        $etagOriginal = $headers['etag'][0];

        return str_replace("W/", "", $etagOriginal);
    }



    function prepareData(){
        if(!get_transient("elementor-lg-map-plugin_blockades_api")) {
            $this->blockades_data = array();
            foreach($this->original_blockades as $row){
                $this->blockades_data[] = $this->buildApiData($row,);
            }

            set_transient("elementor-lg-map-plugin_blockades_api", $this->blockades_data, $this->getBackendCacheDuration());
        } else {
            $this->increaseMetrics('cache_hits');
            $this->blockades_data = get_transient("elementor-lg-map-plugin_blockades_api");
        }
    }


    function buildApiData($entry){
        $element = array(
            'type' => strtolower($entry[0]),
            'live' => $this->isLive($entry[1]),
            'title' => $entry[2],
            'description' => $entry[3]
        );

        if($entry[6]){
            $element['pressebericht'] = $entry[5];
        }

        if($entry[6]){
            $element['livestream'] = $entry[6];
        }

        $element['geodata']['lat'] = floatval(trim(explode(',', $entry[4], )[1]));
        $element['geodata']['lng'] = floatval(trim(explode(',', $entry[4])[0]));

        return $element;
    }

    function isLive($liveString){
        return $liveString && strtolower($liveString) == 'ja';

    }

    public function dataExists(){
        return $this->original_blockades && $this->blockades_data;
    }

    public function refresh() {
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
        $options['blockades_csv_load_time'] =  $current_date->format("H:i:s d.m.Y");

        update_option('elementor-lg-map-plugin_settings' , $options);
    }

    function resetLoadTimer(){
        $options = get_option(  'elementor-lg-map-plugin_settings'  );

        $options['blockades_csv_load_time'] =  null;

        update_option('elementor-lg-map-plugin_settings' , $options);
    }

    // API Endpoints
    function getAllBlockades() {
        $result = new WP_REST_Response($this->blockades_data, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration()));

        return $result;
    }

    function getOriginalData(WP_REST_Request $request) {
        $result = new WP_REST_Response($this->original_blockades, 200);

        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration()));

        return $result;
    }

    function getFrontendCacheDuration(){
        return get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] ? get_option( 'elementor-lg-map-plugin_settings' )['cache_duration'] : 1800;
    }


    function getBackendCacheDuration(){
        return get_option( 'elementor-lg-map-plugin_settings' )['backend_cache_duration'] ? get_option( 'elementor-lg-map-plugin_settings' )['backend_cache_duration'] : 86400;
    }
    

    public static function get_instance() {
        if ( ! isset( static::$instance ) ) {
            static::$instance = new static;
        }

        return static::$instance;
    }
  
}







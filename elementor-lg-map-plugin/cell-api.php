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

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        $this->cell_data = get_transient("elementor-lg-map-plugin_cells_api");
        $this->original_cells = get_transient("elementor-lg-map-plugin_cells_csv");
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

    register_rest_route( 'cell/v1', '/refresh', array(
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
        $etag = get_transient("elementor-lg-map-plugin_cells_csv_etag");

        $data = $this->restRequestCSV($csvUrl, $etag);

        if($data && array_key_exists('csv', $data)) {
            if($data['csv']){
                $this->original_cells = array();
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

                set_transient("elementor-lg-map-plugin_cells_csv", $this->original_cells);
                delete_transient("elementor-lg-map-plugin_cells_api");
            }
        } else if($data && array_key_exists('cache', $data)){
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

        set_transient("elementor-lg-map-plugin_cells_csv_etag", $etagResponse);
        $this->updateLoadTimer();

        return array('csv' => $curl_response);
    }

    function getEtag($headers) {
        $etagOriginal = $headers['etag'][0];

        return str_replace("W/", "", $etagOriginal);
    }



    function prepareData(){
        if(!get_transient("elementor-lg-map-plugin_cells_api")) {
            $this->cell_data = array();
            foreach($this->original_cells as $row){
                $this->cell_data[] = $this->buildApiData($row);
            }

            set_transient("elementor-lg-map-plugin_cells_api", $this->cell_data);
        } else {
            $this->increaseMetrics('cache_hits');
            $this->cell_data = get_transient("elementor-lg-map-plugin_cells_api");
        }
    }

    function buildApiData($entry){
        $element = array(
                 'city' => trim($entry[0]),
                 'contact' => trim($entry[1])
                 );

        $element['geodata']['lat'] = floatval(trim(explode(',', $entry[2], )[1]));
        $element['geodata']['lng'] = floatval(trim(explode(',', $entry[2])[0]));

        return $element;
    }

    public function dataExists(){
        return $this->original_cells && $this->cell_data;
    }

    public function refresh() {
        $this->increaseMetrics('api_requests');
        $csvUrl = get_option( 'elementor-lg-map-plugin_settings' )['cells_url'];

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

    // API Endpoints
    function getAllCells(WP_REST_Request $request) {
        $result = new WP_REST_Response($this->cell_data, 200);
        
        // Set headers.
        $result->set_headers(array('Cache-Control' => 'max-age='.$this->getFrontendCacheDuration()));

        return $result;
    }

    function getOriginalData(WP_REST_Request $request) {
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






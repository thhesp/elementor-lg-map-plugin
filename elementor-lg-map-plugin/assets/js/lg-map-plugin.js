function initMapboxMap(elementId) {
  mapboxgl.accessToken = 'pk.eyJ1IjoiY2xpbWF0ZS1nb256byIsImEiOiJja3RvMjk1Y2MwOGt5Mm5sZzNoeHVnMm45In0.goMBluE8qz03EeDMl4PElA';
  var lgMapPluginMap = new mapboxgl.Map({
  container: elementId,
    style: 'mapbox://styles/climate-gonzo/ckyecnidz4x6314nuzz75453s'
  });
  lgMapPluginMap.addControl(new mapboxgl.FullscreenControl());

  return lgMapPluginMap;
}

function makeScrollable(elementId) {
  document.getElementById( elementId ).style.display = 'none';
}

function toggleCheckboxPins(element) {
    var mapId = jQuery(element).attr("legend-for");

    console.log("toggling for element ", mapId);

    if(element.checked){
        switch (element.id) {
          case 'blockade':
            jQuery("#" + mapId + " .blockadeP").each(function() {
                jQuery(this).removeClass("marker-display-none");
            });
            break;
          case 'soli':
            jQuery("#" + mapId + " .soliP").each(function() {
                jQuery(this).removeClass("marker-display-none");
            });
            break;
          case 'farbe':
            jQuery("#" + mapId + " .farbeP").each(function() {
                jQuery(this).removeClass("marker-display-none");
            });
            break;
          case 'gesa':
            jQuery("#" + mapId + " .gesaP").each(function() {
                jQuery(this).removeClass("marker-display-none");
            });
            break;
          case 'knast':
            jQuery("#" + mapId + " .knastP").each(function() {
                jQuery(this).removeClass("marker-display-none");
            }); 
          case 'vortrag':
            jQuery("#" + mapId + " .vortragP").each(function() {
                jQuery(this).removeClass("marker-display-none");
            }); 
            break;
        } 
    } else {
       switch (element.id) {
          case 'blockade':
           jQuery("#" + mapId + " .blockadeP").each(function() {
                jQuery(this).addClass("marker-display-none");
            });
            break;
          case 'soli':
            jQuery("#" + mapId + " .soliP").each(function() {
                jQuery(this).addClass("marker-display-none");
            });
            break;
          case 'farbe':
            jQuery("#" + mapId + " .farbeP").each(function() {
                jQuery(this).addClass("marker-display-none");
            });
            break;
          case 'gesa':
            jQuery("#" + mapId + " .gesaP").each(function() {
                jQuery(this).addClass("marker-display-none");
            }); 
            break;
          case 'knast':
            jQuery("#" + mapId + " .knastP").each(function() {
                jQuery(this).addClass("marker-display-none");
            });
          case 'vortrag':
            jQuery("#" + mapId + " .vortragP").each(function() {
                jQuery(this).addClass("marker-display-none");
            });
            break;
        } 
    }
}

function buildLegendForMap(mapElement) {
    var mapId = mapElement._container.id;

    var legendElement = jQuery("div[legend-for='"+mapId+"']");

    var mapElement = jQuery("#" + mapId);

    jQuery(legendElement).empty();

    if(checkForMarker(mapElement, 'marker-blockade')) {
        jQuery(legendElement).append('<input type="checkbox" onchange="toggleCheckboxPins(this)" id="blockade" legend-for="' + mapId + '" checked><img src="/wp-content/plugins/elementor-lg-map-plugin/assets/images/blockade-icon.svg" >Blockade<br/>');
    }

    if(checkForMarker(mapElement, 'marker-soli')) {
        jQuery(legendElement).append('<input type="checkbox" onchange="toggleCheckboxPins(this)" id="soli" legend-for="' + mapId + '" checked><img src="/wp-content/plugins/elementor-lg-map-plugin/assets/images/soli-icon.svg">Container-Aktion<br/>');
    }

    if(checkForMarker(mapElement, 'marker-farbe')) {
        jQuery(legendElement).append('<input type="checkbox" onchange="toggleCheckboxPins(this)" id="farbe" legend-for="' + mapId + '" checked><img src="/wp-content/plugins/elementor-lg-map-plugin/assets/images/farbaktion-icon.svg" >Farbaktion<br/>');
    }

    if(checkForMarker(mapElement, 'marker-gesa')) {
        jQuery(legendElement).append('<input type="checkbox" onchange="toggleCheckboxPins(this)" id="gesa" legend-for="' + mapId + '" checked><img src="/wp-content/plugins/elementor-lg-map-plugin/assets/images/gesa-icon.svg" >Gewahrsam<br/>');
    }

    if(checkForMarker(mapElement, 'marker-knast')) {
        jQuery(legendElement).append('<input type="checkbox" onchange="toggleCheckboxPins(this)" id="knast" legend-for="' + mapId + '" checked><img src="/wp-content/plugins/elementor-lg-map-plugin/assets/images/knast-icon.svg" >Gefängnis<br/>');
    }

    if(checkForMarker(mapElement, 'marker-vortrag')) {
       jQuery(legendElement).append('<input type="checkbox" onchange="toggleCheckboxPins(this)" id="vortrag" legend-for="' + mapId + '" checked><img src="/wp-content/plugins/elementor-lg-map-plugin/assets/images/cropped-favicon-32x32.png" >Vorträge<br/>');
    }
}


function checkForMarker(parent, markerClass){
    return jQuery( parent ).find( "div.marker" ).hasClass(markerClass);
}


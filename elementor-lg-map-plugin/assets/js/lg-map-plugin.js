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
            break;
        } 
    }
}
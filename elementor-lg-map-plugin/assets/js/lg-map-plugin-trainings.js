function initTrainings(mapElement){
     getTrainings().then(data => {
      for (var location in data) { 
        // create a HTML element for each feature
        const el = document.createElement('div');
        el.className = 'marker marker-training trainingP';

        // make a marker for each feature and add to the map
        new mapboxgl.Marker(el)
          .setLngLat(data[location].geodata)
          .setPopup(
            new mapboxgl.Popup({ offset: 25 }) // add popups
              .setHTML(
                buildTrainingHtml(data[location])
              )
          )
          .addTo(mapElement);
      }

      buildLegendForMap(mapElement);
    });
}

async function getTrainings() {
      let url = window.location.protocol.concat("//").concat(window.location.host).concat("/wp-json/training/v1/all?groupByLocation=true");
      let response = await fetch(url);
      let data = await response.json();
      return data;
}

function buildTrainingHtml(entry){
    let hostUrl = 'https://' + window.location.host;
    let html = '<div class="map-popup">';
    //html += entry.location + ' in ' + entry.city + '<br>';
    let cityUmlauts = entry.city.toLowerCase();
    cityUmlauts = cityUmlauts.replace(/\u00fc/g, "ue");
    cityUmlauts = cityUmlauts.replace(/\u00dc/g, "Ue");
    cityUmlauts = cityUmlauts.replace(/\u00c4/g, "Ae");
    cityUmlauts = cityUmlauts.replace(/\u00e4/g, "ae");
    cityUmlauts = cityUmlauts.replace(/\u00d6/g, "Oe");
    cityUmlauts = cityUmlauts.replace(/\u00f6/g, "oe");
    cityUmlauts = cityUmlauts.replace(/\u00df/g, "ss");

    html += '<h3 class="training-city">' + entry.city + '</h3>';

  for(var i = 0; i < entry.trainings.length; i++){
    if(i > 0){
      html += '<div class="training-separator"></div>'
    }

    html += '<b>' + entry.trainings[i].date + ' ' + entry.trainings[i].time + '</b><br>';
    html += 'Typ : ' + entry.trainings[i].type  + '<br>';
  }

  html += '<p><a style="color:#FF4C00;" href="' + hostUrl + '/wig/' + cityUmlauts + '/">Widerstandsgruppe ' + entry.city + '</a></p>';
  html +=  '</div>';

  return html;
}
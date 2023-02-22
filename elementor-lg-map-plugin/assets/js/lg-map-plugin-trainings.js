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

  let html = '<div class="map-popup">';
  html += '<h3 class="training-city">' + entry.city + '</h3>';

  for(var i = 0; i < entry.trainings.length; i++){
    if(i > 0){
      html += '<div class="training-separator"></div>'
    }

    html += '<b>' + entry.trainings[i].date + ' ' + entry.trainings[i].time + '</b><br>';
    html += entry.trainings[i].trainer ? ' Trainer:in : ' + entry.trainings[i].trainer  + '<br>': '';
    html += 'Typ : ' + entry.trainings[i].type  + '<br>';
  }

  html += '<p><a href="mailto:' + entry.contact + '"><i class="kontakt-email"></i></a></p>';
  html +=  '</div>';

  return html;
}
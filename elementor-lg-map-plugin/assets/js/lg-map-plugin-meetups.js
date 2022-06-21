function initMeetups(mapElement){
     getMeetups().then(data => {
      for (let i = 0; i < data.length; i++) {
        // create a HTML element for each feature
        const el = document.createElement('div');
        el.className = 'marker marker-vortrag vortragP';

        // make a marker for each feature and add to the map
        new mapboxgl.Marker(el)
          .setLngLat(data[i].geodata)
          .setPopup(
            new mapboxgl.Popup({ offset: 25 }) // add popups
              .setHTML(
                buildMeetupHtml(data[i])
              )
          )
          .addTo(mapElement);
      }

      buildLegendForMap(mapElement);
    });
}

async function getMeetups() {
      let url = window.location.protocol.concat("//").concat(window.location.host).concat("/wp-json/meetup/v1/all");
      let response = await fetch(url);
      let data = await response.json();
      return data;
}

function buildMeetupHtml(entry){
  let html = '<div class="map-popup"><b>' + entry.date + ' ' + entry.time + '</b><br>';
  html += entry.lecturer ? ' Vortragende/-r: ' + entry.lecturer  + '<br>': '<br>';
  html += entry.location + ' in ' + entry.city + '</div>';

  return html;
}
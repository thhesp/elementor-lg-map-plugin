let vortraegeMap;

function initVortraegeMap() {

  vortraegeMap = new google.maps.Map(document.getElementById("vortraege-map"), {
    zoom: 5,
    center: new google.maps.LatLng(51.165691, 10.451526),
  });

   async function getMeetups() {
      let url = window.location.protocol.concat("//").concat(window.location.host).concat("/wp-json/meetup/v1/all");
      let response = await fetch(url);
      let data = await response.json();
      return data;
   }

   getMeetups().then(data => {
    for (let i = 0; i < data.length; i++) {
      let marker = new google.maps.Marker({
        position: data[i].geodata,
        icon: "https://letztegeneration.de/wp-content/uploads/2022/03/cropped-favicon-32x32.png",
        map: vortraegeMap,
      });

      let html = buildHtml(data[i]);

      let information = new google.maps.InfoWindow({
         content: html
      });

      marker.addListener('click', function() {
         information.open(vortraegeMap, marker);
      });
    }
  });
  

  function buildHtml(entry){
    let html = '<div class="map-popup"><b>' + entry.date + ' ' + entry.time + '</b>';
    html += entry.lecturer ? ' Vortragende/-r: ' + entry.lecturer  + '<br>': '<br>';
    html += entry.location + ' in ' + entry.city + '</div>';

    return html;
  }

  
}

window.initVortraegeMap = initVortraegeMap;
                
function initBlockades(mapElement){
     getBlockades().then(data => {
      console.log('Loaded blockages entries: ', data);
      for (let i = 0; i < data.length; i++) {
        // create a HTML element for each feature
        const el = document.createElement('div');
        el.className = getBlockadeClasses(data[i]);

        if(data[i].live){
          const iconimg = document.createElement('img');
          iconimg.src = getLiveIcon(data[i]);
          el.appendChild(iconimg);
        }

        // make a marker for each feature and add to the map
        new mapboxgl.Marker(el)
          .setLngLat(data[i].geodata)
          .setPopup(
            new mapboxgl.Popup({ offset: 25 }) // add popups
              .setHTML(
                buildBlockadePopupHtml(data[i])
              )
          )
          .addTo(mapElement);
      }
    });
}

async function getBlockades() {
      let url = window.location.protocol.concat("//").concat(window.location.host).concat("/wp-json/blockades/v1/all");
      let response = await fetch(url);
      let data = await response.json();
      return data;
}

function getLiveIcon(entry) {
   switch (entry.type) {
          case 'blockade':
              return '/wp-content/plugins/elementor-lg-map-plugin/assets/images/blockade-icon.png';
            break;
          case 'gesa':
              return '/wp-content/plugins/elementor-lg-map-plugin/assets/images/gesa-icon.png';
            break;

    } 
        console.log("Found not live icon");
}

function getBlockadeClasses(entry) {
   switch (entry.type) {
          case 'blockade':
              return 'marker marker-blockade blockadeP';
            break;
          case 'soli':
              return 'marker marker-soli soliP';
            break;
          case 'farbe':
              return 'marker marker-farbaktion farbeP';
            break;
          case 'gesa':
              return 'marker marker-gesa gesaP';
            break;
          case 'knast':
              return 'marker marker-knast knastP';
            break;
    } 
        console.log("Found not blockade class");
}

function buildBlockadePopupHtml(entry) {
  let html = '<h3>' + entry.title + '</h3><p>' + entry.description + '</p><p>';
  html += entry.pressebericht ? ' <p><a target="_blank" href=' + entry.pressebericht + '><i class="pressebericht"></i></a></p>': '';

  if(entry.live && entry.livestream){
    html += '<a target="_blank" href=' + entry.livestream +'><i class="live-icon"></i></a></p>';
  }

  return html;
}
function initCells(mapElement) {
    getCells().then(data => {
        for (let i = 0; i < data.length; i++) {
            // create a HTML element for each feature
            const el = document.createElement('div');
            el.className = 'marker marker-cell cellP';

            // make a marker for each feature and add to the map
            new mapboxgl.Marker(el).setLngLat(data[i].geodata).setPopup(
                new mapboxgl.Popup({offset: 25}) // add popups
                    .setHTML(
                        buildCellHtml(data[i]),
                    ),
            ).addTo(mapElement);
        }

        buildLegendForMap(mapElement);
    });
}

async function getCells() {
    let url = window.location.protocol.concat('//').concat(window.location.host).concat('/wp-json/cell/v1/all');
    let response = await fetch(url);
    let data = await response.json();
    return data;
}

function buildCellHtml(entry) {
    let hostUrl = 'https://' + window.location.host;
    let cityUmlauts = entry.city.toLowerCase();
    cityUmlauts = cityUmlauts.replace(/\u00fc/g, "ue");
    cityUmlauts = cityUmlauts.replace(/\u00dc/g, "Ue");
    cityUmlauts = cityUmlauts.replace(/\u00c4/g, "Ae");
    cityUmlauts = cityUmlauts.replace(/\u00e4/g, "ae");
    cityUmlauts = cityUmlauts.replace(/\u00d6/g, "Oe");
    cityUmlauts = cityUmlauts.replace(/\u00f6/g, "oe");
    cityUmlauts = cityUmlauts.replace(/\u00df/g, "ss");
    return '<h3>' + entry.city + '</h3><p><a style="color:#FF4C00;" href="' + hostUrl + '/wig/' + cityUmlauts + '/">Widerstandsgruppe ' + entry.city + '</a></p><p><a href="mailto:' + entry.contact + '"><i class="kontakt-email"></i></a></p>';
}

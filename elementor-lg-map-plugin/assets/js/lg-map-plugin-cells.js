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
    return '<h3>' + entry.city + '</h3><p><a target="_blank" href=mailto:"' + entry.contact + '"><i class="kontakt-email"></i></a></p>';
}

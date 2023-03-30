function initMeetups(mapElement) {
    getMeetups().then(data => {
        for (var location in data) {
            // create a HTML element for each feature
            const el = document.createElement('div');
            el.className = 'marker marker-vortrag vortragP';

            // make a marker for each feature and add to the map
            new mapboxgl.Marker(el)
                .setLngLat(data[location].geodata)
                .setPopup(
                    new mapboxgl.Popup({offset: 25}) // add popups
                        .setHTML(
                            buildMeetupHtml(data[location])
                        )
                )
                .addTo(mapElement);
        }

        buildLegendForMap(mapElement);
    });
}

async function getMeetups() {
    let url = window.location.protocol.concat("//").concat(window.location.host).concat("/wp-json/meetup/v1/all?groupByLocation=true");
    let response = await fetch(url);
    let data = await response.json();
    return data;
}

function buildMeetupHtml(entry) {
    let hostUrl = 'https://' + window.location.host;
    let html = '<div class="map-popup">';
    html += entry.location + ' in ' + entry.city + '<br>';

    for (let i = 0; i < entry.meetups.length; i++) {
        if (i > 0) {
            html += '<div class="meetup-separator"></div>'
        }

        let cityUmlauts = entry.city.toLowerCase();
        cityUmlauts = cityUmlauts.replace(/\u00fc/g, "ue");
        cityUmlauts = cityUmlauts.replace(/\u00dc/g, "Ue");
        cityUmlauts = cityUmlauts.replace(/\u00c4/g, "Ae");
        cityUmlauts = cityUmlauts.replace(/\u00e4/g, "ae");
        cityUmlauts = cityUmlauts.replace(/\u00d6/g, "Oe");
        cityUmlauts = cityUmlauts.replace(/\u00f6/g, "oe");
        cityUmlauts = cityUmlauts.replace(/\u00df/g, "ss");

        html += '<a style="color:#FF4C00;" href="' + hostUrl + '/wig/' + cityUmlauts + '/">Widerstandsgruppe ' + entry.city + '</a><br>';
        html += '<b>' + entry.meetups[i].date + ' ' + entry.meetups[i].time + '</b><br>';
        html += entry.meetups[i].lecturer ? ' Vortragende/-r: ' + entry.meetups[i].lecturer + '<br>' : '';
    }

    html += '</div>';
    return html;
}
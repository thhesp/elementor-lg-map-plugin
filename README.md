# Installationsanleitung
1. Ordner "elementor-lg-map-plugin" mit Inhalten in das Plugin Verzeichnis von Wordpress (wp-content/plugins/) kopieren. Die enthalten Dateien dem entsprechendem User (vermutlich www-data) als Owner hinzufügen und ggf. Nutzungsrechte anpassen (chmod).
2. Plugin in Wordpress aktivieren (Name: Letzte Generation Vorträge Plugin)
3. Element unter "General" in Elementor auswählen und konfigurieren
4. API Key für Google Maps generieren und für JS Maps + Geocoding freischalten (ggf. URL einschränken auf die genutzte Domain)

# Konfiguration
1. CSV URL hinterlegen
2. API Key für Google Maps Anbindung hinterlegen

# Weiterentwicklung

## CSS (elementor-lg-map-plugin/assets/css/lg-map-plugin.css)
Die CSS Datei sollte nur angezogen werden, wenn das Widget genutzt wird. Im Moment enthält sie primär die Größe der Google Map.

## Backend API (elementor-lg-map-plugin/meetup-api.php)
Enthält die über WP Api eingebunden Rest APIs. Leider ist es nicht ohne weiteres möglich auf die Settings aus dem Widget zuzugreifen.
Daher gibt das JS Frontend den API Key und die Location für die Vorträge als Queryparameter durch, so dass sie nicht doppelt konfiguriert werden müssen. Das ist nicht besonders schön, aber die alternativen wirkten nicht so gut.
Größtes Problem damit ist meiner Ansicht, dass jeder der sich auskennt und Zeit hat die URL für die CSV rausfinden kann und darüber ggf. an die ursprüngliche Tabelle kommt um damit Ärger zu machen.
An sich macht die Klasse nichts anders als das CSV zu laden und etwas zu bereinigen.
Danach versucht es über die Google Geocoding API anhand von Ort + Stadt bzw. alternativ nur Stadt die Adresse zu ermitteln und dadurch die Latitude & Longitude für die Map Marker zu haben.
Diese Daten werden dann als JSON für das Frontend (/wp-json/meetup/v1/all) bereitgestellt. Es gibt eine zweite JSON API die generell das CSV  (/wp-json/meetup/v1/original) wie es geladen wurde zurückgibt.
Im besten Fall sollten diese Daten gecached werden, so dass die Geocoding Anfragen an Google reduziert werden.

## Elementor Widget Code Backend (elementor-lg-map-plugin/widgets/class-lg-map-plugin.php)
Hier ist das eigentliche Widget definiert, der Großteil davon ist Boilerplate Code der notwendig ist.
Darin ist z.B. definiert, dass es das eigene CSS und JSON gibt das geladen werden sollte.
Primär interessant sind die Methoden **render**/**content_template** und **register_controls**.

In **render** wird das grundsätzlich angezeigte HTML generiert das für die Anzeige des Widgets benötigt wird. Hier werden auch der API Key & die URL an das Frontendweitergegeben als Javascript Variablen.

In **register_controls** werden die Konfigurationsmöglichkeiten des Widgets definiert.


## Elementor JS Frontend (elementor-lg-map-plugin/js/lg-map-plugin.js)
Das JS Frontend ist an sich auch sehr minimal. Es initialisiert zunächst Google Maps und lädt asynchron aus dem Backend die Vorträge mit den dazugehörigen Daten. Diese werden dann als Marker auf Google Maps angelegt und jeder bekommt einen "onclick"-Listener, damit eine nette Infobox aufgeht, wenn er geklickt wird.
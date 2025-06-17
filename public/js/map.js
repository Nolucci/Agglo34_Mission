document.addEventListener("DOMContentLoaded", function () {
    const map = L.map("map-agglomeration").setView([43.3442, 3.2158], 10);

    L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    // Chargement des données GeoJSON
    fetch("/data/beziers-agglo-points.geojson")
        .then(res => res.json())
        .then(data => {
            const layer = L.geoJSON(data, {
                pointToLayer: function (feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 6,
                        fillColor: "#1e90ff",
                        color: "#fff",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.9
                    });
                },
                onEachFeature: function (feature, layer) {
                    const props = feature.properties;

                    // Tooltip (survol)
                    if (props && props.nom) {
                        layer.bindTooltip(props.nom, {
                            direction: 'top',
                            offset: [0, -5],
                            sticky: true
                        });
                    }

                    // Pas de popup au clic
                }
            }).addTo(map);

            map.fitBounds(layer.getBounds(), {
                padding: [20, 20],
                maxZoom: 13
            });
        })
        .catch(err => {
            console.error("Erreur de chargement GeoJSON :", err);
        });

});
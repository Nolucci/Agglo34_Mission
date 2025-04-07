document.addEventListener("DOMContentLoaded", function () {
    const map = L.map("map-agglomeration").setView([43.3442, 3.2158], 10);

    L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    // Chargement des données GeoJSON
    fetch("data/beziers-agglo-points.geojson")
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

                    // Popup (clic)
                    layer.on('click', () => {
                        let rows = "";
                        for (const [key, value] of Object.entries(props)) {
                            rows += `<tr><th>${key}</th><td>${value}</td></tr>`;
                        }

                        const popupContent = `
                        <div>
                            <h5>${props.nom}</h5>
                            <table class="table table-bordered table-sm">
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    `;

                        layer.bindPopup(popupContent, {
                            offset: [0, -5],
                            autoPan: true,
                            closeButton: true
                        }).openPopup();
                    });
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
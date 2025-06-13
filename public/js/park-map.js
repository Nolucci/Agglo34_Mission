console.log('Chargement de park-map.js');

// Variable globale pour stocker la carte Leaflet
let parkMap = null;

// Variable pour suivre l'état de chargement des données
let dataLoaded = false;

// Variable pour stocker la commune sélectionnée pendant le chargement
let pendingMunicipalityName = null;

// Fonction pour initialiser la carte
function initializeMap() {
    console.log('Initialisation de la carte...');

    if (parkMap) {
        console.log('La carte est déjà initialisée');
        return;
    }

    const mapElement = document.getElementById("map-agglomeration");
    if (!mapElement) {
        console.error("Élément de carte non trouvé");
        return;
    }

    console.log('Création de la carte Leaflet');
    parkMap = L.map("map-agglomeration").setView([43.3442, 3.2158], 10);

    L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(parkMap);

    loadMapData();
}

// Fonction pour charger les données de la carte
function loadMapData() {
    console.log('Chargement des données GeoJSON');
    // Réinitialiser l'état de chargement
    dataLoaded = false;

    fetch("/data/beziers-agglo-points.geojson")
        .then(res => res.json())
        .then(data => {
            console.log('Données GeoJSON reçues');

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
                onEachFeature: setupFeatureInteraction
            }).addTo(parkMap);

            parkMap.fitBounds(layer.getBounds(), {
                padding: [20, 20],
                maxZoom: 13
            });

            // Marquer les données comme chargées
            dataLoaded = true;

            // Si une commune a été sélectionnée pendant le chargement, charger ses données maintenant
            if (pendingMunicipalityName) {
                console.log('Chargement des données de la commune en attente:', pendingMunicipalityName);
                fetchMunicipalityStatistics(pendingMunicipalityName);
                pendingMunicipalityName = null;
            }
        })
        .catch(err => {
            console.error("Erreur de chargement GeoJSON :", err);
            // En cas d'erreur, on considère quand même que le chargement est terminé
            dataLoaded = true;
        });
}

// Fonction pour configurer l'interaction avec les entités de la carte
function setupFeatureInteraction(feature, layer) {
    const props = feature.properties;

    // Tooltip (survol)
    if (props && props.nom) {
        layer.bindTooltip(props.nom, {
            direction: 'top',
            offset: [0, -5],
            sticky: true
        });
    }

    // Popup et actions au clic
    layer.on('click', () => handleFeatureClick(props, layer));
}

// Fonction pour gérer le clic sur une entité de la carte
function handleFeatureClick(props, layer) {
    console.log('Clic sur la commune:', props.nom);

    // Afficher le popup avec les informations de la commune
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

    // Mettre à jour le titre de la carte "Statistiques du Parc"
    const parkStatsTitle = document.getElementById('park-stats-title');
    if (parkStatsTitle && props && props.nom) {
        parkStatsTitle.textContent = `Équipement de ${props.nom}`;
    }

    // Afficher un indicateur de chargement
    const statsCard = document.getElementById('equipment-stats-body');
    if (statsCard) {
        statsCard.innerHTML = `
            <div class="text-center mb-3">
                <h5>Chargement des données...</h5>
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>
        `;
    }

    // Si les données ne sont pas encore chargées, stocker le nom de la commune pour plus tard
    if (!dataLoaded) {
        console.log('Les données ne sont pas encore complètement chargées, stockage de la commune pour plus tard');
        pendingMunicipalityName = props.nom;
        return;
    }

    // Récupérer et afficher les statistiques de la commune
    fetchMunicipalityStatistics(props.nom);
}

// Fonction pour récupérer et afficher les statistiques d'une commune
function fetchMunicipalityStatistics(municipalityName) {
    // Récupérer l'ID de la commune à partir de son nom
    const municipalityUrl = `/api/municipalities/find-by-name/${encodeURIComponent(municipalityName)}`;
    console.log('Appel API pour trouver la commune:', municipalityUrl);

    fetch(municipalityUrl)
        .then(response => {
            console.log('Réponse de recherche de commune:', response.status);
            if (!response.ok) {
                throw new Error(`Commune non trouvée (${response.status})`);
            }
            return response.json();
        })
        .then(municipalityData => {
            console.log('Données de la commune:', municipalityData);

            // Récupérer les statistiques des équipements pour cette commune
            const statsUrl = `/equipment/statistics/${municipalityData.id}`;
            console.log('Appel API pour les statistiques:', statsUrl);

            return fetch(statsUrl);
        })
        .then(response => {
            console.log('Réponse des statistiques:', response.status);
            if (!response.ok) {
                throw new Error(`Erreur lors de la récupération des statistiques (${response.status})`);
            }
            return response.json();
        })
        .then(statsData => {
            console.log('Données de statistiques reçues:', statsData);
            updateStatisticsCard(statsData);
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des statistiques:', error);
            // Afficher un message d'erreur dans la carte des statistiques
            const statsCard = document.getElementById('equipment-stats-body');
            if (statsCard) {
                statsCard.innerHTML = `
                    <div class="text-center mb-3">
                        <h5>Erreur</h5>
                        <p class="text-danger">Impossible de charger les données pour cette commune.</p>
                    </div>
                `;
            }
        });
}

// Fonction pour mettre à jour la carte des statistiques
function updateStatisticsCard(statsData) {
    // Sélectionner directement la carte par son ID
    const statsCard = document.getElementById('equipment-stats-body');
    console.log('Carte trouvée par ID:', statsCard ? 'Oui' : 'Non');

    if (statsCard) {
        // Formater les versions d'équipements pour l'affichage
        const versionsHtml = statsData.equipmentVersions.length > 0
            ? statsData.equipmentVersions.map(v => `<span class="badge badge-info">${v || 'Non défini'}</span>`).join(' ')
            : '<span class="badge badge-secondary">Aucune version</span>';

        // Mettre à jour le contenu de la carte
        statsCard.innerHTML = `
            <div class="text-center mb-3">
                <h5>${statsData.municipalityName}</h5>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="card bg-light">
                        <div class="card-body p-2 text-center">
                            <h6>Équipements</h6>
                            <h3>${statsData.equipmentCount}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-light">
                        <div class="card-body p-2 text-center">
                            <h6>Incidents</h6>
                            <h3>${statsData.incidentCount}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <h6>Versions des équipements:</h6>
                <div class="mt-2">
                    ${versionsHtml}
                </div>
            </div>
        `;
    } else {
        console.error("Carte des statistiques non trouvée");
    }
}

// Initialiser la carte après le chargement du DOM
document.addEventListener("DOMContentLoaded", function() {
    console.log('DOM chargé dans park-map.js');
    initializeMap();
});

// S'assurer que la carte est initialisée même si le DOMContentLoaded a déjà été déclenché
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    console.log('Document déjà chargé, initialisation immédiate');
    setTimeout(initializeMap, 1);
}
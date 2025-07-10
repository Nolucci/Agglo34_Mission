/**
 * Fonctionnalité de recherche globale pour les listes avec pagination dynamique
 * Permet de filtrer les données sur tous les attributs avec mise à jour de la pagination
 */

// Variables globales pour la gestion de la recherche
let searchTimeouts = {};

// Fonction pour effectuer une recherche avec pagination côté serveur
function performServerSearch(searchTerm, page = 1, entityType) {
    const endpoints = {
        'phone-lines': '/api/phone-line/list',
        'equipment': '/equipment/list',
        'boxes': '/api/box/list'
    };

    const renderFunctions = {
        'phone-lines': window.renderLinesTable,
        'equipment': window.renderEquipmentsTable,
        'boxes': window.renderBoxesTable
    };

    const paginationFunctions = {
        'phone-lines': window.renderPagination,
        'equipment': window.renderEquipmentPagination,
        'boxes': window.renderBoxPagination
    };

    const endpoint = endpoints[entityType];
    const renderFunction = renderFunctions[entityType];
    const paginationFunction = paginationFunctions[entityType];

    if (!endpoint || !renderFunction) {
        console.error(`Configuration manquante pour le type d'entité: ${entityType}`);
        return;
    }

    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set('page', page);
    url.searchParams.set('limit', 50);
    if (searchTerm) {
        url.searchParams.set('search', searchTerm);
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les variables globales selon le type d'entité
            if (entityType === 'phone-lines') {
                window.allPhoneLines = data.data || [];
                window.displayedPhoneLines = [...window.allPhoneLines];
                window.currentPage = data.page;
                window.totalPages = data.totalPages;
                window.totalItems = data.total;
                renderFunction(window.displayedPhoneLines);
            } else if (entityType === 'equipment') {
                window.allEquipments = data.equipments || [];
                window.displayedEquipments = [...window.allEquipments];
                window.currentPage = data.page;
                window.totalPages = data.totalPages;
                window.totalItems = data.total;
                renderFunction(window.displayedEquipments);
            } else if (entityType === 'boxes') {
                window.allBoxes = data.boxes || [];
                window.displayedBoxes = [...window.allBoxes];
                window.currentPage = data.page;
                window.totalPages = data.totalPages;
                window.totalItems = data.total;
                renderFunction(window.displayedBoxes);
            }

            // Mettre à jour la pagination si la fonction existe
            if (paginationFunction && typeof paginationFunction === 'function') {
                paginationFunction();
            }

            console.log(`Recherche ${entityType} terminée:`, {
                terme: searchTerm,
                résultats: data.total,
                page: data.page,
                totalPages: data.totalPages
            });
        })
        .catch(error => {
            console.error(`Erreur lors de la recherche ${entityType}:`, error);
        });
}

// Fonction pour configurer la recherche sur une page spécifique
function setupPageSearch() {
    console.log("Configuration de la recherche avec pagination dynamique...");

    // Lignes téléphoniques
    if (document.getElementById('searchInput')) {
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            console.log("Recherche lignes téléphoniques:", searchTerm);

            // Annuler la recherche précédente si elle existe
            if (searchTimeouts['phone-lines']) {
                clearTimeout(searchTimeouts['phone-lines']);
            }

            // Délai pour éviter trop de requêtes
            searchTimeouts['phone-lines'] = setTimeout(() => {
                // Utiliser la fonction loadPhoneLines qui gère les filtres
                if (window.loadPhoneLines) {
                    window.currentSearchTerm = searchTerm;
                    window.loadPhoneLines(1, searchTerm, window.currentFilters || {});
                } else {
                    performServerSearch(searchTerm, 1, 'phone-lines');
                }
            }, 300);
        });
    }

    // Parc informatique
    if (document.getElementById('searchEquipmentInput')) {
        const searchInput = document.getElementById('searchEquipmentInput');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            console.log("Recherche équipements:", searchTerm);

            // Annuler la recherche précédente si elle existe
            if (searchTimeouts['equipment']) {
                clearTimeout(searchTimeouts['equipment']);
            }

            // Délai pour éviter trop de requêtes
            searchTimeouts['equipment'] = setTimeout(() => {
                // Utiliser la fonction loadEquipments qui gère les filtres
                if (window.loadEquipments) {
                    window.currentSearchTerm = searchTerm;
                    window.loadEquipments(1, searchTerm, window.currentFilters || {});
                } else {
                    performServerSearch(searchTerm, 1, 'equipment');
                }
            }, 300);
        });
    }

    // Box
    if (document.getElementById('searchBoxInput')) {
        const searchInput = document.getElementById('searchBoxInput');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            console.log("Recherche boxes:", searchTerm);

            // Annuler la recherche précédente si elle existe
            if (searchTimeouts['boxes']) {
                clearTimeout(searchTimeouts['boxes']);
            }

            // Délai pour éviter trop de requêtes
            searchTimeouts['boxes'] = setTimeout(() => {
                performServerSearch(searchTerm, 1, 'boxes');
            }, 300);
        });
    }
}

// Fonction pour effectuer une recherche avec pagination (utilisée par les boutons de pagination)
window.searchWithPagination = function(searchTerm, page, entityType) {
    performServerSearch(searchTerm, page, entityType);
};

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM chargé, configuration de la recherche avec pagination...");
    setupPageSearch();
});

// Configurer également après le chargement complet de la page
window.addEventListener('load', function() {
    console.log("Page entièrement chargée, configuration de la recherche avec pagination...");
    setupPageSearch();
});
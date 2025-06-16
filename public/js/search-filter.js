/**
 * Fonctionnalité de recherche globale pour les listes
 * Permet de filtrer les données sur tous les attributs
 */

// Fonction pour rechercher dans un objet, y compris les objets imbriqués
function searchInObject(obj, searchTerm) {
    if (!obj || typeof obj !== 'object') return false;

    // Parcourir toutes les propriétés de l'objet
    return Object.keys(obj).some(key => {
        const value = obj[key];

        // Ignorer les propriétés null ou undefined
        if (value === null || value === undefined) return false;

        // Si la valeur est un objet (mais pas un tableau), rechercher récursivement
        if (typeof value === 'object' && !Array.isArray(value)) {
            return searchInObject(value, searchTerm);
        }

        // Pour les valeurs simples, convertir en chaîne et vérifier
        try {
            const stringValue = String(value).toLowerCase();
            return stringValue.includes(searchTerm);
        } catch (e) {
            console.error("Erreur lors de la conversion en chaîne:", e);
            return false;
        }
    });
}

// Fonction pour configurer la recherche sur une page spécifique
function setupPageSearch() {
    console.log("Configuration de la recherche...");

    // Lignes téléphoniques
    if (document.getElementById('searchInput')) {
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            console.log("Recherche de:", searchTerm);

            if (typeof window.allPhoneLines !== 'undefined' && typeof window.renderLinesTable === 'function') {
                if (searchTerm === '') {
                    window.renderLinesTable(window.allPhoneLines);
                    return;
                }

                const filteredLines = window.allPhoneLines.filter(line => {
                    return searchInObject(line, searchTerm);
                });

                console.log("Résultats filtrés:", filteredLines.length);
                window.renderLinesTable(filteredLines);
            } else {
                console.error("Variables ou fonctions nécessaires non disponibles pour les lignes téléphoniques");
            }
        });
    }

    // Box
    if (document.getElementById('searchBoxInput')) {
        const searchInput = document.getElementById('searchBoxInput');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            console.log("Recherche de:", searchTerm);

            if (typeof window.allBoxes !== 'undefined' && typeof window.renderBoxesTable === 'function') {
                if (searchTerm === '') {
                    window.renderBoxesTable(window.allBoxes);
                    return;
                }

                const filteredBoxes = window.allBoxes.filter(box => {
                    return searchInObject(box, searchTerm);
                });

                console.log("Résultats filtrés:", filteredBoxes.length);
                window.renderBoxesTable(filteredBoxes);
            } else {
                console.error("Variables ou fonctions nécessaires non disponibles pour les box");
            }
        });
    }

    // Parc informatique
    if (document.getElementById('searchEquipmentInput')) {
        const searchInput = document.getElementById('searchEquipmentInput');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            console.log("Recherche de:", searchTerm);

            if (typeof window.allEquipments !== 'undefined' && typeof window.renderEquipmentsTable === 'function') {
                if (searchTerm === '') {
                    window.renderEquipmentsTable(window.allEquipments);
                    return;
                }

                const filteredEquipments = window.allEquipments.filter(equipment => {
                    return searchInObject(equipment, searchTerm);
                });

                console.log("Résultats filtrés:", filteredEquipments.length);
                window.renderEquipmentsTable(filteredEquipments);
            } else {
                console.error("Variables ou fonctions nécessaires non disponibles pour le parc informatique");
            }
        });
    }
}

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM chargé, configuration de la recherche...");
    setupPageSearch();
});

// Configurer également après le chargement complet de la page
window.addEventListener('load', function() {
    console.log("Page entièrement chargée, configuration de la recherche...");
    setupPageSearch();
});
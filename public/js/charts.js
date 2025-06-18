/**
 * Fichier de gestion des graphiques pour les pages lignes téléphoniques, parc informatique et box
 */

// Fonction pour créer un camembert
function createPieChart(canvasId, labels, data, backgroundColor) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColor,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12
                }
            },
            title: {
                display: true,
                text: 'Répartition par type'
            }
        }
    });
}

// Fonction pour créer un histogramme horizontal
function createHorizontalBarChart(canvasId, labels, data, backgroundColor) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColor,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Distribution'
            },
            scales: {
                xAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
}

// Fonction pour générer des couleurs aléatoires
function generateColors(count) {
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push(`hsl(${Math.floor(Math.random() * 360)}, 70%, 60%)`);
    }
    return colors;
}

// Fonction pour initialiser les graphiques des lignes téléphoniques
function initPhoneLineCharts() {
    fetch('/api/phone-line/stats')
        .then(response => response.json())
        .then(data => {
            // Données pour le camembert (par type de ligne)
            const typeLabels = Object.keys(data.byLineType);
            const typeData = Object.values(data.byLineType);
            const typeColors = generateColors(typeLabels.length);

            // Données pour l'histogramme (par opérateur)
            const operatorLabels = Object.keys(data.byOperator);
            const operatorData = Object.values(data.byOperator);
            const operatorColors = generateColors(operatorLabels.length);

            // Créer les graphiques
            createPieChart('phone-line-type-chart', typeLabels, typeData, typeColors);
            createHorizontalBarChart('phone-line-operator-chart', operatorLabels, operatorData, operatorColors);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des statistiques des lignes téléphoniques:', error);
        });
}

// Fonction pour initialiser les graphiques du parc informatique
function initEquipmentCharts() {
    fetch('/equipment/stats')
        .then(response => response.json())
        .then(data => {
            // Données pour le camembert (par OS)
            const osLabels = Object.keys(data.byOs);
            const osData = Object.values(data.byOs);
            const osColors = generateColors(osLabels.length);

            // Données pour l'histogramme (par modèle)
            const modelLabels = Object.keys(data.byModel);
            const modelData = Object.values(data.byModel);
            const modelColors = generateColors(modelLabels.length);

            // Créer les graphiques
            createPieChart('equipment-os-chart', osLabels, osData, osColors);
            createHorizontalBarChart('equipment-model-chart', modelLabels, modelData, modelColors);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des statistiques du parc informatique:', error);
        });
}

// Fonction pour initialiser les graphiques des box
function initBoxCharts() {
    fetch('/api/box/stats')
        .then(response => response.json())
        .then(data => {
            // Données pour le camembert (par type)
            const typeLabels = Object.keys(data.byType);
            const typeData = Object.values(data.byType);
            const typeColors = generateColors(typeLabels.length);

            // Données pour l'histogramme (par commune)
            const communeLabels = Object.keys(data.byCommune);
            const communeData = Object.values(data.byCommune);
            const communeColors = generateColors(communeLabels.length);

            // Créer les graphiques
            createPieChart('box-type-chart', typeLabels, typeData, typeColors);
            createHorizontalBarChart('box-commune-chart', communeLabels, communeData, communeColors);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des statistiques des box:', error);
        });
}

// Initialiser les graphiques en fonction de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier quelle page est chargée
    if (document.querySelector('.lines-content')) {
        initPhoneLineCharts();
    } else if (document.querySelector('.park-content')) {
        initEquipmentCharts();
    } else if (document.querySelector('.box-list-content')) {
        initBoxCharts();
    }
});

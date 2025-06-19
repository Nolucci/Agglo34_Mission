// Fonction appelée lors du clic sur le bouton de modification
function editBox(boxId) {
    console.log("Modifier la boîte avec l'ID : " + boxId);

    // Récupérer les données de la boîte via AJAX
    fetch(`/api/box/${boxId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP ! statut : ${response.status}`);
            }
            return response.json();
        })
        .then(boxData => {
            // Remplir le formulaire du modal avec les données de la boîte
            document.getElementById('box-id').value = boxData.id;
            // Sélectionner l'option correcte dans le champ select de la commune
            const communeSelect = document.getElementById('box-commune');
            if (communeSelect) {
                communeSelect.value = boxData.commune; // boxData.commune contient maintenant l'ID de la municipalité
            }
            document.getElementById('box-service').value = boxData.service;
            document.getElementById('box-adresse').value = boxData.adresse;
            document.getElementById('box-ligne_support').value = boxData.ligne_support;
            document.getElementById('box-type').value = boxData.type;
            document.getElementById('box-attribueA').value = boxData.attribueA;

            // Sélectionner l'option correcte dans le champ select du statut
            const statutSelect = document.getElementById('box-statut');
            if (statutSelect) {
                statutSelect.value = boxData.statut;
            }

            // Changer le titre du modal pour "Modifier une Box"
            document.getElementById('boxModalLabel').innerText = 'Modifier une Box';

            // Ouvrir le modal
            $('#boxModal').modal('show');
        })
        .catch(error => {
            console.error("Erreur lors de la récupération des données de la box :", error);
            alert("Erreur lors de la récupération des données de la box.");
        });
}

// Initialisation des gestionnaires d'événements au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour le bouton "Enregistrer" du modal
    const saveBoxBtn = document.getElementById('save-box-btn');
    if (saveBoxBtn) {
        saveBoxBtn.addEventListener('click', function() {
            const boxId = document.getElementById('box-id').value;
            const commune = document.getElementById('box-commune').value;
            const service = document.getElementById('box-service').value;
            const adresse = document.getElementById('box-adresse').value;
            const ligneSupport = document.getElementById('box-ligne_support').value;
            const type = document.getElementById('box-type').value;
            const attribueA = document.getElementById('box-attribueA').value;
            const statut = document.getElementById('box-statut').value;

            // S'assurer que les noms des champs correspondent exactement à ceux attendus par le contrôleur
            const boxData = {
                commune: commune,
                service: service,
                adresse: adresse,
                ligne_support: ligneSupport, // Vérifier que ce nom correspond à celui attendu par le contrôleur
                type: type,
                attribueA: attribueA, // Vérifier que ce nom correspond à celui attendu par le contrôleur
                statut: statut
            };

            // Validation des champs obligatoires côté client
            if (!commune || commune.trim() === '') {
                alert("Erreur: La commune est obligatoire.");
                return;
            }
            if (!service || service.trim() === '') {
                alert("Erreur: Le service est obligatoire.");
                return;
            }
            if (!adresse || adresse.trim() === '') {
                alert("Erreur: L'adresse est obligatoire.");
                return;
            }

            console.log("Données de la boîte à enregistrer :", boxData);
            const jsonData = JSON.stringify(boxData);
            console.log("Données JSON à envoyer :", jsonData);

            // Déterminer si c'est une création ou une mise à jour
            // Si l'ID est vide ou non défini, c'est une création
            const isNewBox = !boxId || boxId.trim() === '';
            console.log("DEBUG: boxId =", boxId); // Ajout pour débogage
            console.log("DEBUG: isNewBox =", isNewBox); // Ajout pour débogage

            const modalTitle = document.getElementById('boxModalLabel').innerText;
            console.log("Titre du modal:", modalTitle);

            let url;
            let method;

            if (isNewBox) {
                // URL et méthode pour la création
                url = '/api/box';
                method = 'POST';
                console.log("Mode: Création");
            } else {
                // URL et méthode pour la mise à jour
                url = '/api/box/' + boxId;
                method = 'PUT';
                console.log("Mode: Mise à jour");
            }

            console.log("URL de l'API:", url);
            console.log("Méthode HTTP:", method);

            // Envoyer les données au serveur via AJAX
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: jsonData,
            })
            .then(response => {
                console.log("Statut de la réponse:", response.status, response.statusText);

                if (!response.ok) {
                    console.error("Erreur HTTP:", response.status, response.statusText);
                    return response.text().then(text => {
                        console.error("Réponse d'erreur:", text);
                        throw new Error(`Erreur HTTP ! statut : ${response.status}`);
                    });
                }

                return response.json().then(data => {
                    console.log("Réponse JSON complète:", data);
                    return data;
                });
            })
            .then(data => {
                console.log("Réponse du serveur :", data);
                if (data.success) {
                    alert("Box créée avec succès !");
                    // Fermer le modal après l'enregistrement réussi
                    $('#boxModal').modal('hide');
                    // Rediriger vers la première page pour voir la nouvelle box triée
                    window.location.href = window.location.pathname + '?page=1';
                } else {
                    alert("Erreur lors de la création de la box : " + data.error);
                }
            })
            .catch(error => {
                console.error("Erreur lors de l'envoi des données de la box :", error);
                alert("Erreur lors de l'enregistrement de la box.");
            });
        });
    }

    // Gestionnaire pour le bouton "Ajouter" qui ouvre le modal
    const addBoxBtn = document.querySelector('button[data-target="#boxModal"]');
    if (addBoxBtn) {
        addBoxBtn.addEventListener('click', function() {
            // Réinitialiser le formulaire
            document.getElementById('box-form').reset();
            // Vider l'ID caché
            document.getElementById('box-id').value = '';
            // Changer le titre du modal
            document.getElementById('boxModalLabel').innerText = 'Ajouter une Box';
        });
    }
});

// Fonction pour gérer la suppression d'une box (définie globalement)
// Exposer la fonction globalement pour qu'elle soit accessible depuis delete-confirmation.js
window.deleteBox = function(boxId) {
    console.log("Supprimer la boîte avec l'ID : " + boxId);

    fetch(`/api/box/delete/${boxId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP ! statut : ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Réponse du serveur (suppression) :", data);
        if (data.success) {
            // Fermer la modal de confirmation si elle est ouverte
            $('#staticModal').modal('hide');
            // Rediriger vers la première page
            window.location.href = window.location.pathname + '?page=1';
        } else {
            alert("Erreur lors de la suppression de la box : " + data.error);
        }
    })
    .catch(error => {
        console.error("Erreur lors de la suppression de la box :", error);
        alert("Erreur lors de la suppression de la box.");
    });
};

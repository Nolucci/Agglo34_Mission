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
            document.getElementById('box-localisation').value = boxData.localisation;
            document.getElementById('box-adresse').value = boxData.adresse;
            document.getElementById('box-ligne_support').value = boxData.ligne_support;
            document.getElementById('box-type').value = boxData.type;
            document.getElementById('box-name').value = boxData.name;
            document.getElementById('box-description').value = boxData.description;
            document.getElementById('box-brand').value = boxData.brand;
            document.getElementById('box-model').value = boxData.model;
            document.getElementById('box-assignedTo').value = boxData.assignedTo;
            document.getElementById('box-isActive').checked = boxData.isActive; // Pour la case à cocher

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

// Gérer le clic sur le bouton "Enregistrer" du modal
document.addEventListener('DOMContentLoaded', function() {
    const saveBoxBtn = document.getElementById('save-box-btn');
    if (saveBoxBtn) {
        saveBoxBtn.addEventListener('click', function() {
            const boxId = document.getElementById('box-id').value;
            const commune = document.getElementById('box-commune').value; // Récupère l'ID de la municipalité sélectionnée
            const localisation = document.getElementById('box-localisation').value;
            const adresse = document.getElementById('box-adresse').value;
            const ligneSupport = document.getElementById('box-ligne_support').value;
            const type = document.getElementById('box-type').value;
            const name = document.getElementById('box-name').value;
            const description = document.getElementById('box-description').value;
            const brand = document.getElementById('box-brand').value;
            const model = document.getElementById('box-model').value;
            const assignedTo = document.getElementById('box-assignedTo').value;
            const isActive = document.getElementById('box-isActive').checked; // Pour la case à cocher

            const boxData = {
                // Pas besoin d'envoyer l'ID dans le corps pour une requête PUT avec ID dans l'URL
                commune: commune, // C'est l'ID de la municipalité maintenant
                localisation: localisation,
                adresse: adresse,
                ligne_support: ligneSupport,
                type: type,
                name: name,
                description: description,
                brand: brand,
                model: model,
                assignedTo: assignedTo,
                isActive: isActive
            };

            console.log("Données de la boîte à enregistrer :", boxData);

            // Envoyer les données modifiées au serveur via AJAX (PUT request)
            fetch(`/api/box/update/${boxId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(boxData),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP ! statut : ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Réponse du serveur :", data);
                if (data.success) {
                    alert("Box mise à jour avec succès !");
                    // Fermer le modal après l'enregistrement réussi
                    $('#boxModal').modal('hide');
                    // Recharger la page ou mettre à jour la table si nécessaire
                    window.location.reload(); // Option simple pour recharger la page
                } else {
                    alert("Erreur lors de la mise à jour de la box : " + data.error);
                }
            })
            .catch(error => {
                console.error("Erreur lors de l'envoi des données de la box :", error);
                alert("Erreur lors de l'enregistrement de la box.");
            });
        });
    }
// Fonction pour gérer la suppression d'une box
function deleteBox(boxId) {
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
            alert("Box supprimée avec succès !");
            // Recharger la page ou mettre à jour la table si nécessaire
            window.location.reload(); // Option simple pour recharger la page
        } else {
            alert("Erreur lors de la suppression de la box : " + data.error);
        }
    })
    .catch(error => {
        console.error("Erreur lors de la suppression de la box :", error);
        alert("Erreur lors de la suppression de la box.");
    });
}
});
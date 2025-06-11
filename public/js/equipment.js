/**
 * Gestion des équipements du parc informatique
 */
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const equipmentForm = document.getElementById('equipment-form');
    const saveButton = document.getElementById('save-equipment');
    const equipmentIdInput = document.getElementById('equipment-id');

    // Gestionnaire pour le bouton d'enregistrement
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            saveEquipment();
        });
    }

    // Gestionnaire pour les boutons de suppression
    document.addEventListener('click', function(e) {
        if (e.target && e.target.closest('.delete-btn')) {
            const button = e.target.closest('.delete-btn');
            const itemId = button.dataset.id;
            const itemType = button.dataset.type;

            if (itemType === 'equipment') {
                // Le clic sur le bouton confirmer dans le modal est géré séparément
                document.querySelector('#staticModal .btn-primary').dataset.id = itemId;
                document.querySelector('#staticModal .btn-primary').dataset.type = itemType;
            }
        }
    });

    // Le gestionnaire pour le bouton de confirmation de suppression est déjà défini dans le template HTML
    // Nous n'avons pas besoin de le redéfinir ici pour éviter les conflits

    /**
     * Fonction pour sauvegarder un équipement (création ou mise à jour)
     */
    function saveEquipment() {
        if (!equipmentForm) return;

        // Validation du formulaire
        const requiredInputs = equipmentForm.querySelectorAll('[required]');
        let isValid = true;

        requiredInputs.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }

        // Récupération des données du formulaire
        const formData = {
            commune: document.getElementById('equipment-commune').value.trim() || null,
            etiquetage: document.getElementById('equipment-etiquetage').value.trim() || null,
            modele: document.getElementById('equipment-modele').value.trim() || null,
            numeroSerie: document.getElementById('equipment-numeroSerie').value.trim() || null,
            service: document.getElementById('equipment-service').value.trim() || null,
            utilisateur: document.getElementById('equipment-utilisateur').value.trim() || null,
            dateGarantie: document.getElementById('equipment-dateGarantie').value || null,
            os: document.getElementById('equipment-os').value.trim() || null,
            version: document.getElementById('equipment-version').value.trim() || null,
            statut: document.getElementById('equipment-statut').value,
            username: 'Utilisateur' // Idéalement, récupérer l'utilisateur connecté
        };

        // Vérifier que les valeurs ne sont pas nulles pour les champs requis
        const requiredFields = ['commune', 'modele', 'service'];
        for (const field of requiredFields) {
            if (formData[field] === null || formData[field] === undefined) {
                alert(`Le champ ${field} ne peut pas être vide.`);
                return;
            }
        }

        const equipmentId = equipmentIdInput.value;
        let url = '/equipment/create';
        let method = 'POST';

        // Si un ID est présent, c'est une mise à jour
        if (equipmentId) {
            url = `/equipment/update/${equipmentId}`;
            method = 'PUT';
        }

        console.log("Envoi des données d'équipement:", formData);
        console.log("URL:", url, "Méthode:", method);

        // Envoi de la requête
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                console.error("Erreur HTTP:", response.status, response.statusText);
                return response.text().then(text => {
                    console.error("Réponse d'erreur:", text);
                    throw new Error(`Erreur HTTP: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Fermer le modal
                $('#parkModal').modal('hide');

                // Recharger la page pour afficher les changements
                window.location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de l\'enregistrement');
        });
    }

    /**
     * Fonction pour supprimer un équipement
     * Exposée globalement pour être accessible depuis delete-confirmation.js
     */
    window.deleteEquipment = function(id) {
        console.log("Fonction deleteEquipment appelée avec ID:", id);

        // Vérifier que l'ID est valide
        if (!id) {
            console.error("ID d'équipement invalide:", id);
            alert("Erreur: ID d'équipement invalide");
            return;
        }

        console.log("Envoi de la requête de suppression à /equipment/delete/" + id);

        fetch(`/equipment/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                $('#staticModal').modal('hide');

                // Recharger la page pour afficher les changements
                window.location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la suppression');
        });
    }

    /**
     * Fonction pour éditer un équipement existant
     */
    window.editEquipment = function(id) {
        // Récupérer les données de l'équipement
        const equipment = allEquipments.find(e => e.id === parseInt(id));

        if (!equipment) {
            console.error('Équipement non trouvé:', id);
            return;
        }

        // Remplir le formulaire avec les données
        equipmentIdInput.value = equipment.id;
        document.getElementById('equipment-etiquetage').value = equipment.etiquetage || '';
        document.getElementById('equipment-modele').value = equipment.modele || '';
        document.getElementById('equipment-numeroSerie').value = equipment.numeroSerie || '';
        document.getElementById('equipment-service').value = equipment.service || '';
        document.getElementById('equipment-utilisateur').value = equipment.utilisateur || '';

        // Formater la date si elle existe
        if (equipment.dateGarantie) {
            const date = new Date(equipment.dateGarantie);
            const formattedDate = date.toISOString().split('T')[0]; // Format YYYY-MM-DD
            document.getElementById('equipment-dateGarantie').value = formattedDate;
        } else {
            document.getElementById('equipment-dateGarantie').value = '';
        }

        document.getElementById('equipment-os').value = equipment.os || '';
        document.getElementById('equipment-version').value = equipment.version || '';

        const communeSelect = document.getElementById('equipment-commune');
        if (communeSelect) {
            // Trouver l'option correspondante
            const options = Array.from(communeSelect.options);
            const option = options.find(opt => opt.value === (equipment.commune ? equipment.commune.id : null));

            if (option) {
                option.selected = true;
            } else if (equipment.commune) {
                // Si l'option n'existe pas mais qu'on a une valeur, utiliser l'ID de la commune
                console.error('Option de commune non trouvée pour ID:', equipment.commune.id);
                // Ne pas créer de nouvelle option, car nous avons besoin de l'ID correct
            }
        }

        const statutSelect = document.getElementById('equipment-statut');
        if (statutSelect) {
            statutSelect.value = equipment.statut || 'Actif';
        }

        // Changer le titre du modal
        document.getElementById('parkModalLabel').textContent = 'Modifier un Équipement Informatique';

        // Ouvrir le modal
        $('#parkModal').modal('show');
    };

    /**
     * Réinitialiser le formulaire lors de l'ouverture du modal pour ajouter
     */
    $('#parkModal').on('show.bs.modal', function(e) {
        // Si le modal est ouvert par un bouton "Ajouter" (pas par la fonction editEquipment)
        if (e.relatedTarget && e.relatedTarget.classList.contains('btn-success')) {
            equipmentForm.reset();
            equipmentIdInput.value = '';
            document.getElementById('parkModalLabel').textContent = 'Ajouter un Équipement Informatique';
        }
    });
});

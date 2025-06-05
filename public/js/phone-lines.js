/**
 * Gestion des lignes téléphoniques - CRUD
 */
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const lineForm = document.querySelector('#phone-line-form');
    const saveLineBtn = document.querySelector('#save-line-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn[data-type="phone_line"]');
    const municipalitySelect = document.getElementById('line-municipality');

    // Charger les municipalités dans le select si l'élément existe
    if (municipalitySelect) {
        loadMunicipalities();
    }

    // Gestionnaire d'événement pour le bouton Enregistrer
    if (saveLineBtn) {
        saveLineBtn.addEventListener('click', function() {
            saveLine();
        });
    }

    // Gestionnaire d'événement pour les boutons Supprimer
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const lineId = this.getAttribute('data-id');
                // Le clic sur le bouton ouvre déjà la modal de confirmation
                // On stocke l'ID pour la suppression
                document.getElementById('staticModal').setAttribute('data-line-id', lineId);
            });
        });
    }

    // Gestionnaire d'événement pour le bouton de confirmation de suppression
    const confirmDeleteBtn = document.querySelector('#confirm-delete-btn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            const lineId = document.getElementById('staticModal').getAttribute('data-line-id');
            if (lineId) {
                deleteLine(lineId);
            }
        });
    }

    /**
     * Charge la liste des municipalités pour le select
     */
    function loadMunicipalities() {
        fetch('/api/municipalities')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des municipalités');
                }
                return response.json();
            })
            .then(municipalities => {
                // Vider le select
                municipalitySelect.innerHTML = '';
                
                // Ajouter une option par défaut
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Sélectionnez une commune';
                municipalitySelect.appendChild(defaultOption);
                
                // Ajouter les municipalités
                municipalities.forEach(municipality => {
                    const option = document.createElement('option');
                    option.value = municipality.id;
                    option.textContent = municipality.name;
                    municipalitySelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Impossible de charger les municipalités. Veuillez réessayer plus tard.');
            });
    }

    /**
     * Enregistre une ligne téléphonique (création ou modification)
     */
    function saveLine() {
        // Récupérer les données du formulaire
        const formData = new FormData(lineForm);
        const lineData = {};
        
        // Convertir FormData en objet
        for (let [key, value] of formData.entries()) {
            if (key === 'isGlobal') {
                lineData[key] = true; // La case est cochée
            } else {
                // S'assurer que les valeurs ne sont pas vides
                lineData[key] = value.trim() === '' ? null : value.trim();
            }
        }
        
        // Si la case isGlobal n'est pas cochée, définir explicitement à false
        if (!formData.has('isGlobal')) {
            lineData['isGlobal'] = false;
        }
        
        // Vérifier les champs requis
        const requiredFields = ['location', 'service', 'assignedTo', 'operator', 'municipality'];
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!lineData[field] || lineData[field] === null) {
                isValid = false;
                const input = document.getElementById('line-' + field);
                if (input) {
                    input.classList.add('is-invalid');
                }
            }
        });
        
        if (!isValid) {
            alert('Veuillez remplir tous les champs obligatoires.');
            return;
        }
        
        // Déterminer s'il s'agit d'une création ou d'une modification
        const lineId = lineForm.getAttribute('data-id');
        const url = lineId
            ? `/api/phone-line/update/${lineId}`
            : '/api/phone-line/create';
        const method = lineId ? 'PUT' : 'POST';

        // Envoyer la requête
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(lineData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de l\'enregistrement de la ligne téléphonique');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Fermer la modal
                $('#linesModal').modal('hide');
                
                // Recharger la page pour afficher les modifications
                window.location.reload();
            } else {
                alert(data.error || 'Une erreur est survenue lors de l\'enregistrement.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Impossible d\'enregistrer la ligne téléphonique. Veuillez réessayer plus tard.');
        });
    }

    /**
     * Supprime une ligne téléphonique
     */
    function deleteLine(lineId) {
        fetch(`/api/phone-line/delete/${lineId}`, {
            method: 'DELETE'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la suppression de la ligne téléphonique');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Fermer la modal de confirmation
                $('#staticModal').modal('hide');
                
                // Recharger la page pour mettre à jour la liste
                window.location.reload();
            } else {
                alert(data.error || 'Une erreur est survenue lors de la suppression.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Impossible de supprimer la ligne téléphonique. Veuillez réessayer plus tard.');
        });
    }

    /**
     * Prépare le formulaire pour l'édition d'une ligne
     */
    function editLine(lineId) {
        // Récupérer les données de la ligne
        fetch(`/api/phone-line/${lineId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des données de la ligne');
                }
                return response.json();
            })
            .then(line => {
                // Remplir le formulaire avec les données
                document.getElementById('line-location').value = line.location;
                document.getElementById('line-service').value = line.service;
                document.getElementById('line-assignedTo').value = line.assignedTo;
                document.getElementById('line-phoneBrand').value = line.phoneBrand || '';
                document.getElementById('line-model').value = line.model || '';
                document.getElementById('line-operator').value = line.operator;
                document.getElementById('line-lineType').value = line.lineType || '';
                document.getElementById('line-municipality').value = line.municipality.id;
                document.getElementById('line-isGlobal').checked = line.isGlobal;
                
                // Définir l'ID de la ligne dans le formulaire
                lineForm.setAttribute('data-id', line.id);
                
                // Changer le titre de la modal
                document.getElementById('linesModalLabel').textContent = 'Modifier une Ligne Téléphonique';
                
                // Ouvrir la modal
                $('#linesModal').modal('show');
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Impossible de charger les données de la ligne. Veuillez réessayer plus tard.');
            });
    }

    // Exposer la fonction editLine globalement pour pouvoir l'appeler depuis les boutons d'édition
    window.editLine = editLine;

    // Réinitialiser le formulaire lors de l'ouverture de la modal pour une nouvelle ligne
    $('#linesModal').on('show.bs.modal', function (event) {
        // Si la modal est ouverte par un bouton d'ajout (pas d'édition)
        if (!event.relatedTarget || !event.relatedTarget.hasAttribute('data-id')) {
            lineForm.reset();
            lineForm.removeAttribute('data-id');
            document.getElementById('linesModalLabel').textContent = 'Ajouter une Ligne Téléphonique';
        }
    });
});
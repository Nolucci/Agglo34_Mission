/**
 * Gestion des lignes téléphoniques - CRUD
 */
let currentLineData = null; // Variable pour stocker les données de la ligne en cours d'édition

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
        return fetch('/api/municipalities')
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
            // Traitement spécial pour les checkboxes
            if (key === 'isWorking') {
                lineData[key] = true; // La case est cochée
            } else if (key !== 'phoneNumber' && value.trim() === '') { // Exclure phoneNumber ici
                // Pour les champs vides, envoyer null
                lineData[key] = null;
            } else if (key !== 'phoneNumber') { // Exclure phoneNumber ici
                // Pour les autres champs, envoyer la valeur nettoyée
                lineData[key] = value.trim();
            }
        }

        // Si la case isWorking n'est pas cochée, définir explicitement à false
        if (!formData.has('isWorking')) {
            lineData['isWorking'] = false;
        }

        // S'assurer que municipality est un nombre
        if (lineData['municipality']) {
            lineData['municipality'] = parseInt(lineData['municipality'], 10);
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
            } else {
                // Réinitialiser la classe d'erreur si le champ est valide
                const input = document.getElementById('line-' + field);
                if (input) {
                    input.classList.remove('is-invalid');
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
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
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
                        
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
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
                console.log('Données reçues de l\'API pour édition:', line);
                // Stocker les données de la ligne
                currentLineData = line;

                // Charger les municipalités et attendre que ce soit terminé
                return loadMunicipalities().then(() => line); // Retourne la ligne après chargement des municipalités
            })
            .then(line => {
                // Changer le titre de la modal
                document.getElementById('linesModalLabel').textContent = 'Modifier une Ligne Téléphonique';

                // Définir l'ID de la ligne dans le formulaire
                lineForm.setAttribute('data-id', line.id);

                // Remplir le formulaire avec les données stockées
                document.getElementById('line-location').value = line.location;
                document.getElementById('line-service').value = line.service;
                document.getElementById('line-assignedTo').value = line.assignedTo;
                document.getElementById('line-phoneBrand').value = line.phoneBrand || '';
                document.getElementById('line-model').value = line.model || '';
                document.getElementById('line-operator').value = line.operator;
                document.getElementById('line-lineType').value = line.lineType || '';
                document.getElementById('line-municipality').value = line.municipality.id;
                document.getElementById('line-directLine').value = line.directLine || '';
                document.getElementById('line-shortNumber').value = line.shortNumber || '';
                document.getElementById('line-isWorking').checked = line.isWorking;

                // Ouvrir la modal
                $('#linesModal').modal('show');
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des données de la ligne ou du chargement des municipalités:', error);
                            });
    }

    /**
     * Expose la fonction editLine globalement pour pouvoir l'appeler depuis les boutons d'édition
     */
    window.editLine = editLine;

    // Réinitialiser le formulaire lors de l'ouverture de la modal pour une nouvelle ligne
    $('#linesModal').on('show.bs.modal', function (event) {
        // Si currentLineData est null, c'est un ajout. Sinon, c'est une modification.
        if (currentLineData === null) {
            // Réinitialiser le formulaire pour un ajout
            lineForm.reset();
            lineForm.removeAttribute('data-id');
            document.getElementById('linesModalLabel').textContent = 'Ajouter une Ligne Téléphonique';
        } else {
            // Le formulaire est déjà rempli dans editLine pour une modification
            document.getElementById('linesModalLabel').textContent = 'Modifier une Ligne Téléphonique';
        }
    });

    // Réinitialiser currentLineData lors de la fermeture de la modal
    $('#linesModal').on('hidden.bs.modal', function (event) {
        currentLineData = null;
        // Réinitialiser également les classes de validation
        const invalidInputs = lineForm.querySelectorAll('.is-invalid');
        invalidInputs.forEach(input => {
            input.classList.remove('is-invalid');
        });
    });

    // Gestionnaire d'événement pour les clics sur les lignes du tableau
    const tableBody = document.querySelector('.table tbody');
    if (tableBody) {
        tableBody.addEventListener('click', function(event) {
            const clickedRow = event.target.closest('tr');
            if (clickedRow) {
                // Find the edit button within the clicked row to get the data-id
                const editButton = clickedRow.querySelector('.edit-btn');
                if (editButton) {
                    const lineId = editButton.getAttribute('data-id');
                    if (lineId) {
                        event.stopPropagation(); // Stop event propagation
                        editLine(lineId);
                    }
                }
            }
        });
    }
});
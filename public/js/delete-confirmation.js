/**
 * Script pour gérer la confirmation de suppression
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("Script de confirmation de suppression chargé");
    console.log("Modal staticModal existe:", !!document.getElementById('staticModal'));

    // Gestionnaire pour le bouton de confirmation de suppression
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    if (confirmDeleteBtn) {
        console.log("Bouton de confirmation trouvé:", confirmDeleteBtn);

        confirmDeleteBtn.addEventListener('click', function() {
            console.log("Clic sur le bouton de confirmation");

            // Récupérer les données du modal
            const modal = document.getElementById('staticModal');
            console.log("Modal dans le gestionnaire de confirmation:", modal);

            let itemId = modal ? modal.getAttribute('data-item-id') : null;
            let itemType = modal ? modal.getAttribute('data-item-type') : null;
            console.log("Attributs initiaux:", { itemId, itemType });

            // Vérifier également l'attribut data-line-id utilisé par phone-lines.js
            const lineId = modal ? modal.getAttribute('data-line-id') : null;
            console.log("Attribut data-line-id:", lineId);

            if (lineId && (!itemId || !itemType)) {
                console.log("ID de ligne trouvé dans data-line-id:", lineId);
                itemId = lineId;
                itemType = 'phone_line';
            }

            console.log("Données finales récupérées:", { itemId, itemType });

            if (itemType === 'phone_line') {
                console.log("Appel de deleteLine avec ID:", itemId);
                // Vérifier si la fonction est disponible dans le contexte global
                if (typeof window.deleteLine === 'function') {
                    window.deleteLine(itemId);
                } else {
                    console.error("La fonction deleteLine n'est pas disponible. Envoi direct de la requête de suppression.");
                    // Fallback: envoyer directement la requête
                    fetch(`/api/phone-line/delete/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        console.log('Réponse reçue:', response.status);
                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Données reçues après suppression:', data);
                        if (data.success) {
                            // Fermer le modal en utilisant jQuery (méthode la plus compatible)
                            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                $('#staticModal').modal('hide');
                            }
                            // Recharger la page
                            window.location.reload();
                        } else {
                            alert('Erreur: ' + (data.error || 'Une erreur est survenue lors de la suppression'));
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Impossible de supprimer la ligne téléphonique. Veuillez réessayer plus tard.');
                    });
                }
            } else if (itemType === 'equipment') {
                console.log("Appel de deleteEquipment avec ID:", itemId);
                // Vérifier si la fonction est disponible dans le contexte global
                if (typeof window.deleteEquipment === 'function') {
                    window.deleteEquipment(itemId);
                } else {
                    console.error("La fonction deleteEquipment n'est pas disponible. Envoi direct de la requête de suppression.");
                    // Fallback: envoyer directement la requête
                    fetch(`/equipment/delete/${itemId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Équipement supprimé avec succès");
                            // Fermer le modal en utilisant jQuery (méthode la plus compatible)
                            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                $('#staticModal').modal('hide');
                            }
                            // Recharger la page
                            window.location.reload();
                        } else {
                            alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de la suppression'));
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue lors de la suppression');
                    });
                }
            }
            else if (itemType === 'box') {
                console.log("Appel de deleteBox avec ID:", itemId);
                // Vérifier si la fonction est disponible dans le contexte global
                if (typeof window.deleteBox === 'function') {
                    window.deleteBox(itemId);
                } else {
                    console.error("La fonction deleteBox n'est pas disponible. Envoi direct de la requête de suppression.");
                    // Fallback: envoyer directement la requête
                    fetch(`/api/box/delete/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Box supprimée avec succès");
                            // Fermer le modal en utilisant jQuery (méthode la plus compatible)
                            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                $('#staticModal').modal('hide');
                            }
                            // Recharger la page
                            window.location.reload();
                        } else {
                            alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de la suppression'));
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue lors de la suppression');
                    });
                }
            } else {
                console.log("Type d'élément non reconnu:", itemType);
            }
        });
    } else {
        console.error("Bouton de confirmation non trouvé avec ID 'confirm-delete-btn'");
    }

    // Nous utilisons les attributs data-toggle="modal" et data-target="#staticModal" sur les boutons
    // pour ouvrir le modal, donc nous n'avons pas besoin de gestionnaire d'événements pour cela.
    // Bootstrap s'en charge automatiquement.

    // Cependant, nous devons quand même stocker les données dans le modal lorsqu'il est ouvert
    $('#staticModal').on('show.bs.modal', function (event) {
        console.log("Événement show.bs.modal déclenché");

        // Récupérer le bouton qui a déclenché le modal
        const button = $(event.relatedTarget);
        console.log("Bouton qui a déclenché le modal:", button);

        if (button.length) {
            const itemId = button.data('id');
            const itemType = button.data('type');

            console.log("Données du bouton:", { itemId, itemType });

            // Stocker les données dans le modal
            const modal = $(this);
            modal.data('item-id', itemId);
            modal.data('item-type', itemType);
            modal.attr('data-item-id', itemId);
            modal.attr('data-item-type', itemType);

            // Pour les lignes téléphoniques, stocker également dans data-line-id
            if (itemType === 'phone_line') {
                modal.data('line-id', itemId);
                modal.attr('data-line-id', itemId);
            }

            console.log("Attributs définis sur le modal:", {
                'data-item-id': modal.attr('data-item-id'),
                'data-item-type': modal.attr('data-item-type'),
                'data-line-id': modal.attr('data-line-id')
            });

            // Mettre à jour le texte du modal
            const title = modal.find('.modal-title');
            const body = modal.find('.modal-body p');

            if (title.length) {
                const newTitle = 'Confirmer la suppression de ' +
                    (itemType === 'equipment' ? 'l\'équipement' :
                     itemType === 'box' ? 'la box' :
                     itemType === 'phone_line' ? 'la ligne téléphonique' : 'l\'élément');
                title.text(newTitle);
                console.log("Titre du modal mis à jour:", newTitle);
            }

            if (body.length) {
                const newBody = 'Voulez-vous vraiment supprimer ' +
                    (itemType === 'equipment' ? 'cet équipement' :
                     itemType === 'box' ? 'cette box' :
                     itemType === 'phone_line' ? 'cette ligne téléphonique' : 'cet élément') + ' ?';
                body.text(newBody);
                console.log("Corps du modal mis à jour:", newBody);
            }
        } else {
            console.error("Bouton qui a déclenché le modal non trouvé");
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const dropArea = document.getElementById("drop-area");
    const fileInput = document.getElementById("file-input");
    const fileList = document.getElementById("file-list");
    const fileInfoTable = document.getElementById("file-info-table");
    let fileInfoTableBody = null;
    const uploadButton = document.getElementById("upload-button");

    //Verify modal is valid
    const modal = document.getElementById("staticModal");
    let modalTitle = null;
    let modalBody = null;

    if(modal) {
        modalTitle = modal.querySelector(".modal-title");
        modalBody = modal.querySelector(".modal-body");
    }

    //Verify that fileInfoTable is found first
    if (fileInfoTable) {
        fileInfoTableBody = fileInfoTable.querySelector("tbody");
    }
    else
        console.error("Table with file-info-table id wasn't found");

    // Tableau pour stocker les fichiers sélectionnés
    let selectedFiles = [];

    // Empêcher le comportement par défaut du navigateur pour le drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Mettre en évidence la zone de dépôt
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropArea.classList.add('highlight');
    }

    function unhighlight(e) {
        dropArea.classList.remove('highlight');
    }

    // Gérer les fichiers déposés
    dropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        processFiles(files);
    }

    // Gérer le click sur la zone de dépôt
    dropArea.addEventListener('click', openFileSelector);

    function openFileSelector(){
        dropArea.removeEventListener('click', openFileSelector); // bloque les clics multiples
        fileInput.click();
        setTimeout(() => {
            dropArea.addEventListener('click', openFileSelector); // réactive après un petit délai
        }, 500);
    }

    //Attach an event that processes files
    fileInput.addEventListener('change', () => {
        processFiles(fileInput.files);
    });

    // Traiter les fichiers et afficher une prévisualisation
    function processFiles(files) {
        // Convertir FileList en Array
        const filesArray = Array.from(files);

        filesArray.forEach(file => {
            const fileName = file.name.toLowerCase();
            if (fileName.endsWith('.csv') || fileName.endsWith('.xlsx')) {
                console.log(`Fichier accepté: ${file.name} (${file.type})`);
                // Ajouter un attribut pour identifier le type de fichier
                file.isCSV = fileName.endsWith('.csv');
                file.isXLSX = fileName.endsWith('.xlsx');
                selectedFiles.push(file);
            } else {
                console.log(`Fichier rejeté: ${file.name} (${file.type})`);
                //Show alert
                showModal("Error", "Seuls les fichiers CSV et XLSX sont autorisés.");
            }
        });

        // Afficher la prévisualisation des fichiers
        displayFilePreview();

        // Afficher le bouton d'uploads si des fichiers sont sélectionnés
        uploadButton.style.display = selectedFiles.length > 0 ? 'block' : 'none';
    }

    // Function to show the modal
    function showModal(title, message) {

        if(modal && modalTitle && modalBody) {
            modalTitle.textContent = title;
            modalBody.textContent = message;

            //Show Modal use jQuery if bootstrap version is < 5.0
            $(modal).modal('show');

            // Ajouter un gestionnaire d'événements pour le bouton Confirmer
            const confirmButton = modal.querySelector('.btn-primary');
            if (confirmButton) {
                // Supprimer les gestionnaires d'événements existants pour éviter les doublons
                confirmButton.replaceWith(confirmButton.cloneNode(true));
                const newConfirmButton = modal.querySelector('.btn-primary');

                newConfirmButton.addEventListener('click', function() {
                    $(modal).modal('hide');
                });
            }
        }
        else
            console.error("Modal with id staticModal wasn't found");
    }

    // Function to hide the modal (not needed for static modal)
    // Afficher une prévisualisation des fichiers
    function displayFilePreview() {
        // Vider la liste de fichiers
        fileList.innerHTML = '';

        if(fileInfoTable && fileInfoTableBody)
        {
            fileInfoTableBody.innerHTML = '';
        }
        else {
            console.error("fileInfoTableBody wasn't found so preview cannot be displayed");
            return;
        }


        // S'il n'y a pas de fichiers, cacher le tableau
        if (selectedFiles.length === 0) {
            fileInfoTable.style.display = 'none';
            return;
        }

        // Afficher le tableau
        fileInfoTable.style.display = 'table';

        // Parcourir chaque fichier et ajouter les informations
        selectedFiles.forEach((file, index) => {
            // Créer une nouvelle ligne dans le tableau
            const row = fileInfoTableBody.insertRow();

            // Ajouter des cellules pour chaque information
            const nameCell = row.insertCell();
            const sizeCell = row.insertCell();
            const typeCell = row.insertCell();
            const lastModifiedCell = row.insertCell();
            const actionCell = row.insertCell();

            // Formater la date de dernière modification
            const lastModified = new Date(file.lastModified).toLocaleString();

            // Remplir les cellules avec les informations du fichier
            nameCell.textContent = file.name;
            sizeCell.textContent = formatBytes(file.size);
            typeCell.textContent = file.type || 'Non spécifié';
            lastModifiedCell.textContent = lastModified;

            // Ajouter un bouton de suppression
            const deleteButton = document.createElement('button');
            deleteButton.textContent = 'Supprimer';
            deleteButton.className = 'btn btn-sm btn-danger';
            deleteButton.addEventListener('click', () => {
                removeFile(index);
            });
            actionCell.appendChild(deleteButton);

        });
    }

    // Supprimer un fichier de la liste
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        displayFilePreview();
        uploadButton.style.display = selectedFiles.length > 0 ? 'block' : 'none';
    }

    // Envoyer les fichiers au serveur
    uploadButton.addEventListener('click', uploadFiles);

    function uploadFiles() {
        if (selectedFiles.length === 0) {
            showModal("Erreur", "Aucun fichier sélectionné pour le téléchargement.");
            return;
        }

        // Variable pour suivre les requêtes en cours
        let pendingRequests = 0;
        let successCount = 0;
        let errorCount = 0;
        let allMessages = [];
        let currentSessionId = null;

        // Créer et afficher la barre de progression
        const progressContainer = createProgressBar();
        document.body.appendChild(progressContainer);

        // Fonction pour mettre à jour la barre de progression
        function updateProgress(sessionId) {
            if (!sessionId) return;

            fetch(`/equipment/import-progress?sessionId=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    const progressBar = document.getElementById('import-progress-bar');
                    const progressText = document.getElementById('import-progress-text');

                    if (progressBar && progressText) {
                        const percentage = data.total > 0 ? Math.round((data.current / data.total) * 100) : 0;
                        progressBar.style.width = percentage + '%';
                        progressText.textContent = `${data.message} (${data.current}/${data.total})`;

                        if (data.status === 'completed' || data.status === 'error') {
                            setTimeout(() => {
                                document.body.removeChild(progressContainer);
                            }, 2000);
                        } else {
                            setTimeout(() => updateProgress(sessionId), 500);
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération de la progression:', error);
                });
        }

        // Fonction pour finaliser après toutes les requêtes
        function finalize() {
            if (pendingRequests === 0) {
                // Toutes les requêtes sont terminées
                let finalMessage = "";

                if (successCount > 0) {
                    finalMessage += `${successCount} fichier(s) traité(s) avec succès. `;
                }

                if (errorCount > 0) {
                    finalMessage += `${errorCount} fichier(s) ont rencontré des erreurs. `;
                }

                if (allMessages.length > 0) {
                    finalMessage += "\n\nDétails:\n" + allMessages.join("\n");
                }

                if (finalMessage) {
                    showModalWithoutConfirm(successCount > 0 ? "Traitement terminé" : "Erreur", finalMessage);
                }

                // Réinitialiser la liste des fichiers
                selectedFiles = [];
                displayFilePreview();
                uploadButton.style.display = 'none';
            }
        }

        // Traiter tous les fichiers sélectionnés
        selectedFiles.forEach(file => {
            pendingRequests++;

            // Déterminer l'URL en fonction du type de fichier
            let url = '/uploads';
            if (file.isCSV || file.isXLSX) {
                url = '/equipment/import-csv';
            }

            const formData = new FormData();
            formData.append('file', file);
            console.log(`Ajout du fichier à la requête: ${file.name}, taille: ${file.size}, URL: ${url}`);

            console.log(`Envoi du fichier ${file.name} à ${url}`);
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log(`Réponse du serveur pour ${file.name}: status ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log(`Données reçues du serveur pour ${file.name}:`, data);

                // Démarrer le suivi de progression si on a un sessionId
                if (data.sessionId && !currentSessionId) {
                    currentSessionId = data.sessionId;
                    updateProgress(currentSessionId);
                }

                if (data.status === 'success') {
                    successCount++;
                    let message = `Fichier ${file.name} traité avec succès.`;
                    if (data.totalImported) {
                        message += ` ${data.totalImported} équipements importés.`;
                    }
                    if (data.totalSkipped) {
                        message += ` ${data.totalSkipped} lignes ignorées (doublons).`;
                    }
                    if (data.skippedLines && data.skippedLines.length > 0) {
                        message += `\nLignes non importées:\n${data.skippedLines.join('\n')}`;
                    }
                    allMessages.push(message);
                    console.log(`Succès: ${file.name} traité`);
                } else {
                    errorCount++;
                    allMessages.push(`Erreur pour ${file.name}: ${data.message || "Erreur inconnue."}`);
                    console.error(`Erreur: ${file.name} - ${data.message || "Erreur inconnue."}`);
                }
            })
            .catch(error => {
                errorCount++;
                console.error(`Erreur lors de l'envoi du fichier ${file.name}:`, error);
                allMessages.push(`Erreur réseau ou serveur lors de l'envoi de ${file.name}.`);
            })
            .finally(() => {
                pendingRequests--;
                finalize();
            });
        });
    }

    // Fonction pour créer la barre de progression
    function createProgressBar() {
        const container = document.createElement('div');
        container.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            min-width: 400px;
        `;

        container.innerHTML = `
            <h4>Import en cours...</h4>
            <div style="background: #f0f0f0; border-radius: 4px; overflow: hidden; margin: 10px 0;">
                <div id="import-progress-bar" style="background: #007bff; height: 20px; width: 0%; transition: width 0.3s;"></div>
            </div>
            <div id="import-progress-text">Initialisation...</div>
        `;

        return container;
    }

    // Fonction pour afficher le modal sans bouton confirmer
    function showModalWithoutConfirm(title, message) {
        if(modal && modalTitle && modalBody) {
            modalTitle.textContent = title;
            modalBody.textContent = message;

            // Cacher le bouton confirmer
            const confirmButton = modal.querySelector('.btn-primary');
            if (confirmButton) {
                confirmButton.style.display = 'none';
            }

            //Show Modal use jQuery if bootstrap version is < 5.0
            $(modal).modal('show');

            // Auto-fermer le modal après 3 secondes
            setTimeout(() => {
                $(modal).modal('hide');
                // Remettre le bouton confirmer visible pour les autres usages
                if (confirmButton) {
                    confirmButton.style.display = 'block';
                }
            }, 3000);
        }
        else
            console.error("Modal with id staticModal wasn't found");
    }

    // Formater la taille des fichiers
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
});
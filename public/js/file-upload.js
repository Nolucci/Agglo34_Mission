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
            if (file.name.endsWith('.csv') || file.name.endsWith('.xlsx')) {
                selectedFiles.push(file);
            } else {
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
            alert("Aucun fichier sélectionné pour le téléchargement.");
            return;
        }

        selectedFiles.forEach(file => {
            const formData = new FormData();
            formData.append('file', file);

            fetch('/uploads', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log(`Fichier ${data.name} uploadé avec succès à ${data.path}`);
                        // Tu peux ici afficher un message à l'utilisateur, ou ajouter une ligne dans ton tableau
                    } else {
                        showModal("Erreur", data.message || "Erreur inconnue lors de l'envoi.");
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de l’envoi du fichier :', error);
                    showModal("Erreur", "Erreur réseau ou serveur lors de l'envoi.");
                });
        });
        selectedFiles = [];
        displayFilePreview();
        uploadButton.style.display = 'none';
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
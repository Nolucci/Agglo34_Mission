$(document).ready(function () {
    const table = $('#phoneTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Exporter CSV',
                className: 'au-btn au-btn--blue au-btn--small'
            },
            {
                extend: 'excelHtml5',
                text: 'Exporter Excel',
                className: 'au-btn au-btn--blue au-btn--small'
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
        },
        paging: true,
        ordering: true,
        info: true
    });

    // Filtrage par commune
    $('#municipalityFilter').on('change', function () {
        const value = $(this).val();
        table.column(1).search(value).draw(); // Colonne 1 = nom commune
    });

    // Redirection sur clic de ligne
    $('#phoneTable tbody').on('click', 'tr.clickable-row', function (e) {
        // Ignore si clic sur un bouton dans la cellule
        if (!$(e.target).closest('button').length) {
            const href = $(this).data('href');
            if (href) {
                window.location.href = href;
            }
        }
    });

    // Redirection via bouton Modifier
    //$('.edit-btn').on('click', function (e) {
    //    e.stopPropagation(); // Empêche le clic ligne
    //    const id = $(this).data('id');
    //    window.location.href = '/phone-line/' + id + '/edit';
    //});

    // Exemple action bouton Supprimer (à personnaliser)
    $('.delete-btn').on('click', function (e) {
        e.stopPropagation();
        const id = $(this).data('id');
        if (confirm('Supprimer la ligne téléphonique #' + id + ' ?')) {
            // Appel Ajax ou redirection backend à implémenter
            alert('Suppression fictive de la ligne ' + id);
        }
    });
});

$(document).ready(function() { // table projets par année
    $('#table_projets_annee').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 1,3 ]}]
    });
});

$(document).ready(function() { // table projets par année
    $('#table_projets_data').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 2,3 ]}]
    });
});

$(document).ready(function() { // table projets par session
    $('#table_projets_session').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "columnDefs":[{ type: "num-fmt", targets: [13,14] }],
        "aoColumnDefs": [{bSortable: false,aTargets: [ 1,4,6 ]}]
    });
});

$(document).ready(function() { // table tous les projets
    var dt = $('#projets_tous').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 2 ]}]
    });
    // (ne marche pas) dt.fnSort([1,'asc']);
});

$(document).ready(function() { // table utilisateurs
    $('#utilisateurs').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [0,1,2]}]
    });
});

$(document).ready(function() { // table projet
    $('#projet').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [0,1,2,6]}]
    });
});

$(document).ready(function() { // table projets par session
    $('#bilan_session').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 1 ]}]
    });
});

$(document).ready(function() { // table anciennes expertises
    $('#old_expertises').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 2,4 ]}]
    });
});

/*$(document).ready(function() { // table d'affectation des experts
    $('#affecte_experts').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [4]}]
    });
});*/

$(document).ready(function() { // table statistiques laboratoires
    $('#tab_statistiques_labo').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        //~ "aoColumnDefs": [{bSortable: false,aTargets: [ 1, 3, 5 ]}]
    });
});

$(document).ready(function() { // table toutes les publis
    $('#table_publis').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 1,2 ]}]
    });
});

$(document).ready(function() { // table publis par projet
    $('#publis_projet').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false
    });
});

 $(document).ready(function() { // table general
    $('#general').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 0 ]}]
    });
});

$(document).ready(function() { // table laboratoires
    $('#laboratoires').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 1 ]}]
    });
});

$(document).ready(function() { // table laboratoires
    $('#etablissement').DataTable( {
        "bPaginate": false,
        "bFilter":     false,
        "info":         false,
        "aoColumnDefs": [{bSortable: false,aTargets: [ 2 ]}]
    });
});


$(document).ready(function() {
    $('#datatable').DataTable({
        initComplete: function () {
            this.api()
                .columns()
                .every(function () {
                    let column = this;
                    let title = column.footer().textContent;
                    // Create input element
                    let input = document.createElement('input');
                    input.placeholder = title;
                    input.attributes['size'] = input.placeholder.length;
                    let taille = input.placeholder.length*20-input.placeholder.length*7;
                    if (input.placeholder.length>20){
                        taille = input.placeholder.length*20-input.placeholder.length*12;
                    }
                    input.style.width = taille.toString() + 'px';
                    if (title !=='\xa0'){
                        column.footer().replaceChildren(input);
                    }
                    $('#datatable tfoot tr').appendTo('#datatable thead');
                    // Event listener for user input
                    input.addEventListener('keyup', () => {
                        if (column.search() !== this.value) {
                            column.search(input.value).draw();
                        }
                    });
                });
        }
    })
});





// $(document).ready(function() {
//     $('#example').DataTable({
//         initComplete: function () {
//         this.api()
//             .columns()
//             .eq(0)
//             .each(function(colIdx) {
//                 let column = this;
//                 $('#example thead th').each(function() {
//                     var title = $('#example thead th').eq($(this).index()).text();
//                     $(this).html('&lt;input type=&quot;text&quot; placeholder=&quot;Search ' + title + '&quot; /&gt;');
//                     console.log('bhsfdhu')
//                 });
//
//             $('input', column.column(colIdx).header()).on('keyup change', function() {
//                 column
//                     .column(colIdx)
//                     .search(this.value)
//                     .draw();
//             });
//
//             $('input', column.column(colIdx).header()).on('click', function(e) {
//                 e.stopPropagation();
//             });
//         });
//     }
//     }
//     );
// })



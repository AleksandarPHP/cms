console.log('© Copyright' + '%c symple.ch CMS PRO', 'font-weight: bold', '- All rights reserved.');

// tooltip init
$(function() {
    $('[data-toggle="tooltip"]').tooltip()
})

// menu dropdown
$('.dropdown-btn').click(function(e) {
    e.preventDefault();
    $(this).siblings('.dropdown-ul').toggle(300);
});

// menu mobile
$('.mobile-btn').click(function(e) {
    e.preventDefault();
    $(".menu").toggleClass('active');
});
$('.mobile-close-btn').click(function(e) {
    e.preventDefault();
    $(".menu").toggleClass('active');
});

// alert btn
$('.alert__btn').click(function(e) {
    e.preventDefault();
    $(this).parent(".alert").hide(100);
});

// input add file
$('.inputfile').on("change", function() {
    if (this.value.length > 0) {
        $(this).siblings(".add-file").find(".add-file-title").html(this.value.replace("C:\\fakepath\\", ""));
    } else {
        $(this).siblings(".add-file").find(".add-file-title").html("No file selected");
    }
});
// input add file
$('.multiplefile').on("change", function() {
    if (this.value.length > 0) {
        if (this.files.length == 1) {
            $(this).siblings(".add-file").find(".add-file-title").html(this.files.length + " file selected");
        } else {
            $(this).siblings(".add-file").find(".add-file-title").html(this.files.length + " files selected");
        }

    } else {
        $(this).siblings(".add-file").find(".add-file-title").html("No files selected");
    }
});

// date pickers
$(function() {
    $('#datetimepickerAll').datetimepicker({
        locale: 'de-ch'
    });
});
$(function() {
    $('#datetimepickerDate').datetimepicker({
        locale: 'de-ch',
        format: 'DD.MM.YYYY'
    });
});

// table
$(document).ready(function() {
    $('#example').DataTable({
        "iDisplayLength": 25,
        "language": {
            "sEmptyTable": "Keine Daten in der Tabelle vorhanden",
            "sInfo": "_START_ bis _END_ von _TOTAL_ Einträgen",
            "sInfoEmpty": "0 bis 0 von 0 Einträgen",
            "sInfoFiltered": "(gefiltert von _MAX_ Einträgen)",
            "sInfoPostFix": "",
            "sInfoThousands": ".",
            "sLengthMenu": "_MENU_ Einträge anzeigen",
            "sLoadingRecords": "Wird geladen...",
            "sProcessing": "Bitte warten...",
            "sSearch": "Suchen",
            "sZeroRecords": "Keine Einträge vorhanden.",
            "oPaginate": {
                "sFirst": "Erste",
                "sPrevious": "Zurück",
                "sNext": "Nächste",
                "sLast": "Letzte"
            },
            select: {
                rows: {
                    _: '%d Zeilen ausgewählt',
                    0: 'Zum Auswählen auf eine Zeile klicken',
                    1: '1 Zeile ausgewählt'
                }
            }
        }
    });
    $('#users').DataTable({
        "order": [
            [0, "asc"]
        ],
        "paging": false,
        "searching": false,
        "info": false
    });
});

// sortable
$("#sortable").sortable().bind('sortupdate', function() {
    var x = '';
    $('#sortable li').each(function() {
        if (x != '') x = x + '|';
        x = x + $(this).attr('alt');
    });
    $('#sortarray').val(x);
});

function readURL(input, id) {
    const validImageTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/x-icon'];
    if (input.files && input.files[0] && validImageTypes.includes(input.files[0]['type'])) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $(id).attr('src', e.target.result);
            $(id).parent().attr('href', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        input.value = '';
    }
}
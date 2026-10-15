@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    <div class="row main__title">
        <div class="col-sm-8">
            <h2><i class="fas fa-list"></i>{{ $title }}</h2>
            <p>{{ __('app.list') }}</p>
        </div>
        <div class="col-sm-4 shortcuts">
            <a
                href="{{ url('cms') }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.daschlow') }}"
            >
                <i class="fas fa-desktop"></i>
            </a>
            <a
                href="{{ url('cms/'.$route) }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.list') }}"
            >
                <i class="far fa-clone"></i>
            </a>
            <a
                href="{{ url('cms/'.$route.'/create') }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.add') }}"
            >
                <i class="fas fa-plus"></i>
            </a>
            <a
                href="https://symple.kayako.com/"
                target="_blank"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="Support"
            >
                <i class="fas fa-question"></i>
            </a>
        </div>
    </div>
    @include('cms.partials.alerts')
    <div class="row main__buttons">
        <div class="col-md-12">
            <a href="{{ url('cms/'.$route.'/create') }}" class="button button-white"><i class="far fa-plus-square"></i>{{ __('app.add') }}</a>
        </div>
    </div>
    <div class="main__list">
        <div class="row form__title">
            <h3>{{ $title }}</h3>
        </div>
        <div class="row default__table">
            <div class="table-responsive">
                    <table class="display" id="dataTableSSR" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('app.username') }}</th>
                            <th class="nosort">{{ __('app.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('app.username') }}</th>
                            <th class="nosort">{{ __('app.action') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script type="text/javascript">
$(function() {
   $(document).on('click', '.confirmation', function(e) {
       e.preventDefault();
       if (confirm("{{ __('app.deletequest') }}")) $('#delete-form'+$(this).attr('data-id')).submit();
   });
});
var gif = "{{ asset('cmsfiles/images/5.gif') }}";
var imgon = "{{ asset('cmsfiles/images/btn_on.jpg') }}";
var imgoff = "{{ asset('cmsfiles/images/btn_off.jpg') }}";
$(document).on("click", ".status", function(event){
      event.preventDefault();
          $.ajaxSetup({
              headers: {
                'X-CSRF-TOKEN': '@csrf'
              }
            });
        var id = $(this).attr('data-id');
        $(this).empty().append('<img class="img-'+id+'" src="'+gif+'">');
        $.ajax({
          url:"{{ url('cms/'.$route) }}/status/"+id,
          type:"get",
          data: {'id':id},
          success:function(response){
            if(response[1]) {
                if(response[0]) {
                    $('#status-'+id).html('<img class="img-'+id+'" src="'+imgon+'">');
                } else {
                    $('#status-'+id).html('<img class="img-'+id+'" src="'+imgoff+'">');
                }
            } else {
                alert("{{ __('app.error') }}");
            }
          },
      })
});

$(document).ready(function() {
    $('#dataTableSSR').DataTable({
    'aoColumnDefs': [{
        'bSortable': false,
        'aTargets': ['nosort']
    }],
    'language': {
        "sEmptyTable":     "{!! __('app.sEmptyTable') !!}",
        "sInfo":           "{!! __('app.sInfo') !!}",
        "sInfoEmpty":      "{!! __('app.sInfoEmpty') !!}",
        "sInfoFiltered":   "{!! __('app.sInfoFiltered') !!}",
        "sInfoPostFix":    "",
        "sInfoThousands":  ".",
        "sLengthMenu":     "{!! __('app.sLengthMenu') !!}",
        "sLoadingRecords": "{!! __('app.sLoadingRecords') !!}",
        "sProcessing":     "{!! __('app.sProcessing') !!}",
        "sSearch":         "{!! __('app.sSearch') !!}",
        "sZeroRecords":    "{!! __('app.sZeroRecords') !!}",
        "oPaginate": {
            "sFirst":    "{!! __('app.sFirst') !!}",
            "sLast":     "{!! __('app.sLast') !!}",
            "sNext":     "{!! __('app.sNext') !!}",
            "sPrevious": "{!! __('app.sPrevious') !!}"
        },
        "oAria": {
            "sSortAscending":  ": {!! __('app.sSortAscending') !!}",
            "sSortDescending": ": {!! __('app.sSortDescending') !!}"
        }
    },
    "bProcessing": true,
    "serverSide": true,
    "ajax": {
        url: "{{ url('cms/'.$route.'/ajax') }}",
        type: "post",
        headers: {
            'X-CSRF-Token': "{{ csrf_token() }}"
        },
        error: function(){
            $("#dataTableSSR_processing").css("display","none");
            alert("{{ __('app.errorloading') }}")
        }
    },
    "createdRow": function (row, data, index) {
        $(row).addClass("text-center");
        $(row).find('td:eq(2)').addClass("td_action");
    },
    "order": [[ 0, "desc"]],
  });
});
</script>
@endsection
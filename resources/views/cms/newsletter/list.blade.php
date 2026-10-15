@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    @include('cms.newsletter.partials.heading', ['subtitle' => __('app.list')])
    @include('cms.partials.alerts')
    <div class="row main__buttons">
        <div class="col-md-12">
            <a href="{{ route('newsletter.create') }}" class="button button-white"><i class="far fa-plus-square"></i>{{ __('app.add') }}</a>
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
                            <th>{{ __('app.title') }}</th>
                            <th>{{ __('app.newsletter_status') }}</th>
                            <th class="nosort">{{ __('app.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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

    $('#dataTableSSR').DataTable({
        aoColumnDefs: [{
            bSortable: false,
            aTargets: ['nosort']
        }],
        language: {
            sEmptyTable: "{!! __('app.sEmptyTable') !!}",
            sInfo: "{!! __('app.sInfo') !!}",
            sInfoEmpty: "{!! __('app.sInfoEmpty') !!}",
            sInfoFiltered: "{!! __('app.sInfoFiltered') !!}",
            sLengthMenu: "{!! __('app.sLengthMenu') !!}",
            sLoadingRecords: "{!! __('app.sLoadingRecords') !!}",
            sProcessing: "{!! __('app.sProcessing') !!}",
            sSearch: "{!! __('app.sSearch') !!}",
            sZeroRecords: "{!! __('app.sZeroRecords') !!}",
            oPaginate: {
                sFirst: "{!! __('app.sFirst') !!}",
                sLast: "{!! __('app.sLast') !!}",
                sNext: "{!! __('app.sNext') !!}",
                sPrevious: "{!! __('app.sPrevious') !!}"
            }
        },
        bProcessing: true,
        serverSide: true,
        ajax: {
            url: "{{ url('cms/newsletter/ajax') }}",
            type: "post",
            headers: {
                'X-CSRF-Token': "{{ csrf_token() }}"
            },
            error: function() {
                $("#dataTableSSR_processing").css("display", "none");
                alert("{{ __('app.errorloading') }}");
            }
        },
        createdRow: function (row) {
            $(row).addClass("text-center");
            $(row).find('td:eq(3)').addClass("td_action");
        },
        order: [[0, "desc"]]
    });
});
</script>
@endsection

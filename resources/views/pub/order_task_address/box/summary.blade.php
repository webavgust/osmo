@extends('components.box.box-static-extralarge')

@section('body')
    <style>
        th.border-left-3, td.border-left-3 {
            border-left-width: 2px;
            border-left-color: #CCC;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <table class="tablesaw table-bordered table-hover table no-wrap tablesaw-sortable tablesaw-swipe">
                <x-order_task_address.summary.table :address="$address"></x-order_task_address.summary.table>
            </table>
        </div>
    </div>
@endsection

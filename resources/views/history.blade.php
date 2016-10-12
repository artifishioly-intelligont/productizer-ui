@extends('layouts.master')

@section('body')
<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <h3>Previous Uploads</h3>
            <hr>
            <table class="table table-bordered table-hover">
                <thead>
                    <th>Thumbnail</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Date Uploaded</th>
                </thead>
                <tbody>
                    @foreach ($maps as $map)
                        <tr class="row-clickable" data-href="{{ url('/map/'.$map->id) }}">
                            <td width="100" class="cell-nopadding"><img src="{{ url($map->thumb) }}"/></td>
                            <td>-</td>
                            <td>-</td>
                            <td>{{ date('d/m/Y H:i', strtotime($map->created_at)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{ $maps->links() }}
@endsection

@section('scripts')
<script>
jQuery(document).ready(function($) {
    $(".row-clickable").click(function() {
        window.document.location = $(this).data("href");
    });
});
</script>
@endsection
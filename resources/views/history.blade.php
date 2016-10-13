@extends('layouts.master')

@section('body')
<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <h3>Previous Uploads</h3>
            <hr>

            <div class="row">
                @foreach ($maps as $map)
                <div class="col-xs-4 col-sm-3 col-md-2 col-lg-2">
                    <a href="{{ url('/map/'.$map->id) }}">
                        <div class="history-col">
                            <img src="{{ url($map->thumb) }}"/>
                            <div class="details">
                                <small>{{ $map->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{ $maps->links() }}
@endsection

@section('scripts')
<script>
jQuery(document).ready(function($) {
    $(".col-clickable").click(function() {
        window.document.location = $(this).data("href");
    });
});
</script>
@endsection
@extends('layouts.master')

@section('body')
<div class="container">
    <div class="jumbotron slider">
        <div class="@if (count($errors) == 0) slide-1 @else slide-2 @endif">
            <h1 class="display-3">Welcome!</h1>
            <p class="lead">The Productizer allows you to select a feature from an Ordinance Survey image that you upload, and will automatically, using complex machine learnin algorithms, find the rest of those features within the image.</p>
            <hr class="m-y-2">
            <p>To get started, simply upload an Ordinance Survey image, and we'll guide you through the rest.</p>
            <p class="lead">
                <a class="btn btn-primary btn-lg" id="upload-image" role="button">Upload Image</a>
            </p>
        </div>
        <div class="@if (count($errors) > 0) slide-1 @else slide-2 @endif">
        {!! \Form::open(['files' => true]) !!}
            <h1 class="display-3">Upload</h1>
            <hr class="m-y-2">

            <p>Select your image file below.</p>
            <input type="text" disabled id="upload-file-info" class="form-control inline-input" value="No file selected."/>
            <label class="btn btn-default btn-file">
                Browse <input type="file" style="display: none;" onchange="$('#upload-file-info').val($(this).val());" name="image">
            </label>
            @if (count($errors) > 0)
            <br />
            <span class="label label-danger">You must select an image to upload. Images must be smaller than 4000x4000px.</span>
            @endif
            <p class="lead" style="margin-top:15px;">
                <input type="submit" class="btn btn-primary btn-lg" role="button" value="Upload"/>
            </p>
        {!! \Form::close() !!}
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6">
            <h3>Section One</h3>
            <hr>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-6">
            <h3>About</h3>
            <hr>
            <div class="row">
                <div class="col-xs-12 col-sm-8 col-md-7">
                    <p>The Productizer is a University of Southampton Electronics &amp; Computer Science Group Design Project in collaboration with Ordnance Survey. Designed and developed by Ed Crampin, Prem Limbu, Stefan Collier & Tom Potter.</p>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-5">
                    <div class="row">
                        <div class="col-xs-6 col-sm-12">
                            <img class="full-width" src="{{ asset('img/uoslogobw.png') }}"/>
                            <div class="hidden-xs" style="margin-bottom:20px;"></div>
                        </div>
                        <div class="col-xs-6 col-sm-12">
                            <img class="full-width" src="{{ asset('img/oslogobw.png') }}"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
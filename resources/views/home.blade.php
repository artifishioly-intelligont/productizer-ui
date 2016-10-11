@extends('layouts.master')

@section('body')
<div class="container">
    <div class="jumbotron">
      <h1 class="display-3">Welcome!</h1>
      <p class="lead">The Productizer allows you to select a feature from an Ordinance Survey image that you upload, and will automatically, using complex machine learnin algorithms, find the rest of those features within the image.</p>
      <hr class="m-y-2">
      <p>To get started, simply upload an Ordinance Survey image, and we'll guide you through the rest.</p>
      <p class="lead">
        <a class="btn btn-primary btn-lg" href="#" role="button">Upload Image</a>
      </p>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-6">
            <h3>Section One</h3>
            <hr>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-6">
            <h3>Section Two</h3>
            <hr>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        </div>
    </div>
</div>
@endsection
<div class="header">
    <div class="@if(\Request::is('map/*')) container-fluid @else container @endif">
        <div class="navbar-header">
            <div class="logo">
                <a href="{{ url('/') }}">TheProductizer</a>

                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-navi" aria-expanded="false" style="border-color: #fff;margin-top:15px;">
                    <span class="sr-only color-white ">Toggle navigation</span>
                    <span class="icon-bar color-white"></span>
                    <span class="icon-bar color-white"></span>
                    <span class="icon-bar color-white"></span>
                </button>
            </div>
        </div>
        @include('includes.navbar')
    </div>
</div>
<div class="head-spacer">
</div>
@extends('layouts.master')

@section('body')
<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-8">
            <h3>Map</h3>
            <hr>
            <div id="map"></div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4">
          <h3>Controls</h3>
          <hr>
          <div class="row">
            <div class="col-xs-8 col-xs-offset-2">
              <input type="checkbox" @if(!session()->has('guess')) checked @endif data-toggle="toggle" data-on="Learn Mode" data-off="Discover Mode" data-onstyle="primary" data-offstyle="info" data-width="100%" id="learnMode">
            </div>
          </div>
          <hr>
          <div id="controls-learn">
              <div class="form-group">
                  <label for="learn-feature">Select a feature to learn</label>
                    <div class="full-width">
                      <div style="width:68%;display:inline-block;">
                        <select class="form-control" id="learn-feature" name="learn-feature">
                          @foreach($features as $feature)
                            <option>{{ $feature }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div style="width:30%;display:inline-block;">
                        <button class="btn btn-primary full-width" id="add-feature-btn">Add New</button>
                      </div>
                    </div>
                </div>
                <div class="well" id="add-feature" style="display:none;">
                      <div class="pull-right" style="margin-top:-6px;margin-right:5px;"><button type="button" class="close" onclick="$('#add-feature').fadeOut();" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                      <p>Add Feature</p>
                      <div style="width:78%;display:inline-block;">
                        <input type="text" class="form-control" id="add-feature-input">
                      </div>
                      <div style="width:20%;display:inline-block;">
                        <button class="btn btn-primary full-width" id="add-feature-submit">Add</button>
                      </div>
                </div>
            <p>Then select 5 or more features on the map to teach The Productizer the feature. Click on an image below to remove it from selection.</p>

            <hr>
          </div>
          {{--
          <div id="controls-guess" style="display:none;">
            <p>On the map to the left, select a tile, and we will predict it's theme!</p>
          </div>
          --}}
          <div id="map-selected-learn"></div>
          {{--
          <div id="map-selected-guess" style="display:none;"></div>
          --}}
          <div id="controls-guess" style="display:none;">
              <div class="form-group">
                  <label for="discover-feature">Select a feature to discover</label>
                    <div class="full-width">
                      <div style="width:68%;display:inline-block;">
                        <select class="form-control" id="discover-feature" name="discover-feature">
                          @foreach($features as $feature)
                            <option>{{ $feature }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div style="width:30%;display:inline-block;">
                        <button class="btn btn-info full-width" id="discover-feature-btn">Discover</button>
                      </div>
                    </div>
                </div>

            <p>Select a feature and press discover, and all matching tiles will be highlighted on the map!</p>
          </div>
          <div class="row">
            <div class="col-xs-8 col-xs-offset-2">
            {!! Form::open(['id' => 'btn-learn']) !!}
              <input type="hidden" name="mode" value="learn"/>
              <input type="hidden" name="selected-feature" class="selected-feature" value="{{ $features[0] }}"/>
              <input type="hidden" id="learn-files" name="learn-files" value=";"/>
              <button type="submit" class="btn btn-primary full-width">Learn</button>
            {!! Form::close() !!}
            {{--
            {!! Form::open(['id' => 'btn-guess', 'style' => 'display:none;']) !!}
              <input type="hidden" name="mode" value="guess"/>
              <input type="hidden" name="selected-feature" class="selected-feature" value="{{ $features[0] }}"/>
              <input type="hidden" id="guess-files" name="guess-files" value=";"/>
              <button type="submit" class="btn btn-info full-width">Guess</button>
            {!! Form::close() !!}
            --}}
            </div>
          </div>
          @if(session()->has('class'))
          <br />
          <div class="row">
            <div class="col-xs-8 col-xs-offset-2">
              @if(session()->has('image')) <img src="{!! session('image') !!}" class="tile-img"/> @endif
            </div>
            <div class="col-xs-12" style="text-align:center;margin-top:10px;">
              <div class="alert alert-info">
                We predict this is a <strong>{{ session('class') }}</strong>.
              </div>
            </div>
          </div>
          @endif


        </div>
      </div>
      @if(round($current / count($tiles) * 100) != 100)
      <div class="row" id="processing-row">
        <div class="col-xs-12">
        <h3>Processing (<span id="processing-percent">{{ round($current / count($tiles) * 100) }}</span>%)...</h3>
          <div class="progress" style="margin-top:20px;">
            <div class="progress-bar progress-bar-striped active" id="processing-progress" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="{{ count($tiles) }}" style="width: {{ round($current / count($tiles) * 100) }}%;">
            </div>
          </div>
        </div>
      </div>
      @endif
</div>
@endsection

@section('scripts')
<script>

  var map;
  var learnMode = true;
  var mapMarkers = [];
  var activeMarkers = [];
  @foreach($features as $feature)
    mapMarkers["{{$feature}}"] = [];
  @endforeach
  @foreach($tiles as $tile)
    @if($tile->classification != null)
      mapMarkers["{{$tile->classification}}"].push([{{ $tile->x }}, {{ $tile->y }}]);
    @endif
  @endforeach



@if(round($current / count($tiles) * 100) != 100)
$(function() {
    var maxTiles = {{ count($tiles) }};
    var currentTiles = {{ $current }};
    pubnub = PUBNUB({
        publish_key : '{!! env('PUBNUB_PUB') !!}',
        subscribe_key : '{!! env('PUBNUB_SUB') !!}'
    })
    pubnub.subscribe({                                     
        channel : "map{{ $map->id }}",
        message : function (message, envelope, channelOrGroup, time, channel) {
            var json = JSON.parse(message);
            console.log(json);
            currentTiles++;
            var percent = Math.round(currentTiles / maxTiles * 100);
            if(percent != 100) {
              $('#processing-progress').css('width', percent+'%').attr('aria-valuenow', percent); 
              $('#processing-percent').html(percent);
            } else {
              $('#processing-row').slideUp();
            }
        }
    })
});
@endif




  $(function() {

    @if(session()->has('guess'))
      $('#btn-learn').hide();
      $('#btn-guess').show();
      $('#controls-learn').hide();
      $('#controls-guess').show();
      $('#map-selected-learn').hide();
      $('#map-selected-guess').show();
      learnMode = false;
    @endif

    $('#add-feature-btn').click(function() {
      $('#add-feature').fadeToggle();
    });


    $('#discover-feature-btn').click(function() {

      for (var i = 0; i < activeMarkers.length; i++) {
        activeMarkers[i].setMap(null);
      }
      activeMarkers = [];

      var feature = $("#discover-feature").val();

      $.each(mapMarkers[feature], function(index, value) {
        var centerLatLng = {lat: tile2lat(value[0] + 1 + 0.5, map.getZoom()), lng: tile2long(value[1] + 0.5, map.getZoom())};

        var marker = new google.maps.Marker({
          position: centerLatLng,
          map: map,
          title: 'Hello World!'
        });
        activeMarkers.push(marker);
      });
    });

    $('#add-feature-submit').click(function() {
      var feature = $('#add-feature-input').val();
      var furl = '{{ env('SATURN_URL') }}features/' + feature;
      // Check alphabetical only
      if(feature.toLowerCase().match(/[a-z]/i)) {
          $.ajax({
              url: furl,
              type: "GET",
              dataType : "json",
          })/*.done(function( json ) {
              alert(json);
          });*/
          $('#learn-feature').append($('<option/>', { 
              value: feature,
              text : feature 
          })).val(feature);

          $('#add-feature').fadeOut();
      } else {
        alert('You must enter an alphabetical feature.');
      }
    });

    $('#learn-feature').change(function() {
      $('.selected-feature').val($(this).val());
    });

    $('#learnMode').change(function() {
      learnMode = !learnMode;
      if(learnMode) {
        $('#controls-guess').fadeOut(function() {
          $('#controls-learn').fadeIn();
          $('#btn-learn').fadeIn();
          $('#map-selected-learn').fadeIn();
        });
      } else {
        $('#map-selected-learn').fadeOut(function() {
          $('#map-selected-guess').fadeIn();
        });
        $('#controls-learn').fadeOut(function() {
          $('#controls-guess').fadeIn();
        });
        $('#btn-learn').fadeOut(function() {
          $('#btn-guess').fadeIn();
        });
      }
    })
  });

  $('body').on('click', '.tile-img', function() {
    var tile = $(this).attr('src');
    var index = learnSelected.indexOf(tile);
    if (index >= 0) {
      learnSelected.splice( index, 1 );
    }
    $('#learn-files').val(learnSelected.join(";") + ";");
    $(this).closest('.tile-col').fadeOut(function() {
      $(this).remove();
    });
  });
  var markers = [];
  var learnSelected = [];
  var lines = [];
  var currentTileX = 0;
  var currentTileY = 0;

  function tile2long(x,z) { return (x/Math.pow(2,z)*360-180); }

  function tile2lat(y,z) {
      var n=Math.PI-2*Math.PI*y/Math.pow(2,z);
      return (180/Math.PI*Math.atan(0.5*(Math.exp(n)-Math.exp(-n))));
  }

  function initMap() {

    var TILE_SIZE = 256;
    if(document.getElementById('map')) {
        map = new google.maps.Map(document.getElementById('map'), {
          //center: {lat:-89.6, lng: -0},
          //zoom: 0,
          center: {lat:80.000, lng: -150},
          zoom: 4,

          styles: [
            {elementType: 'geometry', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.stroke', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.fill', stylers: [{color: '#746855'}]},
          ],
          streetViewControl: false,
          mapTypeControlOptions: {
            mapTypeIds: ['os']
          }
        });

        var osMapType = new google.maps.ImageMapType({
          getTileUrl: function(coord, zoom) {
              var normalizedCoord = getNormalizedCoord(coord, zoom);
              if (!normalizedCoord) {
                return null;
              }
              //var bound = Math.pow(2, zoom);
              var cols = {{ $map->columns }} - 1;
              var rows = {{ $map->rows }} - 1;
              if(normalizedCoord.x > cols || normalizedCoord.x < 0) {
                return null;
              }
              if(normalizedCoord.y - 1 > rows || normalizedCoord.y - 1 < 0) {
                return null;
              }
              var folder = ({{ ($map->levels - 1) }} - 2 + (zoom - 2));
              folder = (folder < 0) ? 0 : folder;
              return '{{ url('/') }}' +
                  '/maps/{{ $map->id }}/actual/actual_files/' + folder + '/' + normalizedCoord.x + '_' +
                  (normalizedCoord.y - 1) + '.jpg';
          },
          tileSize: new google.maps.Size(TILE_SIZE, TILE_SIZE),
          maxZoom: 4,
          minZoom: 0,
          radius: 1,
          name: 'OS',

        });

        map.mapTypes.set('OS', osMapType);
        map.setMapTypeId('OS');

        // HOVER TILE WIP
        google.maps.event.addListener(map,'mousemove', function(mev){
            var TILE_SIZE = 256;
            var proj = map.getProjection();
            var numTiles = 1 << map.getZoom();
            var worldCoordinate = proj.fromLatLngToPoint(mev.latLng);

            var pixelCoordinate = new google.maps.Point(
                  worldCoordinate.x * numTiles,
                  worldCoordinate.y * numTiles);

            var tileCoordinate = new google.maps.Point(
                  Math.floor(pixelCoordinate.x / TILE_SIZE),
                  Math.floor(pixelCoordinate.y / TILE_SIZE));

            for (var i = 0; i < markers.length; i++) {
              markers[i].setMap(null);
            }
            for (var i = 0; i < lines.length; i++) {
              lines[i].setMap(null);
            }


            markers = [];
            lines = [];

            if(learnMode) {
              var myLatLng = {lat: tile2lat(tileCoordinate.y, map.getZoom()), lng: tile2long(tileCoordinate.x, map.getZoom())};

              var myLatLng2 = {lat: tile2lat(tileCoordinate.y + 1, map.getZoom()), lng: tile2long(tileCoordinate.x + 1, map.getZoom())};

              var myLatLng3 = {lat: tile2lat(tileCoordinate.y + 1, map.getZoom()),  lng: tile2long(tileCoordinate.x, map.getZoom())};

              var myLatLng4 = {lat: tile2lat(tileCoordinate.y, map.getZoom()),   lng: tile2long(tileCoordinate.x + 1, map.getZoom())};
              var line = new google.maps.Polyline({
                  path: [
                      myLatLng,
                      myLatLng3,
                      myLatLng2,
                      myLatLng4,
                      myLatLng,
                  ],
                  strokeColor: "#DD0000",
                  strokeOpacity: .8,
                  strokeWeight: 3,
                  map: map
              });
              lines.push(line);
            }

        });

        google.maps.event.addListener(map,'click', function(mev){
            var TILE_SIZE = 256;
            var proj = map.getProjection();
            var numTiles = 1 << map.getZoom();
            var worldCoordinate = proj.fromLatLngToPoint(mev.latLng);

            var pixelCoordinate = new google.maps.Point(
                    worldCoordinate.x * numTiles,
                    worldCoordinate.y * numTiles);
            var tileCoordinate = new google.maps.Point(
                Math.floor(pixelCoordinate.x / TILE_SIZE),
                Math.floor(pixelCoordinate.y / TILE_SIZE));

            var tileCoordinateY = (tileCoordinate.y - 1) % {{ $map->rows }};
            tileCoordinateY = tileCoordinateY < 0 ? 0 : tileCoordinateY;

            var rawurl = '/maps/{{ $map->id }}/actual/actual_files/' + ({{ $map->levels - 1}} - (4 - map.getZoom())) + '/' + tileCoordinate.x + '_' +
                  tileCoordinateY + '.jpg';
            var tileimg = '{{ url('/') }}' + rawurl;
            var mode = learnMode ? "learn" : "guess";
            if(mode == "learn") {
              $('#map-selected-' + mode).append('<div class="col-xs-6 col-sm-4 col-md-4 col-lg-4 tile-col"><img src="'+tileimg+'" class="tile-img"/></div>');
              learnSelected.push(tileimg);
              $('#' + mode + '-files').val(learnSelected.join(";") + ";");
            } else if (false && mode == "guess") {
              $('#map-selected-' + mode).html('<div class="col-xs-8 col-xs-offset-2 tile-col"><img src="'+tileimg+'" class="tile-img"/></div>');
              $('#' + mode + '-files').val(tileimg + ";");
            }

        });
    }
  }

  // Normalizes the coords that tiles repeat across the x axis (horizontally)
  // like the standard Google map tiles.
  function getNormalizedCoord(coord, zoom) {
    var y = coord.y;
    var x = coord.x;

    // tile range in one direction range is dependent on zoom level
    // 0 = 1 tile, 1 = 2 tiles, 2 = 4 tiles, 3 = 8 tiles, etc
    var tileRange = 1 << zoom;

    var rows = {{ $map->rows }};
    // don't repeat across y-axis (vertically)
    if (y < 0 || y >= rows) { // y >= tileRange) {
      return null;
    }
    var cols = {{ $map->columns }};
    // DONT repeat across x-axis
    if (x < 0 || x >= cols) {//3 || x >= tileRange) {
        return null;
        x = (x % cols + cols) % cols;
    }

    return {x: x, y: y};
  }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBUAtkOq17fvrO06CtpNZ8UjJFFWAsFhKY&callback=initMap&libraries=drawing">
</script>
@endsection
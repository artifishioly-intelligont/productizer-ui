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
              <input type="checkbox" checked data-toggle="toggle" data-on="Learn Mode" data-off="Guess Mode" data-onstyle="primary" data-offstyle="info" data-width="100%" id="learnMode">
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
                      <p>Add Feature</p>
                      <div style="width:78%;display:inline-block;">
                        <input type="text" class="form-control" id="add-feature-input">
                      </div>
                      <div style="width:20%;display:inline-block;">
                        <button class="btn btn-primary full-width" id="add-feature-submit">Add</button>
                      </div>
                </div>
            <p>Then select 5 or more features on the map to teach The Productizer the feature.</p>
          </div>
          <div id="controls-guess" style="display:none;">
            <p>On the map to the left, draw a box around a feature, and we will predict it's theme!</p>
          </div>
          <hr>
          <div id="map-selected-learn"></div>
          <div id="map-selected-guess" style="display:none;"></div>

          <div class="row">
            <div class="col-xs-8 col-xs-offset-2">
            {!! Form::open(['id' => 'btn-learn']) !!}
              <input type="hidden" name="mode" value="learn"/>
              <input type="hidden" name="selected-feature" class="selected-feature" value="{{ $features[0] }}"/>
              <input type="hidden" id="learn-files" name="learn-files" value=""/>
              <button type="submit" class="btn btn-primary full-width">Learn</button>
            {!! Form::close() !!}
            {!! Form::open(['id' => 'btn-guess']) !!}
              <input type="hidden" name="mode" value="guess"/>
              <input type="hidden" name="selected-feature" class="selected-feature" value="{{ $features[0] }}"/>
              <input type="hidden" id="guess-files" name="guess-files" value=""/>
              <button type="submit" class="btn btn-info full-width" style="display:none;">Guess</button>
            {!! Form::close() !!}
            </div>
          </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>

  var learnMode = true;

  $(function() {

    $('#add-feature-btn').click(function() {
      $('#add-feature').fadeToggle();
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
        $('#map-selected-guess').fadeOut(function() {
          $('#map-selected-learn').fadeIn();
        });
        $('#controls-guess').fadeOut(function() {
          $('#controls-learn').fadeIn();
        });
        $('#btn-guess').fadeOut(function() {
          $('#btn-learn').fadeIn();
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
  var map;
  var markers = [];
  var currentTileX = 0;
  var currentTileY = 0;
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

        var drawingManager = new google.maps.drawing.DrawingManager({
          //drawingMode: google.maps.drawing.OverlayType.MARKER,
          drawingControl: true,
          drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_LEFT,
            drawingModes: []
          },

          rectangleOptions: {
            fillOpacity: 0.2,
            strokeWeight: 1,
            clickable: false,
            zIndex: 1
          }
        });
        drawingManager.setMap(map);

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


            function tile2long(x,z) { return (x/Math.pow(2,z)*360-180); }

            function tile2lat(y,z) {
                var n=Math.PI-2*Math.PI*y/Math.pow(2,z);
                return (180/Math.PI*Math.atan(0.5*(Math.exp(n)-Math.exp(-n))));
            }


            for (var i = 0; i < markers.length; i++) {
              markers[i].setMap(null);
            }
            markers = [];
            var myLatLng = {lat: tile2lat(tileCoordinate.y, map.getZoom()), lng: tile2long(tileCoordinate.x, map.getZoom())};

            var marker = new google.maps.Marker({
              position: myLatLng,
              map: map
            });
            var myLatLng2 = {lat: tile2lat(tileCoordinate.y + 1, map.getZoom()), lng: tile2long(tileCoordinate.x + 1, map.getZoom())};

            var marker2 = new google.maps.Marker({
              position: myLatLng2,
              map: map
            });
            markers.push(marker);
            markers.push(marker2);

            var rectangle = new google.maps.Rectangle({
              strokeColor: '#000000',
              strokeOpacity: 0.8,
              strokeWeight: 1,
              fillColor: '#555555',
              fillOpacity: 0.35,
              map: map,
              bounds: {
                north: marker.getPosition().lng(),
                south: marker2.getPosition().lng(),
                west: marker.getPosition().lat(),
                east: marker2.getPosition().lat(),
              }
            });

            console.log('TileX:' +tileCoordinate.x+' - TileY:'+tileCoordinate.y);
            console.log();

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

            var rawurl = '/maps/{{ $map->id }}/actual/actual_files/12/' + tileCoordinate.x + '_' +
                  tileCoordinateY + '.jpg';
            var tileimg = '{{ url('/') }}' + rawurl;
            var mode = learnMode ? "learn" : "guess";
            $('#map-selected-' + mode).append('<div class="col-xs-6 col-sm-4 col-md-4 col-lg-4 tile-col"><img src="'+tileimg+'" class="tile-img"/></div>');
            $('#' + mode + '-files').val($('#' + mode + '-files').val() + tileimg + ";");

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
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
            <form>
              <div class="form-group">
                  <label for="learn-feature">Select a feature to learn</label>
                  <select class="form-control" id="learn-feature" name="learn-feature">
                    @foreach($features as $feature)
                      <option>{{ $feature }}</option>
                    @endforeach
                  </select>
                </div>
            </form>
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
              <button type="button" class="btn btn-primary full-width" id="btn-learn">Learn</button>
              <button type="button" class="btn btn-info full-width" id="btn-guess" style="display:none;">Guess</button>
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

  function initMap() {

    var TILE_SIZE = 256;
    if(document.getElementById('map')) {
        var map = new google.maps.Map(document.getElementById('map'), {
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


              function point2LatLng(point) {
                var topRight = map.getProjection().fromLatLngToPoint(map.getBounds().getNorthEast());
                var bottomLeft = map.getProjection().fromLatLngToPoint(map.getBounds().getSouthWest());
                var scale = Math.pow(2, map.getZoom());
                var worldPoint = new google.maps.Point(point.x / scale + bottomLeft.x, point.y / scale + topRight.y);
                return map.getProjection().fromPointToLatLng(worldPoint);
              }
              var topLeftTileLatLng = point2LatLng(new google.maps.Point(
                  tileCoordinate.x * TILE_SIZE,
                  tileCoordinate.y * TILE_SIZE));
              var bottomRightTileLatLng = point2LatLng(new google.maps.Point(
                  tileCoordinate.x * (TILE_SIZE + 1),
                  tileCoordinate.y * (TILE_SIZE + 1)));
              var rectangle = new google.maps.Rectangle({
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.35,
                map: map,
                bounds: {
                  north: mev.latLng.lng(),
                  south: mev.latLng.lng(),
                  east: mev.latLng.lat(),
                  west: mev.latLng.lat()
                }
              });
            //console.log('TileX:' +tileCoordinate.x+' - TileY:'+tileCoordinate.y);

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

            var tileimg = '{{ url('/') }}' +
                  '/maps/{{ $map->id }}/actual/actual_files/12/' + tileCoordinate.x + '_' +
                  (tileCoordinate.y - 1) + '.jpg';
            var mode = learnMode ? "learn" : "guess";
            $('#map-selected-' + mode).append('<div class="col-xs-6 col-sm-4 col-md-4 col-lg-4 tile-col"><img src="'+tileimg+'" class="tile-img"/></div>');

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

    // don't repeat across y-axis (vertically)
    if (y < 0) { // y >= tileRange) {
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
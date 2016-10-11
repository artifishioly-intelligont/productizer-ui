@extends('layouts.master')

@section('body')
<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-7">
            <h3>Map</h3>
            <hr>
            <div id="map"></div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-5">
            <h3>Controls</h3>
            <hr>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>

  function initMap() {

    var TILE_SIZE = 256;
    if(document.getElementById('map')) {
        var map = new google.maps.Map(document.getElementById('map'), {
          center: {lat:70, lng: -120},
          zoom: 3,
          zoomControl: false,
          scaleControl: false,
          scrollwheel: false,
          disableDoubleClickZoom: true,
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
              return '{{ url('/') }}' +
                  '/maps/{{ $map->id }}/actual/actual_files/{{ $map->levels - 1}}/' + normalizedCoord.x + '_' +
                  (normalizedCoord.y - 1) + '.jpg';
          },
          tileSize: new google.maps.Size(TILE_SIZE, TILE_SIZE),
          maxZoom: 3,
          minZoom: 3,
          radius: 2,
          name: 'OS',

        });

        map.mapTypes.set('OS', osMapType);
        map.setMapTypeId('OS');

        var drawingManager = new google.maps.drawing.DrawingManager({
          //drawingMode: google.maps.drawing.OverlayType.MARKER,
          drawingControl: true,
          drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_CENTER,
            drawingModes: ['rectangle']
          }
        });
        drawingManager.setMap(map);
        google.maps.event.addListener(drawingManager, 'rectanglecomplete', function (rectangle) {
            //var coordinates = (rectangle.getBounds().getArray());

        var scale = 1 << map.getZoom();

        var worldCoordinate = project(rectangle.getBounds().getNorthEast());

        var pixelCoordinate = new google.maps.Point(
            Math.floor(worldCoordinate.x * scale),
            Math.floor(worldCoordinate.y * scale));

        var tileCoordinate = new google.maps.Point(
            Math.floor(worldCoordinate.x * scale / TILE_SIZE),
            Math.floor(worldCoordinate.y * scale / TILE_SIZE));

        var worldCoordinate2 = project(rectangle.getBounds().getSouthWest());

        var pixelCoordinate2 = new google.maps.Point(
            Math.floor(worldCoordinate2.x * scale),
            Math.floor(worldCoordinate2.y * scale));

        var tileCoordinate2 = new google.maps.Point(
            Math.floor(worldCoordinate2.x * scale / TILE_SIZE),
            Math.floor(worldCoordinate2.y * scale / TILE_SIZE));

            console.log(pixelCoordinate + " - " + pixelCoordinate2);
        });
    }

      // The mapping between latitude, longitude and pixels is defined by the web
      // mercator projection.
      function project(latLng) {
        var siny = Math.sin(latLng.lat() * Math.PI / 180);

        // Truncating to 0.9999 effectively limits latitude to 89.189. This is
        // about a third of a tile past the edge of the world tile.
        siny = Math.min(Math.max(siny, -0.9999), 0.9999);

        return new google.maps.Point(
            TILE_SIZE * (0.5 + latLng.lng() / 360),
            TILE_SIZE * (0.5 - Math.log((1 + siny) / (1 - siny)) / (4 * Math.PI)));
      }
  }

  // Normalizes the coords that tiles repeat across the x axis (horizontally)
  // like the standard Google map tiles.
  function getNormalizedCoord(coord, zoom) {
    var y = coord.y;
    var x = coord.x;

    // tile range in one direction range is dependent on zoom level
    // 0 = 1 tile, 1 = 2 tiles, 2 = 4 tiles, 3 = 8 tiles, etc
    var tileRange = 8;

    // don't repeat across y-axis (vertically)
    if (y < 0 || y >= tileRange) {
      return null;
    }

    // DONT repeat across x-axis
    if (x < 0) {//3 || x >= tileRange) {
        return null;
      //x = (x % tileRange + tileRange) % tileRange;
    }

    return {x: x, y: y};
  }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBUAtkOq17fvrO06CtpNZ8UjJFFWAsFhKY&callback=initMap&libraries=drawing">
</script>
@endsection
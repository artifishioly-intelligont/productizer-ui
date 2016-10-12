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
            <p>To do</p>
            <img src="{{ asset($map->thumb) }}"/>
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
          center: {lat:-89.6, lng: -0},
          zoom: 0,

          styles: [
            {elementType: 'geometry', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.stroke', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.fill', stylers: [{color: '#746855'}]},
          ],
          zoomControl: true,
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
            drawingModes: ['rectangle']
          },

          rectangleOptions: {
            fillColor: '#ffff00',
            fillOpacity: 0.2,
            strokeWeight: 1,
            clickable: false,
            editable: true,
            zIndex: 1
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
            console.log(tileCoordinate + " - " + tileCoordinate2);
        });

        google.maps.event.addListener(map, 'bounds_changed', function() {
            // Bounds for map calculated
            var strictBounds = new google.maps.LatLngBounds(
                map.getBounds().getSouthWest(),
                map.getBounds().getNorthEast()
            );
            // Listen for the dragend event
            google.maps.event.addListener(map, 'dragend', function() {
                if (strictBounds.contains(map.getCenter())) return;

             // We're out of bounds - Move the map back within the bounds

            var c = map.getCenter(),
                x = c.lng(),
                y = c.lat(),
                maxX = strictBounds.getNorthEast().lng(),
                maxY = strictBounds.getNorthEast().lat(),
                minX = strictBounds.getSouthWest().lng(),
                minY = strictBounds.getSouthWest().lat();

            if (x < minX) x = minX;
            if (x > maxX) x = maxX;
            if (y < minY) y = minY;
            if (y > maxY) y = maxY;

            //map.setCenter(new google.maps.LatLng(y, x));
        });
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
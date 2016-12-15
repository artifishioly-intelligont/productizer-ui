@extends('layouts.master')

@section('body')
<div class="container-fluid">
  <div class="row">
    <div id="map"></div>
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
<div class="container" style="margin-top:25px;">
    <div class="row">
          <div class="col-xs-10 col-xs-offset-1 col-md-offset-0 col-md-3">
              <input type="checkbox" @if(!session()->has('guess')) checked @endif data-toggle="toggle" data-on="Learn Mode" data-off="Discover Mode" data-onstyle="primary" data-offstyle="info" data-width="100%" id="learnMode">
            <hr class="hidden-md hidden-lg">
          </div>
          <div id="controls-learn">
            <div class="col-xs-10 col-xs-offset-1 col-md-offset-0 col-md-4">
                <p>Select tiles from the map above, then select a feature and hit learn to teach the classifier!</p>
              <hr class="hidden-md hidden-lg">
            </div>
            <div class="col-xs-10 col-xs-offset-1 col-md-offset-0 col-md-3">
                <div class="form-group">
                      <div class="full-width">
                        <div style="width:68%;display:inline-block;">
                          <select class="form-control" id="learn-feature" name="learn-feature">
                            @foreach($features as $feature)
                              <option>{{ $feature }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div style="width:30%;display:inline-block;">
                          <button class="btn btn-primary full-width" id="add-feature-btn">Add</button>
                        </div>
                      </div>
                  </div>
                <hr class="hidden-md hidden-lg">
              </div>
              <div class="col-xs-10 col-xs-offset-1 col-md-offset-0 col-md-2">
                  {!! Form::open(['id' => 'btn-learn']) !!}
                    <input type="hidden" name="mode" value="learn"/>
                    <input type="hidden" name="selected-feature" class="selected-feature" value="{{ $features[0] }}"/>
                    <input type="hidden" id="learn-files" name="learn-files" value=";"/>
                    <button type="submit" class="btn btn-primary full-width" >Learn</button>
                  {!! Form::close() !!}
              </div>
              <div class="row">
                <div class="col-xs-4 col-xs-offset-4">
                  <div class="well" id="add-feature" style="display:none;">
                        <div class="pull-right" style="margin-top:-6px;margin-right:5px;"><button type="button" class="close" onclick="$('#add-feature').fadeOut();" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                        <p>Add</p>
                        <div style="width:78%;display:inline-block;">
                          <input type="text" class="form-control" id="add-feature-input">
                        </div>
                        <div style="width:20%;display:inline-block;">
                          <button class="btn btn-primary full-width" id="add-feature-submit">Add</button>
                        </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-xs-12">
                  <div id="map-selected-learn" style="margin-top:15px;"></div>
                </div>
              </div>

            </div>


          <div id="controls-guess" style="display:none;">
            <div class="col-xs-10 col-xs-offset-1 col-md-offset-0 col-md-4">
                <p class="hidden-md hidden-lg" style="padding:19px 10px;">Select a feature to discover and it will be marked on the map above.</p>
                <p class="hidden-xs hidden-sm">Select a feature to discover and it will be marked on the map above.</p>
                <hr class="hidden-md hidden-lg">
            </div>
            <div class="col-xs-10 col-xs-offset-1 col-md-offset-0 col-md-3">
                <div class="form-group">
                      <div class="full-width">
                        <div style="width:100%;display:inline-block;">
                          <select multiple class="form-control" id="discover-feature" name="discover-feature">
                            @foreach($features as $feature)
                              <option>{{ $feature }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                  </div>
              <hr class="hidden-md hidden-lg">
            </div>

            <div class="col-xs-10 col-xs-offset-1 col-md-offset-0 col-md-2">
                <a id="btn-reclassify" style="display:none;" class="btn btn-info full-width" href="{{ url('/requeue').'/'.$map->id }}">Reclassify</a>
              </div>
          </div>


          {{--@if(session()->has('class'))
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
          @endif--}}


      </div>
    </div>
</div>
@endsection

@section('scripts')
<script>

  var map;
  var repeatX = true;
  var learnMode = true;
  var mapMarkers = [];
  var markerImages = [];
  var activeMarkers = [];

// expects an object and returns a string
function hslToRGB(hue, sat, lig) {
    var h = hue,
        s = sat,
        l = lig,
        c = (1 - Math.abs(2*l - 1)) * s,
        x = c * ( 1 - Math.abs((h / 60 ) % 2 - 1 )),
        m = l - c/ 2,
        r, g, b;

    if (h < 60) {
        r = c;
        g = x;
        b = 0;
    }
    else if (h < 120) {
        r = x;
        g = c;
        b = 0;
    }
    else if (h < 180) {
        r = 0;
        g = c;
        b = x;
    }
    else if (h < 240) {
        r = 0;
        g = x;
        b = c;
    }
    else if (h < 300) {
        r = x;
        g = 0;
        b = c;
    }
    else {
        r = c;
        g = 0;
        b = x;
    }

    r = normalize_rgb_value(r, m);
    g = normalize_rgb_value(g, m);
    b = normalize_rgb_value(b, m);
    return rgbToHex(r,g,b);
}

function normalize_rgb_value(color, m) {
    color = Math.floor((color + m) * 255);
    if (color < 0) {
        color = 0;
    }
    return color;
}

function rgbToHex(r, g, b) {
    return ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

  @foreach($features as $key => $feature)
    mapMarkers["{{ $feature }}"] = [];
    markerImages["{{ $feature }}"] = new google.maps.MarkerImage("http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|" + hslToRGB({{ 360 * ($key + 1) / count($features) }}, 1, 0.5),
            new google.maps.Size(21, 34),
            new google.maps.Point(0,0),
            new google.maps.Point(10, 34));
  @endforeach
  @foreach($tiles as $tile)
    @if($tile->classification != null)
      if(!("{{ $tile->classification }}" in mapMarkers)) {
        mapMarkers["{{ $tile->classification }}"] = [];
      }
      mapMarkers["{{$tile->classification}}"].push([{{ $tile->y }}, {{ $tile->x }}]);
    @endif
  @endforeach

  function tile2long(x,z) { return (x/Math.pow(2,z)*360-180); }

  function tile2lat(y,z) {
      var n=Math.PI-2*Math.PI*y/Math.pow(2,z);
      return (180/Math.PI*Math.atan(0.5*(Math.exp(n)-Math.exp(-n))));
  }
  var lastGoodLat = null;
  var checkLatitude = function() {
      var proj = map.getProjection();
      var bounds = map.getBounds();
      var zoom = map.getZoom();
      var sLat = map.getBounds().getSouthWest().lat();
      var nLat = map.getBounds().getNorthEast().lat();
      var wLng = map.getBounds().getSouthWest().lng();
      var eLng = map.getBounds().getNorthEast().lng();
      if (sLat < tile2lat({{ $map->rows / 2 }}, zoom)) {
          map.setCenter(new google.maps.LatLng(lastGoodLat, map.getCenter().lng()));
      } else if(nLat > tile2lat(0.5, zoom)) {
          map.setCenter(new google.maps.LatLng(lastGoodLat, map.getCenter().lng()));
      } else {
        lastGoodLat = map.getCenter().lat();
        lastGoodLng = map.getCenter().lng();
      }

  }


  var updatemarkers = function() {
    for (var i = 0; i < activeMarkers.length; i++) {
      activeMarkers[i].setMap(null);
    }
    activeMarkers = [];
    if(learnMode == false) {
      var features = $("#discover-feature").val();
      $.each(features, function(ind, feature) {

      $.each(mapMarkers[feature], function(index, value) {
        var centerLatLng = {lat: tile2lat(value[0] + 2.0, map.getZoom() + 1), lng: tile2long(value[1] + 1.0, map.getZoom() + 1)};


        var marker = new google.maps.Marker({
          position: centerLatLng,
          map: map,
          title: feature,
          icon: markerImages[feature],
        });

        var infowindow = new google.maps.InfoWindow({
          content: feature,// + " X: " + value[1] + " Y: " + value[0],
        });

        marker.addListener('mouseover', function() {
          infowindow.open(map, marker);
        });

        marker.addListener('mouseout', function() {
          infowindow.close();
        });

        activeMarkers.push(marker);
      });
      });
    }
  }

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
            mapMarkers[json.classification].push([json.y, json.x]);
            updatemarkers();
            //console.log(json);
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
      $('#btn-reclassify').show();
      $('#controls-learn').hide();
      $('#controls-guess').show();
      $('#map-selected-learn').hide();
      $('#map-selected-guess').show();
      learnMode = false;
    @endif

    $('#add-feature-btn').click(function() {
      $('#add-feature').fadeToggle();
    });
    $('#discover-feature').change(updatemarkers);

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

          $('.selected-feature').val(feature);

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
        $('#btn-reclassify').fadeOut();
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
          $('#btn-reclassify').fadeIn();
        });
      }
      updatemarkers();
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

  function initMap() {

    var TILE_SIZE = 128;
    if(document.getElementById('map')) {
        map = new google.maps.Map(document.getElementById('map'), {
          //center: {lat:-89.6, lng: -0},
          //zoom: 0,
          center: {lat:78.800, lng: -115},
          zoom: 4,

          styles: [
            {elementType: 'geometry', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.stroke', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.fill', stylers: [{color: '#746855'}]},
          ],
          streetViewControl: false,
          zoomControl: false,
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
          //minZoom: 0,
          minZoom: 4,
          radius: 1,
          name: 'OS',

        });
        var boundaryline = new google.maps.Polyline({
            path: [
                {lat: tile2lat(0, map.getZoom()),   lng: tile2long(0, map.getZoom())},
                {lat: tile2lat({{ $map->columns }}, map.getZoom()),   lng: tile2long(0, map.getZoom())},
            ],
            strokeColor: '#3A99D8',
            strokeOpacity: 1.0,
            strokeWeight: 7,
            map: map
        });
        map.mapTypes.set('OS', osMapType);
        map.setMapTypeId('OS');

        google.maps.event.addListener(map, 'center_changed', checkLatitude);

        // HOVER TILE WIP
        google.maps.event.addListener(map,'mousemove', function(mev){
            var proj = map.getProjection();
            var numTiles = 1 << map.getZoom();
            var worldCoordinate = proj.fromLatLngToPoint(mev.latLng);
            var zoom = map.getZoom() + 1;

            var pixelCoordinate = new google.maps.Point(
                  worldCoordinate.x * numTiles,
                  worldCoordinate.y * numTiles);

            var tileCoordinate = new google.maps.Point(
                  Math.floor(pixelCoordinate.x / TILE_SIZE),
                  Math.floor(pixelCoordinate.y / TILE_SIZE));

            if(tileCoordinate.x >= {{ $map->columns - 1 }}) {
              tileCoordinate.x = {{ $map->columns - 2 }};
            }
            if(tileCoordinate.y >= {{ $map->rows - 1 }}) {
              tileCoordinate.y = {{ $map->columns - 2 }};
            }

            if(tileCoordinate.y <= 0) {
              tileCoordinate.y = 1;
            }
            for (var i = 0; i < markers.length; i++) {
              markers[i].setMap(null);
            }
            for (var i = 0; i < lines.length; i++) {
              lines[i].setMap(null);
            }


            markers = [];
            lines = [];

            if(learnMode || true) {
              var myLatLng = {lat: tile2lat(tileCoordinate.y, zoom), lng: tile2long(tileCoordinate.x, zoom)};

              var myLatLng2 = {lat: tile2lat(tileCoordinate.y + 2, zoom), lng: tile2long(tileCoordinate.x + 2, zoom)};

              var myLatLng3 = {lat: tile2lat(tileCoordinate.y + 2, zoom),  lng: tile2long(tileCoordinate.x, zoom)};

              var myLatLng4 = {lat: tile2lat(tileCoordinate.y, zoom),   lng: tile2long(tileCoordinate.x + 2, zoom)};
              var line = new google.maps.Polyline({
                  path: [
                      myLatLng,
                      myLatLng3,
                      myLatLng2,
                      myLatLng4,
                      myLatLng,
                  ],
                  strokeColor: (learnMode ? "#DD0000" : "#3AB0D3"),
                  strokeOpacity: 1.0,
                  strokeWeight: 4,
                  map: map
              });
              lines.push(line);
            }

        });

        google.maps.event.addListener(map,'click', function(mev){
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


            if(tileCoordinate.x >= {{ $map->columns - 1 }}) {
              tileCoordinate.x = {{ $map->columns - 2 }};
            }
            if(tileCoordinateY >= {{ $map->rows - 2 }}) {
              tileCoordinateY = {{ $map->columns - 3 }};
            }

            var rawurl = '/maps/{{ $map->id }}/actual/actual_files/' + ({{ $map->levels - 1}} - (4 - map.getZoom())) + '/' + tileCoordinate.x + '_' +
                  tileCoordinateY + 'sel.jpg';
            var tileimg = '{{ url('/') }}' + rawurl;
            var mode = learnMode ? "learn" : "guess";
            if(mode == "learn") {
              $('#map-selected-' + mode).append('<div class="col-xs-4 col-sm-3 col-md-2 col-lg-1 tile-col"><img src="'+tileimg+'" class="tile-img"/></div>');
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
        if(!repeatX) return null;
        x = (x % cols + cols) % cols;
    }

    return {x: x, y: y};
  }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBUAtkOq17fvrO06CtpNZ8UjJFFWAsFhKY&callback=initMap&libraries=drawing">
</script>
@endsection
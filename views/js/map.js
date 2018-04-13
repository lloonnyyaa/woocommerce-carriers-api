var map_initialized = false;

/* detect shipping method change */
jQuery('form.checkout').on('change', 'input[name^="shipping_method"]', function() {
    /* check new shipping method */
    var new_shipping_method = jQuery(this);
    /* show the map and controls, if new shipping method is our custom shipping method, otherwise hide them */
    if (new_shipping_method.val().indexOf('custom_shipping_method') != -1) {
        jQuery('#vd_woo_pudo_penguin_wrapper').slideDown();
        /* reinit map, since it may look bad if init happened on hidden parent block */
        if (map_initialized == false) {
            initMap();
        }
        /* if the different shipping address checkbox isn't set, set it to true */
        if (jQuery('#ship-to-different-address-checkbox').is(':checked') == false) {
            jQuery('#ship-to-different-address-checkbox').attr('checked', true).trigger('change');
        } 
    } else {
        jQuery('#vd_woo_pudo_penguin_wrapper').slideUp();
    }
});

/* detect other change events, e.g. changing of address, which might impact the shipping method */
jQuery('body').on('updated_checkout', function() {
    /* check new shipping method */
    var new_shipping_method = jQuery('input[name^="shipping_method"]:checked');
    /* show the map and controls, if new shipping method is our custom shipping method, otherwise hide them */
    if (new_shipping_method.length > 0 && new_shipping_method.val().indexOf('custom_shipping_method') != -1) {
        jQuery('#vd_woo_pudo_penguin_wrapper').slideDown();
        /* reinit map, since it may look bad if init happened on hidden parent block */
        if (map_initialized == false) {
            initMap();
        }
        /* if the different shipping address checkbox isn't set, set it to true */
        if (jQuery('#ship-to-different-address-checkbox').is(':checked') == false) {
            jQuery('#ship-to-different-address-checkbox').attr('checked', true).trigger('change');
        } 
    } else {
        jQuery('#vd_woo_pudo_penguin_wrapper').slideUp();
    }
});

var googleMaps = function (config) {

    this.config = config;
    this.map;
    this.infoWindow = new google.maps.InfoWindow();
    this.bounds = new google.maps.LatLngBounds();
    this.preloader = document.getElementById("preloader");
    this.preloader_find_user = document.getElementById("preloader-find-user");
    this.preloader_find_locations = document.getElementById("preloader-find-locations");
    this.btn_find_me = document.getElementById("btnAction");
    this.btn_find_zip = document.getElementById("enterZip");
    var that = this;

    this.initMap = function () {
        var defaultPosition = {lat: 52.6139336, lng: -104.5328769};
        
        that.showMap(defaultPosition, 4);
        that.locate();
    };
    
    this.showMap = function(coordinates, zoom){
        var mapLayer = document.getElementById("map");
        var centerCoordinates = new google.maps.LatLng(coordinates.lat, coordinates.lng);
        var defaultOptions = {center: centerCoordinates, zoom: zoom};

        that.map = new google.maps.Map(mapLayer, defaultOptions);
    };

    this.locate = function () {
        if (navigator.geolocation) {
            that.preloader.style.display = 'block';
            that.preloader_find_user.style.display = 'inline-block';
            that.preloader_find_locations.style.display = 'none';

            that.btn_find_me.disabled = true;
            that.btn_find_zip.disabled = true;

            navigator.geolocation.getCurrentPosition(function (position) {
                var currentLatitude = position.coords.latitude;
                var currentLongitude = position.coords.longitude;

                var currentLocation = {lat: currentLatitude, lng: currentLongitude};
                that.map.setCenter(currentLocation);
                that.infoWindow.setOptions({
                    map: that.map,
                    position: currentLocation,
                    content: "<p><strong>You a here</strong></p><p>you can select a closest to you<br>carrier location</p>",
                });

                that.bounds.extend(currentLocation);

                var pos = {
                    lat: parseFloat(position.coords.latitude),
                    lng: parseFloat(position.coords.longitude)
                }
                that.geocodeLatLng(pos);
            }, function () {
                that.handleLocationError(true);
            });
        } else {
            that.handleLocationError(false);
        }
    };

    this.geocodeLatLng = function (position) {
        that.preloader_find_user.style.display = 'none';
        that.preloader_find_locations.style.display = 'inline-block';

        var geocoder = new google.maps.Geocoder;

        geocoder.geocode({location: position}, function (results, status) {
            var zip, address;
            if (status === 'OK') {
                address = results[0].address_components;
                zip = address[address.length - 1].long_name;

//                results[0].address_components.forEach(function(element){
//                    if (element.types.indexOf('postal_code') == 0) {
//                        zip = element.long_name;
//                    }
//                });
            }
            that.findСarrierLocations(position, zip);
        });
    };

    this.findByZip = function (zip) {
        that.preloader.style.display = 'block';
        that.preloader_find_user.style.display = 'none';
        that.preloader_find_locations.style.display = 'inline-block';
        
        var geocoder = new google.maps.Geocoder;

        geocoder.geocode({address: zip}, function (results, status) {
            var position, lat, lng;
            if (status === 'OK') {
                lat = results[0].geometry.location.lat();
                lng = results[0].geometry.location.lng();
                position = {lat: lat, lng: lng}
            }
            that.showMap(position, 10);
            
            that.findСarrierLocations(position, zip);
        });
    };

    this.findСarrierLocations = function (position, zip) {
        jQuery.ajax({
            url: that.config.ajax_url,
            data: {
                action: 'vdws_find_carrier_locations',
                nonce: that.config.nonce,
                position: position,
                zip: zip
            },
            type: "POST",
        }).done(function (answer) {
            //console.log(answer);
            that.addMarkers(answer.data)
        }).fail(function (xhr, status, error) {
            var err = xhr.responseText;
            console.log(err);
        });
    };

    this.addMarkers = function (locations) {
        var markers = locations.map(function (location, i) {
            that.bounds.extend(location);

            return new google.maps.Marker({
                position: new google.maps.LatLng(location.lat, location.lng)
            });
        });

        new MarkerClusterer(that.map, markers,
                {imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'});

        markers.map(function (marker, i) {
            google.maps.event.addListener(marker, 'click', (function (marker, i) {
                var content =
                        "<h4>" + locations[i].carrier + "</h4>" +
                        "<p>" + locations[i].address + "</p>" +
                        "<button onclick='selectCarrierLocation(this)' data-address_1='"
                        + locations[i].address_1 +
                        "' data-address_2='"
                        + locations[i].address_2 +
                        "' data-city='"
                        + locations[i].city +
                        "' data-zip='"
                        + locations[i].zip +
                        "' data-country='"
                        + locations[i].country +
                        "' data-carrier='"
                        + locations[i].carrier +
                        "' data-location_name='"
                        + locations[i].location_name +
                        "' data-province='"
                        + locations[i].province +
                        "'>choose</button>";

                return function () {
                    that.infoWindow.setContent(content);
                    that.infoWindow.open(that.map, marker);
                };
            })(marker, i));
        });

        that.map.fitBounds(that.bounds);

        that.preloader.style.display = 'none';
        that.btn_find_me.disabled = false;
        that.btn_find_zip.disabled = false;
    };

    this.selectCarrierLocation = function (location) {
        var address_1 = jQuery(location).data('address_1');
        var address_2 = jQuery(location).data('address_2');
        var city = jQuery(location).data('city');
        var zip = jQuery(location).data('zip');
        var province = jQuery(location).data('province');
        var country = jQuery(location).data('country');
        var carrier = jQuery(location).data('carrier');
        var location_name = jQuery(location).data('location_name');
        var order_note = "Carrier: " + carrier + ", location name: " + location_name;

//        jQuery('select#billing_country').val(country).trigger('change');
//        jQuery('input#billing_address_1').val(address_1).trigger('change');
//        jQuery('input#billing_address_2').val(address_2).trigger('change');
//        jQuery('input#billing_city').val(city).trigger('change');
//        jQuery('input#billing_postcode').val(zip).trigger('change');
//        if (country == 'CA') {
//            jQuery('select#billing_state').val(province).trigger('change');
//        }

        jQuery('#ship-to-different-address-checkbox').prop("checked", true).trigger('change');
        jQuery('select#shipping_country').val(country).trigger('change');
        jQuery('input#shipping_address_1').val(address_1).trigger('change');
        jQuery('input#shipping_address_2').val(address_2).trigger('change');
        jQuery('input#shipping_city').val(city).trigger('change');
        jQuery('input#shipping_postcode').val(zip).trigger('change');
        if (country == 'CA') {
            jQuery('select#shipping_state').val(province).trigger('change');
        }

        jQuery('textarea#order_comments').val(order_note);
    };

    this.handleLocationError = function (browserHasGeolocation) {
        var pos = that.map.getCenter();
        that.infoWindow.setPosition(pos);
        that.infoWindow.setContent(browserHasGeolocation ?
                'Error: The Geolocation service failed.' :
                'Error: Your browser doesn\'t support geolocation.');
    };
};

function initMap() {
    var map = new googleMaps(vdws_data);
    map_initialized = true;
    map.initMap();
}

function findByZip(zip) {
    var map = new googleMaps(vdws_data);
    map.findByZip(zip);
}

function selectCarrierLocation(location) {
    var map = new googleMaps(vdws_data);
    map.selectCarrierLocation(location);
}
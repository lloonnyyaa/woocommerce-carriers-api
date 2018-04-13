<div id="vd_woo_pudo_penguin_wrapper">
    <div id="button-layer">
        <button id="enterZip" onClick="jQuery('#zip-input-wrapper').slideToggle()">Find by address or zip code</button>
        <button id="btnAction" onClick="initMap();return false;">My Current Location</button>
        <div id="zip-input-wrapper" style="padding:10px 0;display:none">
            <input id="zip-input" type="text" placeholder="Enter address or zip code">
            <button id="btn-find-zip" onClick="findByZip(jQuery('#zip-input').val());return false;">Find</button>
        </div>
    </div>
    <div id="preloader">
        <p id="preloader-find-user">trying to determine your location</p>
        <p id="preloader-find-locations" style="display:none">trying to determine closest to you carrier locations</p>
        <div class="lds-ellipsis">
            <div></div>
            <div></div>
            <div></div>
            <div></div>        
        </div>
    </div>
    <div id="map"></div>
</div>
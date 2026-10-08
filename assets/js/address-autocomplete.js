(function () {
  window.gm_authFailure = function () {
    console.error('[Google Places] Auth failure — check API key referrers at console.cloud.google.com/apis/credentials');
  };

  /**
   * Initialize Google Places Autocomplete on a single input element.
   * Expects sibling fields named city / state / zip within the same <form>.
   *
   * @param {HTMLInputElement} input
   */
  function initAutocomplete(input) {
    google.maps.importLibrary('places').then(function (places) {
      var form = input.closest('form');

      var autocomplete = new places.Autocomplete(input, {
        types: ['address'],
        componentRestrictions: { country: 'us' },
        fields: ['address_components']
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); }
      });

      autocomplete.addListener('place_changed', function () {
        var place = autocomplete.getPlace();
        if (!place || !place.address_components) return;

        var num = '', route = '', city = '', state = '', zip = '';

        place.address_components.forEach(function (c) {
          var t = c.types;
          if      (t.indexOf('street_number')              !== -1) num   = c.long_name;
          else if (t.indexOf('route')                       !== -1) route = c.long_name;
          else if (t.indexOf('locality')                    !== -1) city  = c.long_name;
          else if (t.indexOf('administrative_area_level_1') !== -1) state = c.short_name;
          else if (t.indexOf('postal_code')                 !== -1) zip   = c.long_name;
        });

        var streetAddr = [num, route].filter(Boolean).join(' ');
        if (streetAddr) {
          input.value = streetAddr;
        }

        if (form) {
          var cityEl  = form.querySelector('[name="city"]');
          var stateEl = form.querySelector('[name="state"]');
          var zipEl   = form.querySelector('[name="zip"]');

          if (cityEl  && city)  { cityEl.value  = city;  }
          if (stateEl && state) { stateEl.value = state; }
          if (zipEl   && zip)   { zipEl.value   = zip;   }
        }
      });
    });
  }

  /**
   * Initialize autocomplete on all [data-address-autocomplete] elements
   * present in the DOM at call time.
   */
  function initAddressAutocomplete() {
    document.querySelectorAll('[data-address-autocomplete]').forEach(function (input) {
      initAutocomplete(input);
    });
  }

  window.initAutocomplete        = initAutocomplete;
  window.initAddressAutocomplete = initAddressAutocomplete;
}());

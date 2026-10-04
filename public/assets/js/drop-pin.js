/**
 * Delivery pin map, used at checkout and for saved addresses (Settings > Shipping addresses).
 * The pin stays in the middle and the customer drags the map under it, like delivery apps.
 * It tries the customer's live location first, and suggests the nearest street address
 * (OpenStreetMap Nominatim reverse geocoding).
 *
 *   const pin = await psaDropPin(element, { start: { lat, lng }, onUseAddress: text => ... });
 *   pin.getLatLng()  // { lat, lng } of the pinned spot
 */
(function () {
  const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
  const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
  const PHNOM_PENH = { lat: 11.5564, lng: 104.9282 };
  const DELIVERY_AREA = { minLat: 11.42, maxLat: 11.72, minLng: 104.72, maxLng: 105.08 }; // greater Phnom Penh

  function loadLeaflet() {
    if (window.L) return Promise.resolve();
    return new Promise((resolve, reject) => {
      if (!document.querySelector(`link[href="${LEAFLET_CSS}"]`)) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = LEAFLET_CSS;
        document.head.appendChild(link);
      }
      const script = document.createElement('script');
      script.src = LEAFLET_JS;
      script.onload = resolve;
      script.onerror = () => reject(new Error('The map could not load.'));
      document.head.appendChild(script);
    });
  }

  const insideArea = p => p.lat >= DELIVERY_AREA.minLat && p.lat <= DELIVERY_AREA.maxLat && p.lng >= DELIVERY_AREA.minLng && p.lng <= DELIVERY_AREA.maxLng;

  window.psaDropPin = async function (host, opts = {}) {
    host.innerHTML = `
      <div class="psa-pin">
        <div class="psa-pin__mapwrap">
          <div class="psa-pin__map" data-pin-map role="application" aria-label="Map: drag to place the delivery pin"></div>
          <div class="psa-drop-pin" data-pin-marker aria-hidden="true">
            <svg viewBox="0 0 40 52" width="40" height="52"><path d="M20 51s17-17.4 17-30A17 17 0 0 0 3 21c0 12.6 17 30 17 30z" fill="#FFA552" stroke="#2B1D1D" stroke-width="3"/><circle cx="20" cy="20" r="6.5" fill="#2B1D1D"/></svg>
            <span class="psa-drop-pin__shadow"></span>
          </div>
          <button type="button" class="psa-locate-btn" data-pin-locate>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
            <span data-pin-locate-label>Locate me</span>
          </button>
        </div>
        <div class="psa-pin__card" aria-live="polite">
          <span class="psa-pin__dot" data-pin-dot></span>
          <div class="psa-pin__text">
            <div class="psa-pin__status" data-pin-status>Loading the map…</div>
            <div class="psa-pin__address" data-pin-address></div>
            <div class="psa-pin__coords" data-pin-coords></div>
          </div>
          <button type="button" class="psa-pin__use" data-pin-use hidden>Use address</button>
        </div>
      </div>`;
    const q = sel => host.querySelector(sel);

    function setStatus(text, tone) {
      q('[data-pin-status]').textContent = text;
      q('[data-pin-dot]').dataset.tone = tone || 'wait';
    }

    try {
      await loadLeaflet();
    } catch (e) {
      setStatus('The map could not load. Type your address instead.', 'warn');
      return null;
    }

    const start = opts.start && Number.isFinite(Number(opts.start.lat)) && Number.isFinite(Number(opts.start.lng))
      ? { lat: Number(opts.start.lat), lng: Number(opts.start.lng) } : null;
    let pinned = start || { ...PHNOM_PENH };
    let accuracyCircle = null;
    let geocodeTimer = null;
    let lastGeocoded = null;
    let suggested = '';

    const map = L.map(q('[data-pin-map]'), {
      center: [pinned.lat, pinned.lng],
      zoom: start ? 18 : 15,
      scrollWheelZoom: 'center',
      touchZoom: 'center',
      doubleClickZoom: 'center',
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      subdomains: ['a', 'b', 'c'],
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    async function lookUpAddress() {
      const p = pinned;
      if (lastGeocoded && map.distance([p.lat, p.lng], [lastGeocoded.lat, lastGeocoded.lng]) < 25) return;
      lastGeocoded = { ...p };
      try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1&accept-language=en&lat=${p.lat}&lon=${p.lng}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('lookup failed');
        const a = (await res.json()).address || {};
        const parts = [
          [a.house_number, a.road].filter(Boolean).join(' '),
          a.neighbourhood || a.quarter || a.suburb,
          a.city_district || a.district,
          a.city || a.town || a.state,
        ].filter(Boolean);
        suggested = [...new Set(parts)].join(', ');
      } catch (e) {
        suggested = '';
      }
      q('[data-pin-address]').textContent = suggested ? 'Near ' + suggested : '';
      q('[data-pin-use]').hidden = !suggested || !opts.onUseAddress;
    }

    function onMoved() {
      const c = map.getCenter();
      pinned = { lat: c.lat, lng: c.lng };
      q('[data-pin-coords]').textContent = `${c.lat.toFixed(5)}, ${c.lng.toFixed(5)}`;
      if (insideArea(pinned)) setStatus(opts.pinnedLabel || 'Delivery pin set', 'ok');
      else setStatus('This spot is outside our Phnom Penh delivery area', 'warn');
      if (typeof opts.onChange === 'function') opts.onChange({ ...pinned });
      clearTimeout(geocodeTimer);
      geocodeTimer = setTimeout(lookUpAddress, 700); // wait until the map stops (and stay polite to the free geocoder)
    }

    function locate(fromButton) {
      const label = q('[data-pin-locate-label]');
      if (!('geolocation' in navigator)) {
        setStatus("Your browser can't share your location. Drag the map to your door instead.", 'wait');
        return;
      }
      label.textContent = 'Locating…';
      setStatus('Finding your location…', 'wait');
      navigator.geolocation.getCurrentPosition(pos => {
        label.textContent = 'Locate me';
        const here = [pos.coords.latitude, pos.coords.longitude];
        if (accuracyCircle) accuracyCircle.remove();
        accuracyCircle = L.circle(here, { radius: Math.min(pos.coords.accuracy, 500), color: '#FFA552', weight: 1, fillColor: '#FFA552', fillOpacity: 0.12, interactive: false }).addTo(map);
        map.setView(here, 18);
        if (pos.coords.accuracy > 80) {
          setTimeout(() => setStatus(`Located to about ${Math.round(pos.coords.accuracy)} m: drag the map to your exact door`, 'wait'), 50);
        }
      }, err => {
        label.textContent = 'Locate me';
        setStatus(err.code === 1
          ? 'Location is turned off for this site. Drag the map to your door, or allow location and tap Locate me.'
          : "We couldn't get your location. Drag the map to your door.", 'wait');
        if (!fromButton) onMoved();
      }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 });
    }

    const marker = q('[data-pin-marker]');
    map.on('movestart', () => marker.classList.add('is-lifted'));
    map.on('moveend', () => { marker.classList.remove('is-lifted'); onMoved(); });
    map.on('click', e => map.panTo(e.latlng)); // tap anywhere to move the pin there
    q('[data-pin-locate]').addEventListener('click', () => locate(true));
    q('[data-pin-use]').addEventListener('click', () => opts.onUseAddress && opts.onUseAddress(suggested));

    setTimeout(() => {
      map.invalidateSize();
      onMoved();
      if (!start && opts.autoLocate !== false) locate(false);
    }, 200);

    return {
      map,
      getLatLng: () => ({ ...pinned }),
      getAddress: () => suggested,
      setView: (lat, lng) => map.setView([lat, lng], 18),
      locate: () => locate(true),
    };
  };
})();

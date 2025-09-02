// assets/js/event-form.js

document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('.autocomplete-place');
    const suggestionsList = document.querySelector('#suggestions');
    const cityInput = document.querySelector('#event_city');
    const latInput = document.querySelector('#event_gpsLatitude');
    const lonInput = document.querySelector('#event_gpsLongitude');
    const streetInput = document.querySelector('#event_street');
    const postalCodeInput = document.querySelector('#event_postalCode');

    let suggestions = [];

    input.addEventListener('input', async function() {
        const query = this.value;
        if(query.length < 3) {
            suggestionsList.innerHTML = '';
            return;
        }

        const response = await fetch(`places/search?q=${encodeURIComponent(query)}`);
        suggestions = await response.json();

        suggestionsList.innerHTML = '';
        suggestions.forEach((place, index) => {
            const li = document.createElement('li');
            li.classList.add('list-group-item');
            li.textContent = place.display_name;
            li.addEventListener('click', () => selectSuggestion(index));
            suggestionsList.appendChild(li);
        });
    });

    function selectSuggestion(index) {
        const place = suggestions[index];
        const nameParts = place.display_name.split(',');
        input.value = nameParts[0].trim();

        const address = place.address || {};
        cityInput.value = address.city || address.town || address.village || address.municipality || address.county || '';
        latInput.value = place.lat || '';
        lonInput.value = place.lon || '';

        let streetParts = [];
        if (address.house_number) streetParts.push(address.house_number);
        if (address.road) streetParts.push(address.road);
        if (address.neighbourhood) streetParts.push(address.neighbourhood);
        if (address.suburb) streetParts.push(address.suburb);

        streetInput.value = streetParts.join(' ').trim();
        postalCodeInput.value = address.postcode || '';

        suggestionsList.innerHTML = '';
    }

    document.addEventListener('click', function(e) {
        if (!suggestionsList.contains(e.target) && e.target !== input) {
            suggestionsList.innerHTML = '';
        }
    });
});

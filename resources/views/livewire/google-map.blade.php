<div class="p-4">
    <div id="currentLocationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg max-w-md mx-auto mt-20">
            <h3 class="text-lg font-bold mb-4">Find Nearby Places</h3>
            <input id="currentLocationInput" type="text" placeholder="Enter your current location" 
                class="p-3 border rounded-lg w-full mb-4">
            <div class="flex justify-end space-x-4">
                <button onclick="closeLocationModal()" class="p-2 border rounded-lg">Skip</button>
                <button onclick="showNearbyPlaces()" class="p-2 bg-blue-500 text-white rounded-lg">Show Places</button>
            </div>
        </div>
    </div>
    <div class="flex justify-center space-x-4 mb-8">
        <input id="startLocation" type="text" placeholder="Enter your starting location"
            class="p-3 border rounded-lg w-64">
        <input id="endLocation" type="text" placeholder="Find and select your destination"
            class="p-3 border rounded-lg w-64">
        <button onclick="getDirection()" id="directionbutton" class="p-3 bg-blue-500 text-white rounded-lg">Get
            Directions</button>
    </div>

    <!-- Message Display Area -->
    <div id="loginMessage" class="hidden text-red-600 font-semibold text-center mb-4"></div>

    <div class="flex items-start h-screen gap-8">
        <div id="googleMap" class="w-full h-full"></div>

        <div class="w-full overflow-y-auto h-full">
            <h2>Places You Can Visit!</h2>
            <div>
                <ul id="places-list"></ul>
            </div>
        </div>
    </div>
</div>

<script>
    let directionsService;
    let directionsRenderer;
    let startAutocomplete;
    let endAutocomplete;
    let currentLocationAutocomplete;
    let map;

    const placesList = document.getElementById("places-list");
    const loginMessage = document.getElementById("loginMessage");

    // Simulate user authentication status
    const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
    const samplePlaces = @json($places);

    function initMap() {
        const mapProp = {
            center: new google.maps.LatLng(6.905221589477516, 79.9133676248998),
            zoom: 10,
        };
        map = new google.maps.Map(document.getElementById("googleMap"), mapProp);

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(map);

        startAutocomplete = new google.maps.places.Autocomplete(document.getElementById("startLocation"), {
            componentRestrictions: {
                country: "LK"
            }
        });
        endAutocomplete = new google.maps.places.Autocomplete(document.getElementById("endLocation"), {
            componentRestrictions: {
                country: "LK"
            }
        });
        currentLocationAutocomplete = new google.maps.places.Autocomplete(document.getElementById("currentLocationInput"), {
        componentRestrictions: { country: "LK" }
        });

        document.getElementById("currentLocationModal").classList.remove("hidden");
    }

    function closeLocationModal() {
        document.getElementById("currentLocationModal").classList.add("hidden");
    }

    function showNearbyPlaces() {
        const place = currentLocationAutocomplete.getPlace();
        if (!place || !place.geometry) {
            loginMessage.textContent = "Please select a valid location.";
            loginMessage.classList.remove("hidden");
            return;
        }

        // Clear previous markers and directions
        directionsRenderer.setMap(null);
        map.setCenter(place.geometry.location);
        map.setZoom(12);

    // Draw 20km radius circle
        const circle = new google.maps.Circle({
            map: map,
            center: place.geometry.location,
            radius: 20000,
            fillColor: '#FF000050',
            strokeColor: '#FF0000'
        });

        // Clear previous place cards
        placesList.innerHTML = '';

        // Find and display nearby places
        findLocationsWithinRadius(samplePlaces, [place.geometry.location], 20);
        closeLocationModal();
    }

    function getDirection() {
        if (!isLoggedIn) {
            // Display the message
            loginMessage.textContent = "You need to log in first to use this feature.";
            loginMessage.classList.remove("hidden");
            loginMessage.classList.add("block");
            return;
        }

        // Hide the login message if user is logged in
        loginMessage.classList.add("hidden");
        loginMessage.classList.remove("block");

        const startPlace = startAutocomplete.getPlace();
        const endPlace = endAutocomplete.getPlace();

        if (!startPlace || !endPlace || !startPlace.geometry || !endPlace.geometry) {
            loginMessage.textContent = "Please select valid starting and destination locations.";
            loginMessage.classList.remove("hidden");
            return;
        }

        directionsService
            .route({
                origin: startPlace.geometry.location,
                destination: endPlace.geometry.location,
                travelMode: google.maps.TravelMode.DRIVING,
            })
            .then((response) => {
                directionsRenderer.setDirections(response);
                findLocationsWithinRadius(samplePlaces, response.routes[0].overview_path, 10);
            })
            .catch((e) => {
                loginMessage.textContent = "Directions request failed: " + e.message;
                loginMessage.classList.remove("hidden");
            });
    }

    function haversine(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const toRad = (angle) => angle * (Math.PI / 180);

        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const lat1Rad = toRad(lat1);
        const lat2Rad = toRad(lat2);

        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(lat1Rad) * Math.cos(lat2Rad) * Math.sin(dLon / 2) ** 2;
        const c = 2 * Math.asin(Math.sqrt(a));

        return R * c;
    }

    function findLocationsWithinRadius(samplePlaces, routePath, radius) {
        const locationsWithinRadius = [];

        console.log(samplePlaces);

        samplePlaces.forEach((place) => {
            const {
                name,
                lat,
                long,
                photo
            } = place;

            for (let i = 0; i < routePath.length; i++) {
                const routePoint = routePath[i];
                const distance = haversine(lat, long, routePoint.lat(), routePoint.lng());

                if (distance <= radius) {
                    placesList.innerHTML += `
                        <div class="place-card" style="display: flex; align-items: center; margin: 10px 0; border: 1px solid #ddd; padding: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <img src="storage/${photo}" alt="${name}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; margin-right: 15px;">
                            <div class="place-details">
                                <h3 style="margin: 0; font-size: 18px; font-weight: bold;">${name}</h3>
                                <p style="margin: 5px 0; font-size: 14px; color: #555;">${distance.toFixed(2)} km away</p>
                            </div>
                        </div>                    `;

                    const pin = "{{ asset('images/pin.png') }}";

                    new google.maps.Marker({
                        map: map,
                        position: {
                            lat: lat,
                            lng: long
                        },
                        icon: pin,
                        title: name
                    });

                    locationsWithinRadius.push({
                        name,
                        lat,
                        long,
                        distance
                    });
                    break;
                }
            }
        });

        return locationsWithinRadius;
    }
</script>

<script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=places&callback=initMap" async
    defer></script>

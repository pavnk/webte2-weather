function drawCountriesTable(data) {
    var uniqueVisitors = {};
    var tableData = [];
    var waypoints = []; // Array to store the waypoints for the Leaflet Map

    // Count unique visitors by country and collect waypoints
    for (var i = 0; i < data.length; i++) {
        var ip = data[i].ip;
        var country = data[i].country;
        var countryCode = data[i].code;
        var latitude = data[i].latitude; // Assuming you have latitude data in your `get-data-all.php` response
        var longitude = data[i].longitude; // Assuming you have longitude data in your `get-data-all.php` response

        // Check if the visitor's IP is unique in the last 24 hours
        var visitTime = new Date(data[i].visit_time);
        var currentTime = new Date();
        var timeDiff = currentTime - visitTime;
        var twentyFourHours = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

        if (timeDiff <= twentyFourHours) {
            if (!uniqueVisitors[ip] || uniqueVisitors[ip] > visitTime) {
                uniqueVisitors[ip] = visitTime;

                // Increment the visitor count for the country
                if (!tableData[country]) {
                    tableData[country] = { count: 1, code: countryCode, visitTime: visitTime };
                } else {
                    tableData[country].count++;
                }

                // Add the waypoint for the Leaflet Map
                waypoints.push({ lat: latitude, lng: longitude });
            }
        }
    }

    // Prepare data for table
    var tableDataArray = [];
    for (var country in tableData) {
        var row = {
            country: country,
            visitorCount: tableData[country].count,
            flag: "http://www.geonames.org/flags/x/" + tableData[country].code + ".gif"
        };
        tableDataArray.push(row);
    }

    // Draw table
    var table = new Tabulator("#countries", {
        data: tableDataArray,
        layout: "fitColumns",
        columns: [
            { title: "Country", field: "country", width: 200 },
            { title: "Visitor Count", field: "visitorCount", width: 150 },
            {
                title: "Flag",
                field: "flag",
                formatter: "image",
                formatterParams: { width: 195, height: 117 }
            }
        ]
    });
    table.on("rowClick", function(e, row){
        for(let i=0; i<data.length;++i){
            if(row.getData().country === data[i].country && row.getData().country ===data[i].country){
                console.log(data[i].id);
                window.open("/zadanie4/country.php?name="+ data[i].country);
                break;
            }
        }
    });

    // Create Leaflet Map with waypoints
    var map = L.map("osm-map");

// Add OpenStreetMap tile layer
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
        minZoom: 0,
        maxZoom: 18
    }).addTo(map);

// Get the coordinates of the first marker
    var firstMarker = waypoints[0];
    var initialLatLng = L.latLng(firstMarker.lat, firstMarker.lng);

// Create a LatLngBounds object to encompass all waypoints
    var bounds = L.latLngBounds(initialLatLng);

// Add markers for each waypoint
    for (var i = 0; i < waypoints.length; i++) {
        var waypoint = waypoints[i];
        var marker = L.marker([waypoint.lat, waypoint.lng]).addTo(map);

        // Extend the bounds to include the marker
        bounds.extend(marker.getLatLng());
    }

// Set the initial position and zoom level of the map based on the first marker
    map.setView(initialLatLng, 10); // Adjust the desired zoom level here

// Set a minimum zoom level to prevent zooming too far out
    map.setMinZoom(map.getBoundsZoom(bounds));

    document.getElementById("countries").style.width = "700px";
}

function drawIntervalsTable(data) {
    // Step 1: Extract relevant data
    const visitors = data.map(entry => ({
        userId: entry['user.id'],
        cityName: entry['city_name'],
        visitTime: new Date(entry['visit_time']),
        ip: entry['ip'],
    }));

    // Step 2: Define time intervals
    const intervals = [
        { label: '6:00-15:00', start: 6, end: 15 },
        { label: '15:00-21:00', start: 15, end: 21 },
        { label: '21:00-24:00', start: 21, end: 24 },
        { label: '24:00-6:00', start: 0, end: 6 },
    ];

    // Step 3: Filter visits in the last 24 hours and track unique visitors
    const uniqueVisitors = {};
    const filteredVisitors = visitors.filter(visitor => {
        const ip = visitor.ip;
        const visitTime = visitor.visitTime.getTime();

        if (!uniqueVisitors[ip] || uniqueVisitors[ip] > visitTime) {
            uniqueVisitors[ip] = visitTime;
            return true;
        }
        return false;
    });

    // Step 4: Count number of visitors in each interval
    const counts = {};
    filteredVisitors.forEach(visitor => {
        const hour = visitor.visitTime.getHours();
        intervals.forEach(interval => {
            if (hour >= interval.start && hour < interval.end) {
                if (!counts[interval.label]) counts[interval.label] = 0;
                counts[interval.label]++;
            }
        });
    });

    // Step 5: Display information using Tabulator.js
    const tableData = intervals.map(interval => ({
        interval: interval.label,
        visitors: counts[interval.label] || 0,
    }));

    const table = new Tabulator('#intervals', {
        data: tableData,
        columns: [
            { title: 'Time Interval', field: 'interval' },
            { title: 'Visitors', field: 'visitors' },
        ],
        layout: 'fitColumns',
    });
}

window.addEventListener("load", function() {
    fetch('get-data-all.php')
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            drawCountriesTable(data);
            drawIntervalsTable(data);
        });
}, false);
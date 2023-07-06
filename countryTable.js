var url = new URL(window.location.href);
var name = url.searchParams.get("name");

function drawCountryTable(data){
    var uniqueVisitors = {};
    var tableData = [];
    var waypoints = [];

    for (var i = 0; i < data.length; i++) {
        var ip = data[i].ip;
        var country = data[i].country;
        var city = data[i].city_name;
        var countryCode = data[i].code;
        var visitTime = new Date(data[i].visit_time);

        var currentTime = new Date();
        var timeDiff = currentTime - visitTime;
        var twentyFourHours = 24 * 60 * 60 * 1000;

        if (timeDiff <= twentyFourHours) {
            if (!uniqueVisitors[ip] || uniqueVisitors[ip] > visitTime) {
                uniqueVisitors[ip] = visitTime;

                // Increment the visitor count for the country
                if (!tableData[country]) {
                    tableData[country] = { count: 1, code: countryCode };
                } else {
                    tableData[country].count++;
                }

            }
        }
    }

    var tableDataArray = [];
    for (var city in tableData) {
        var row = {
            city: city,
            visitorCount: tableData[city].count,
        };
        tableDataArray.push(row);
    }

    var table = new Tabulator("#country", {
        data: tableDataArray,
        layout: "fitColumns",
        columns: [
            { title: "City", field: "city", width: 200 },
            { title: "Visitor Count", field: "visitorCount", width: 150 },

        ]
    });
}

window.addEventListener("load", function() {
    fetch('get-country-info.php?name=' + name)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            drawCountryTable(data);
        });
},false);
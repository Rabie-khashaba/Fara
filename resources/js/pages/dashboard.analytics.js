/**
 * Theme: Wander - Responsive Bootstrap 5 Admin Dashboard
 * Author: Techzaa
 * Module/App: Dashboard
 */
import ApexCharts from 'apexcharts/dist/apexcharts.min.js';

window.ApexCharts = ApexCharts;

import jsVectorMap from 'jsvectormap'
import 'jsvectormap/dist/maps/world-merc.js'
import 'jsvectormap/dist/maps/world.js'

const saudiCitiesMapData = window.saudiCitiesMap ?? {
    markers: [],
};

//
// Conversions
//
var options = {
    chart: {
        height: 292,
        type: 'radialBar',
    },
    plotOptions: {
        radialBar: {
            startAngle: -135,
            endAngle: 135,
            dataLabels: {
                name: {
                    fontSize: '14px',
                    color: "undefined",
                    offsetY: 100
                },
                value: {
                    offsetY: 55,
                    fontSize: '20px',
                    color: undefined,
                    formatter: function (val) {
                        return val + "%";
                    }
                }
            },
            track: {
                background: "rgba(170,184,197, 0.2)",
                margin: 0
            },
        }
    },
    fill: {
        gradient: {
            enabled: true,
            shade: 'dark',
            shadeIntensity: 0.2,
            inverseColors: false,
            opacityFrom: 1,
            opacityTo: 1,
            stops: [0, 50, 65, 91]
        },
    },
    stroke: {
        dashArray: 4
    },
    colors: ["#7f56da", "#22c55e"],
    series: [65.2],
    labels: ['Conversations rate'],
    responsive: [{
        breakpoint: 380,
        options: {
            chart: {
                height: 180
            }
        }
    }],
    grid: {
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
        }
    }
}

var chart = new ApexCharts(
    document.querySelector("#conversions"),
    options
);

chart.render();


//
//Performance-chart
//
const performanceChartData = window.dashboardAnalytics ?? {
    categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    users: [34, 65, 46, 68, 49, 61, 42, 44, 78, 52, 63, 67],
    posts: [8, 12, 7, 17, 21, 11, 5, 9, 7, 29, 12, 35],
};

var options = {
    series: [{
        name: "Users",
        type: "bar",
        data: performanceChartData.users,
    },
        {
            name: "Posts",
            type: "area",
            data: performanceChartData.posts,
        },
    ],
    chart: {
        height: 313,
        type: "line",
        toolbar: {
            show: false,
        },
    },
    stroke: {
        dashArray: [0, 0],
        width: [0, 2],
        curve: 'smooth'
    },
    fill: {
        opacity: [1, 1],
        type: ['solid', 'gradient'],
        gradient: {
            type: "vertical",
            inverseColors: false,
            opacityFrom: 0.5,
            opacityTo: 0,
            stops: [0, 90]
        },
    },
    markers: {
        size: [0, 0],
        strokeWidth: 2,
        hover: {
            size: 4,
        },
    },
    xaxis: {
        categories: performanceChartData.categories,
        axisTicks: {
            show: false,
        },
        axisBorder: {
            show: false,
        },
    },
    yaxis: {
        min: 0,
        axisBorder: {
            show: false,
        }
    },
    grid: {
        show: true,
        strokeDashArray: 3,
        xaxis: {
            lines: {
                show: false,
            },
        },
        yaxis: {
            lines: {
                show: true,
            },
        },
        padding: {
            top: 0,
            right: -2,
            bottom: 0,
            left: 10,
        },
    },
    legend: {
        show: true,
        horizontalAlign: "center",
        offsetX: 0,
        offsetY: 5,
        markers: {
            width: 9,
            height: 9,
            radius: 6,
        },
        itemMargin: {
            horizontal: 10,
            vertical: 0,
        },
    },
    plotOptions: {
        bar: {
            columnWidth: "30%",
            barHeight: "70%",
            borderRadius: 3,
        },
    },
    colors: ["#7f56da", "#22c55e"],
    tooltip: {
        shared: true,
        y: [{
            formatter: function (y) {
                if (typeof y !== "undefined") {
                    return y.toFixed(0);
                }
                return y;
            },
        },
            {
                formatter: function (y) {
                    if (typeof y !== "undefined") {
                        return y.toFixed(0);
                    }
                    return y;
                },
            },
        ],
    },
}

var chart = new ApexCharts(
    document.querySelector("#dash-performance-chart"),
    options
);

chart.render();


class VectorMap {
    initWorldMapMarker() {
        new jsVectorMap({
            map: 'world_merc',
            selector: '#world-map-markers',
            zoomOnScroll: true,
            zoomButtons: true,
            markersSelectable: false,
            panOnDrag: true,
            focusOn: {
                coords: [23.8859, 45.0792],
                scale: 7,
                animate: true,
            },
            markers: saudiCitiesMapData.markers,
            backgroundColor: 'transparent',
            markerStyle: {
                initial: {
                    fill: "#6f7f95",
                    stroke: "#dbe2ea",
                    strokeWidth: 5,
                    r: 7,
                },
                hover: {
                    fill: "#7f56da",
                    stroke: "#ffffff",
                    strokeWidth: 5,
                }
            },
            labels: {
                markers: {
                    render: marker => marker.name
                }
            },
            regionStyle: {
                initial: {
                    fill: '#d9dee5',
                    fillOpacity: 1,
                    stroke: '#ffffff',
                    strokeWidth: 0.75,
                },
                hover: {
                    fill: '#d1d7df',
                }
            },
        });
    }

    init() {
        this.initWorldMapMarker();
    }

}

document.addEventListener('DOMContentLoaded', function (e) {
    if (document.querySelector('#world-map-markers')) {
        new VectorMap().init();
    }
});

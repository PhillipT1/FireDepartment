// Incident Response Manager Chart.js initializations
document.addEventListener('DOMContentLoaded', function () {
    // Helper function for generating random colors for charts (optional)
    function getRandomColor() {
        var r = Math.floor(Math.random() * 255);
        var g = Math.floor(Math.random() * 255);
        var b = Math.floor(Math.random() * 255);
        return "rgb(" + r + "," + g + "," + b + ")";
    }

    function getStandardColors(count) {
        const baseColors = [
            'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)', 'rgba(83, 102, 89, 0.7)',
            'rgba(0, 203, 132, 0.7)', 'rgba(235, 54, 54, 0.7)' 
        ];
        let colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
        return colors;
    }


    // 1. Active Incidents by Status (Pie Chart)
    if (typeof irmActiveIncidentsData !== 'undefined') {
        const activeIncidentsCtx = document.getElementById('irmActiveIncidentsChart');
        if (activeIncidentsCtx) {
            new Chart(activeIncidentsCtx, {
                type: 'pie',
                data: {
                    labels: irmActiveIncidentsData.labels,
                    datasets: [{
                        label: irmActiveIncidentsData.title,
                        data: irmActiveIncidentsData.data,
                        backgroundColor: getStandardColors(irmActiveIncidentsData.data.length),
                        borderColor: getStandardColors(irmActiveIncidentsData.data.length).map(color => color.replace('0.7', '1')), // Make border opaque
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: irmActiveIncidentsData.title
                        }
                    }
                }
            });
        }
    }

    // 2. Vehicle Availability (Pie Chart)
    if (typeof irmVehicleStatusData !== 'undefined') {
        const vehicleStatusCtx = document.getElementById('irmVehicleStatusChart');
        if (vehicleStatusCtx) {
            new Chart(vehicleStatusCtx, {
                type: 'pie',
                data: {
                    labels: irmVehicleStatusData.labels,
                    datasets: [{
                        label: irmVehicleStatusData.title,
                        data: irmVehicleStatusData.data,
                        backgroundColor: getStandardColors(irmVehicleStatusData.data.length),
                        borderColor: getStandardColors(irmVehicleStatusData.data.length).map(color => color.replace('0.7', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: irmVehicleStatusData.title
                        }
                    }
                }
            });
        }
    }

    // 3. Equipment Availability (Pie Chart)
    if (typeof irmEquipmentStatusData !== 'undefined') {
        const equipmentStatusCtx = document.getElementById('irmEquipmentStatusChart');
        if (equipmentStatusCtx) {
            new Chart(equipmentStatusCtx, {
                type: 'pie',
                data: {
                    labels: irmEquipmentStatusData.labels,
                    datasets: [{
                        label: irmEquipmentStatusData.title,
                        data: irmEquipmentStatusData.data,
                        backgroundColor: getStandardColors(irmEquipmentStatusData.data.length),
                        borderColor: getStandardColors(irmEquipmentStatusData.data.length).map(color => color.replace('0.7', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: irmEquipmentStatusData.title
                        }
                    }
                }
            });
        }
    }

    // 4. Incidents in Last 7 Days (Line Chart)
    if (typeof irmIncidentsLast7DaysData !== 'undefined') {
        const incidentsLast7DaysCtx = document.getElementById('irmIncidentsLast7DaysChart');
        if (incidentsLast7DaysCtx) {
            new Chart(incidentsLast7DaysCtx, {
                type: 'line',
                data: {
                    labels: irmIncidentsLast7DaysData.labels,
                    datasets: [{
                        label: irmIncidentsLast7DaysData.datasetLabel || 'Incidents',
                        data: irmIncidentsLast7DaysData.data,
                        fill: false,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1 // Ensure y-axis shows whole numbers for counts
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true, // Can be false if only one dataset
                        },
                        title: {
                            display: true,
                            text: irmIncidentsLast7DaysData.title
                        }
                    }
                }
            });
        }
    }

});

function getTranslation(str) {
    return JSON.parse(document.getElementById('translations').getAttribute('content'))[str];
}

function getDataItem(name) {
    return JSON.parse(document.getElementById('data').getAttribute('content'))[name];
}

var ctx = "myChart";
new Chart(ctx, { // eslint-disable-line no-new
    type: 'bar',
    data: {
        labels: [
            getTranslation('saml_production'),
            getTranslation('saml_test'),
            getTranslation('oidc_production'),
            getTranslation('oidc_test')
        ],
        datasets: [{
            label: '',
            data: [
                getDataItem('samlProductionCount'),
                getDataItem('samlTestServicesCount'),
                getDataItem('oidcProductionCount'),
                getDataItem('oidcTestServicesCount')
            ],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)'
            ],
            borderColor: [
                'rgba(255,99,132,1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function (tooltipItem) {
                        return tooltipItem.yLabel;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function (value) {
                        if (Number.isInteger(value)) {
                            return value;
                        }
                    }
                }
            }
        }
    }
});

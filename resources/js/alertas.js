function cargarAlertas() {
    fetch('/api/alertas')
        .then(response => response.json())
        .then(alertas => {
            const alertaContainer = document.getElementById('alerta-container');
            if (alertas.length > 0) {
                let html = '<div class="alert alert-warning"><h4>Alertas de stock bajo:</h4><ul>';
                alertas.forEach(alerta => {
                    html += `<li>${alerta.item_description} en ${alerta.warehouse}: 
                             Stock actual: ${alerta.stock_available}, 
                             Stock mínimo: ${alerta.stock_minimo}</li>`;
                });
                html += '</ul></div>';
                alertaContainer.innerHTML = html;
            } else {
                alertaContainer.innerHTML = '';
            }
        });
}

// Cargar alertas cada 5 minutos
setInterval(cargarAlertas, 300000);

// Cargar alertas al cargar la página
document.addEventListener('DOMContentLoaded', cargarAlertas);

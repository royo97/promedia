</div> <!-- Cierre del container-fluid de main-content -->
    </main>

    <!-- Footer Admin -->
    <footer class="admin-footer bg-light border-top py-3" style="margin-left: 250px;">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <small class="text-muted">&copy; <?= date('Y') ?> Streaming Premium Admin Panel. Todos los derechos reservados.</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-muted">Versión 1.0.0 | Última actualización: <?= date('d/m/Y') ?></small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts esenciales -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Scripts personalizados -->
    <script>
        // Inicialización de componentes Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            // Tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            
            // Popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
            
            // Sidebar toggle para móviles
            const sidebar = document.querySelector('.sidebar');
            document.getElementById('sidebarToggle').addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                sidebar.classList.toggle('collapsed');
            });
            
            // Activar menús desplegables
            const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.map(dropdownToggleEl => {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });

        // Función para confirmar acciones importantes
        function confirmAction(message, callback) {
            if (confirm(message)) {
                if (typeof callback === 'function') {
                    callback();
                }
                return true;
            }
            return false;
        }

        // Mostrar notificaciones
        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.maxWidth = '400px';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(notification);
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    </script>
    
    <!-- Scripts adicionales específicos por página -->
    <?php if (isset($custom_scripts)): ?>
        <?php foreach ($custom_scripts as $script): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-dismiss flash messages after 5 seconds
        setTimeout(function() {
            var alertFlash = document.querySelector('.alert-flash');
            if (alertFlash) {
                var bsAlert = new bootstrap.Alert(alertFlash);
                bsAlert.close();
            }
        }, 5000);

        // Confirm before delete/critical actions
        function confirmAction(message) {
            return confirm(message || 'Are you sure you want to perform this action?');
        }

        // Format trailer number input to uppercase
        document.addEventListener('DOMContentLoaded', function() {
            var trailerInputs = document.querySelectorAll('input[name="trailer_number"]');
            trailerInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                });
            });
        });

        // Auto-refresh for real-time pages (optional)
        function enableAutoRefresh(seconds) {
            setTimeout(function() {
                location.reload();
            }, seconds * 1000);
        }
    </script>
</body>
</html>

@if (session('swal_alert'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.AppAlerts?.notify(@json(session('swal_alert')));
        });
    </script>
@endif

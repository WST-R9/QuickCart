<?php
function flashMessage() {
    if (!isset($_SESSION)) session_start();

    if (!empty($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $code = $_SESSION['code'] ?? 'info'; // fallback

        // Clear flash message
        unset($_SESSION['message'], $_SESSION['code']);
        ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        Toast.fire({
            icon: '<?php echo $code; ?>',
            title: '<?php echo addslashes($message); ?>'
        });
        </script>
        <?php
    }
}
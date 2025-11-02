    </main>

    <footer class="bg-white border-t mt-auto py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                &copy; <?= date('Y') ?> CREODENT CADCAM Work Management System v<?= $config['app']['version'] ?? '1.0.0' ?>
            </p>
        </div>
    </footer>

    <script>
        // Global utilities
        function showNotification(message, type = 'success') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // AJAX helper with CSRF token
        async function apiRequest(url, method = 'GET', data = null) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            if (data) {
                data.csrf_token = window.APP_CONFIG.csrf_token;
                options.body = JSON.stringify(data);
            }

            const response = await fetch(url, options);
            return response.json();
        }
    </script>
</body>
</html>

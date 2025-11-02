</main>

<!-- Footer -->
<footer class="bg-white border-t mt-12">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-gray-500 text-sm">
            &copy; <?= date('Y') ?> CREODENT Integrated Web Operations System. All rights reserved.
        </p>
    </div>
</footer>

<!-- Global JavaScript -->
<script>
    // CSRF token for AJAX requests
    const csrfToken = '<?= csrfToken() ?>';

    // Helper function for AJAX requests
    async function apiRequest(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            return { success: false, error: error.message };
        }
    }

    // Show notification
    function showNotification(message, type = 'success') {
        const color = type === 'success' ? 'green' : 'red';
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 bg-${color}-50 border-l-4 border-${color}-400 p-4 rounded shadow-lg z-50`;
        notification.innerHTML = `
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm text-${color}-700">${message}</p>
                </div>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Handle optimistic locking conflicts
    function handleVersionConflict() {
        if (confirm('This record was updated by another user. Do you want to refresh the page to see the latest version?')) {
            window.location.reload();
        }
    }
</script>

</body>
</html>

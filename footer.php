    <footer class="app-footer">
        <p>&copy; <?php echo date("Y"); ?> Deadline Hub</p>
    </footer>
</div>
<script>
    (function () {
        var root = document.documentElement;
        var button = document.getElementById('themeToggle');
        var label = document.getElementById('themeToggleLabel');
        var savedTheme = localStorage.getItem('theme') || 'light';

        function applyTheme(theme) {
            root.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            if (button) {
                button.setAttribute('data-current-theme', theme);
            }
            if (label) {
                label.textContent = theme === 'dark' ? label.dataset.darkLabel : label.dataset.lightLabel;
            }
        }

        applyTheme(savedTheme);

        if (button) {
            button.addEventListener('click', function () {
                var nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(nextTheme);
            });
        }
    })();
</script>
</body>
</html>

<script>
    (() => {
        try {
            const theme = localStorage.getItem('brewgo-theme') === 'light' ? 'light' : 'dark';
            document.documentElement.dataset.theme = theme;
            document.documentElement.dataset.bsTheme = theme;
        } catch (error) {
            document.documentElement.dataset.theme = 'dark';
            document.documentElement.dataset.bsTheme = 'dark';
        }
    })();
</script>

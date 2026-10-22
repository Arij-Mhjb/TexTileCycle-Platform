<meta name="color-scheme" content="light dark">
<script>
    (() => {
        const storedTheme = localStorage.getItem('textilecycle-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', storedTheme ? storedTheme === 'dark' : prefersDark);
    })();
</script>

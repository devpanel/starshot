<script>
  import ProjectBrowser from './ProjectBrowser.svelte';

  // Removes initial loader if it exists.
  const initialLoader = document.getElementById('initial-loader');
  if (initialLoader) {
    initialLoader.remove();
  }
</script>

<ProjectBrowser />

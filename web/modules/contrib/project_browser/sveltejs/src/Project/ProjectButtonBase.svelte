<script>
  // eslint-disable-next-line import/prefer-default-export
  export let click = () => {};
</script>

<button on:click={click} class="project__action_button" {...$$restProps}>
  <slot />
</button>

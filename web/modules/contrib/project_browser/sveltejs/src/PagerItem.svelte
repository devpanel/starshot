<script>
  import { createEventDispatcher, getContext } from 'svelte';

  const dispatch = createEventDispatcher();
  const stateContext = getContext('state');
  const { Drupal } = window;

  export let itemTypes = [];
  export let linkTypes = [];
  export let label = '';
  export let toPage = 0;
  export let ariaLabel = null;
  export let isCurrent = false;

  function onChange(event, selectedPage) {
    const state = stateContext.getState();
    const detail = {
      originalEvent: event,
      page: selectedPage,
      pageIndex: 0,
      pageSize: state.pageSize,
    };
    dispatch('pageChange', detail);
    if (detail.preventDefault !== true) {
      stateContext.setPage(detail.page, detail.pageIndex);
    }
  }
</script>

<li
  class={`pager__item ${itemTypes
    .map((item) => `pager__item--${item}`)
    .join(' ')}`}
  class:pager__item--active={isCurrent}
>
  <a
    href="#pb-sort"
    class={`pager__link ${linkTypes
      .map((item) => `pager__link--${item}`)
      .join(' ')}`}
    class:is-active={isCurrent}
    aria-label={ariaLabel || Drupal.t('@location page', { '@location': label })}
    on:click={(e) => onChange(e, toPage)}
    aria-current={isCurrent ? 'page' : null}
  >
    {label}
  </a>
</li>

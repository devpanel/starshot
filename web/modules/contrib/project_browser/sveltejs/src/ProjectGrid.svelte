<script context="module">
  import Search from './Search/Search.svelte';
  import Loading from './Loading.svelte';
  import { pageSize, mediaQueryValues } from './stores';

  export { Search };
</script>

<script>
  import { setContext } from 'svelte';

  const { Drupal } = window;

  export let loading = false;
  export let page = 0;
  export let pageIndex = 0;
  export let toggleView;
  export let rows;
  export let labels = {
    empty: Drupal.t('No modules found'),
    loading: Drupal.t('Loading data'),
  };

  let mqMatches;
  mediaQueryValues.subscribe((mqlMap) => {
    mqMatches = mqlMap.get('(min-width: 1200px)');
  });

  $: isDesktop = mqMatches;
  $: filteredRows = rows;
  $: visibleRows = filteredRows
    ? filteredRows.slice(pageIndex, pageIndex + $pageSize)
    : [];

  setContext('state', {
    getState: () => ({
      page,
      pageIndex,
      pageSize,
      rows,
      filteredRows,
    }),
    setPage: (_page, _pageIndex) => {
      page = _page;
      pageIndex = _pageIndex;
    },
    setRows: (_rows) => {
      filteredRows = _rows;
    },
  });
</script>

<!--Aligns Category filter and Grid cards side by side-->
<slot name="head" />
<div class="pb-layout">
  <aside class="pb-layout__aside">
    <slot name="left" />
  </aside>
  <div class="pb-layout__main">
    {#if loading}
      <Loading />
    {:else if visibleRows.length === 0}
      <div>{@html labels.empty}</div>
    {:else}
      <ul
        class="pb-projects-{isDesktop ? toggleView.toLowerCase() : 'list'}"
        aria-label={Drupal.t('Projects')}
      >
        <slot rows={visibleRows} />
      </ul>
    {/if}
    <slot name="foot" />
  </div>
</div>

<slot name="bottom" />

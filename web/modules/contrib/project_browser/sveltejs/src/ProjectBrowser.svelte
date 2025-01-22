<script>
  import { onMount } from 'svelte';
  import { withPrevious } from 'svelte-previous';
  import ProjectGrid, { Search } from './ProjectGrid.svelte';
  import Pagination from './Pagination.svelte';
  import Project from './Project/Project.svelte';
  import { numberFormatter } from './util';
  import ProcessQueueButton from './ProcessQueueButton.svelte';
  import {
    filters,
    rowsCount,
    moduleCategoryFilter,
    page,
    sort,
    focusedElement,
    searchString,
    activeTab,
    categoryCheckedTrack,
    sortCriteria,
    preferredView,
    pageSize,
    updated,
    queueList,
  } from './stores';
  import MediaQuery from './MediaQuery.svelte';
  import {
    DEFAULT_SOURCE_ID,
    BASE_URL,
    FULL_MODULE_PATH,
    ACTIVE_PLUGIN,
    PACKAGE_MANAGER,
    MAX_SELECTIONS,
  } from './constants';
  // cspell:ignore tabwise

  const { Drupal } = window;
  const { announce } = Drupal;

  let data;
  let rows = [];
  let sources = [];
  let dataArray = [];
  let projectId = null;
  const pageIndex = 0; // first row

  let loading = true;
  let sortText = $sortCriteria.find((option) => option.id === $sort).text;
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let searchText;
  searchString.subscribe((value) => {
    searchText = value;
  });
  let toggleView = 'Grid';
  preferredView.subscribe((value) => {
    toggleView = value;
  });
  const [currentPage, previousPage] = withPrevious(0);
  $: $currentPage = $page;
  let element = '';
  focusedElement.subscribe((value) => {
    element = value;
  });
  let searchComponent;

  function hasItemsInQueue(tabId) {
    let hasItems = false;
    queueList.subscribe((currentList) => {
      hasItems = currentList[tabId] && currentList[tabId].length > 0;
    })();
    return hasItems;
  }

  /**
   * Load data from Drupal.org API.
   *
   * @param {number|string} _page
   *   The page number.
   *
   * @return {Promise<void>}
   *   Empty promise that resolves on content load.*
   */
  async function load(_page) {
    loading = true;
    const searchParams = new URLSearchParams({
      page: _page,
      limit: $pageSize,
      sort: $sort,
      source: $activeTab,
    });
    if (searchText) {
      searchParams.set('search', searchText);
    }
    if ($moduleCategoryFilter && $moduleCategoryFilter.length) {
      searchParams.set('categories', $moduleCategoryFilter);
      $categoryCheckedTrack[$activeTab] = $moduleCategoryFilter;
    } else {
      // If no category filter is set, reset the tracking for the active tab.
      $categoryCheckedTrack[$activeTab] = [];
    }
    if ('developmentStatus' in $filters) {
      searchParams.set(
        'development_status',
        Number($filters.developmentStatus).toString(),
      );
    }
    if ('maintenanceStatus' in $filters) {
      searchParams.set(
        'maintenance_status',
        Number($filters.maintenanceStatus).toString(),
      );
    }
    if ('securityCoverage' in $filters) {
      searchParams.set(
        'security_advisory_coverage',
        Number($filters.securityCoverage).toString(),
      );
    }
    if (Object.keys($categoryCheckedTrack).length !== 0) {
      searchParams.set(
        'tabwise_categories',
        JSON.stringify($categoryCheckedTrack),
      );
    }

    const url = `${BASE_URL}project-browser/data/project?${searchParams.toString()}`;

    const res = await fetch(url);
    if (res.ok) {
      const messenger = new Drupal.Message();
      data = await res.json();
      // A list of the available sources to get project data.
      sources = Object.keys(data);
      dataArray = Object.values(data);
      rows = data[$activeTab].list;
      $rowsCount = data[$activeTab].totalResults;

      if (data[$activeTab].error && data[$activeTab].error.length) {
        messenger.add(data[$activeTab].error, { type: 'error' });
      }

      if (
        PACKAGE_MANAGER.available &&
        (PACKAGE_MANAGER.errors.length || PACKAGE_MANAGER.warnings.length)
      ) {
        if (PACKAGE_MANAGER.errors.length) {
          PACKAGE_MANAGER.errors.forEach((e) => {
            messenger.add(`Unable to download modules via the UI: ${e}`, {
              type: 'error',
            });
          });
        }

        if (PACKAGE_MANAGER.warnings.length) {
          PACKAGE_MANAGER.warnings.forEach((e) => {
            messenger.add(
              `There may be issues which effect downloading modules: ${e}`,
              { type: 'warning' },
            );
          });
        }
      }
    } else {
      rows = [];
      $rowsCount = 0;
    }
    loading = false;
  }

  /**
   * Load remote data when the Svelte component is mounted.
   */
  onMount(async () => {
    if (MAX_SELECTIONS === 1) {
      $queueList = {};
    }
    const savedPageSize = localStorage.getItem('pageSize');
    if (savedPageSize) {
      pageSize.set(Number(savedPageSize));
    }

    $activeTab = DEFAULT_SOURCE_ID;
    // The project ID, if there is one, will be the last thing in the URL
    // path, and we can reasonably expect it to be different than the
    // source plugin ID.
    projectId = window.location.pathname.substring(1).split('/').pop();
    if (projectId === $activeTab) {
      projectId = null;
    }

    await load($page);
    const focus = element ? document.getElementById(element) : false;
    if (focus) {
      focus.focus();
      $focusedElement = '';
    }
  });

  function onPageChange(event) {
    const activePages = document.querySelectorAll(
      `[aria-label="Page ${$page + 1}"]`,
    );
    if (activePages) {
      const activePage = activePages[0];
      activePage.focus();
    }
    page.set(event.detail.page);
    load($page);
  }

  function onPageSizeChange() {
    page.set(0);
    localStorage.setItem('pageSize', $pageSize);
    load($page);
  }

  async function onSearch(event) {
    searchText = event.detail.searchText;
    await load(0);
    page.set(0);
  }

  async function onSelectCategory(event) {
    moduleCategoryFilter.set(event.detail.category);
    await load(0);
    page.set(0);
  }
  async function onSort(event) {
    sort.set(event.detail.sort);
    sortText = $sortCriteria.find((option) => option.id === $sort).text;
    await load(0);
    page.set(0);
  }
  async function onAdvancedFilter() {
    await load(0);
    page.set(0);
  }

  async function onToggle(val) {
    if (val !== toggleView) toggleView = val;
    preferredView.set(val);
  }

  /**
   * Refreshes the live region after a filter or search completes.
   */
  const refreshLiveRegion = () => {
    if ($rowsCount) {
      // Set announce() to an empty string. This ensures the result count will
      // be announced after filtering even if the count is the same.
      announce('');

      // The announcement is delayed by 210 milliseconds, a wait that is
      // slightly longer than the 200 millisecond debounce() built into
      // announce(). This ensures that the above call to reset the aria live
      // region to an empty string actually takes place instead of being
      // debounced.
      setTimeout(() => {
        announce(
          Drupal.t('@count Results for @active_tab, Sorted by @sortText', {
            '@count': numberFormatter.format($rowsCount),
            '@sortText': sortText,
            '@active_tab': ACTIVE_PLUGIN,
          }),
        );
      }, 210);
    }
  };

  document.onmouseover = function setInnerDocClickTrue() {
    window.innerDocClick = true;
  };

  document.onmouseleave = function setInnerDocClickFalse() {
    window.innerDocClick = false;
  };

  // Handles back button functionality to go back to the previous page the user was on before.
  window.addEventListener('popstate', () => {
    // Confirm the popstate event was a back button action by checking that
    // the user clicked out of the document.
    if (!window.innerDocClick) {
      page.set($previousPage);
      load($page);
    }
  });

  window.onload = { onSearch };
  // Removes initial loader if it exists.
  const initialLoader = document.getElementById('initial-loader');
  if (initialLoader) {
    initialLoader.remove();
  }
</script>

<MediaQuery query="(min-width: 1200px)" let:matches>
  <ProjectGrid {toggleView} {loading} {rows} {pageIndex} {$pageSize} let:rows>
    <div slot="head">
      <Search
        bind:this={searchComponent}
        on:search={onSearch}
        on:sort={onSort}
        on:advancedFilter={onAdvancedFilter}
        on:selectCategory={onSelectCategory}
        {searchText}
        {refreshLiveRegion}
      />

      <div class="pb-layout__header">
        <div class="pb-search-results">
          {#each dataArray as dataValue}
            {#if $activeTab === dataValue.pluginId}
              {Drupal.formatPlural(
                $rowsCount,
                `${numberFormatter.format(1)} Result`,
                `${numberFormatter.format($rowsCount)} Results`,
              )}
            {/if}
          {/each}
        </div>

        {#if matches}
          <div class="pb-display">
            <button
              class:pb-display__button--selected={toggleView === 'List'}
              class="pb-display__button pb-display__button--first"
              value="List"
              aria-pressed={toggleView === 'List'}
              on:click={(e) => {
                toggleView = 'List';
                onToggle(e.target.value);
              }}
            >
              <img
                class="pb-display__button-icon project-browser__list-icon"
                src="{FULL_MODULE_PATH}/images/list.svg"
                alt=""
              />
              {Drupal.t('List')}
            </button>
            <button
              class:pb-display__button--selected={toggleView === 'Grid'}
              class="pb-display__button pb-display__button--last"
              value="Grid"
              aria-pressed={toggleView === 'Grid'}
              on:click={(e) => {
                toggleView = 'Grid';
                onToggle(e.target.value);
              }}
            >
              <img
                class="pb-display__button-icon project-browser__grid-icon"
                src="{FULL_MODULE_PATH}/images/grid-fill.svg"
                alt=""
              />
              {Drupal.t('Grid')}
            </button>
          </div>
        {/if}
      </div>
    </div>
    {#key $updated}
      {#each rows as row, index (row)}
        <Project {toggleView} project={row} />
      {/each}
      {#if PACKAGE_MANAGER.available && hasItemsInQueue($activeTab) && (MAX_SELECTIONS === null || MAX_SELECTIONS > 1)}
        <ProcessQueueButton />
      {/if}
    {/key}
    <div slot="bottom">
      <Pagination
        page={$page}
        count={$rowsCount}
        on:pageChange={onPageChange}
        on:pageSizeChange={onPageSizeChange}
      />
    </div>
  </ProjectGrid>
</MediaQuery>

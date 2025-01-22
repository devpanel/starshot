<script>
  import { createEventDispatcher, getContext } from 'svelte';
  import FilterApplied from './FilterApplied.svelte';
  import BooleanFilter from './BooleanFilter.svelte';
  import MultipleChoiceFilter from '../MultipleChoiceFilter.svelte';
  import SearchSort from './SearchSort.svelte';
  import {
    filters,
    moduleCategoryFilter,
    categoryCheckedTrack,
    moduleCategoryVocabularies,
    sort,
    searchString,
    sortCriteria,
  } from '../stores';
  import { FULL_MODULE_PATH, DARK_COLOR_SCHEME, FILTERS } from '../constants';

  const { Drupal } = window;
  const dispatch = createEventDispatcher();
  const stateContext = getContext('state');

  export let refreshLiveRegion;
  export const filter = (row, text) =>
    Object.values(row).filter(
      (item) =>
        item && item.toString().toLowerCase().indexOf(text.toLowerCase()) > 1,
    ).length > 0;
  export let index = -1;
  export let searchText;
  searchString.subscribe((value) => {
    searchText = value;
  });

  let sortMatch = $sortCriteria.find((option) => option.id === $sort);
  if (typeof sortMatch === 'undefined') {
    $sort = $sortCriteria[0].id;
    sortMatch = $sortCriteria.find((option) => option.id === $sort);
  }
  let sortText = sortMatch.text;
  let filterComponent;

  export async function onSearch(event) {
    const state = stateContext.getState();
    const detail = {
      originalEvent: event,
      filter,
      index,
      searchText,
      page: state.page,
      pageIndex: state.pageIndex,
      pageSize: state.pageSize,
      rows: state.filteredRows,
    };
    dispatch('search', detail);
    if (filterComponent) {
      filterComponent.setModuleCategoryVocabulary();
    }
    if (detail.preventDefault !== true) {
      if (detail.searchText.length === 0) {
        stateContext.setRows(state.rows);
      } else {
        stateContext.setRows(
          detail.rows.filter((r) => detail.filter(r, detail.searchText, index)),
        );
      }
      stateContext.setPage(0, 0);
    } else {
      stateContext.setRows(detail.rows);
    }
    refreshLiveRegion();
  }

  const onAdvancedFilter = async (event) => {
    if (event) {
      const filterName = event.target.name;

      if (FILTERS[filterName]._type === 'boolean') {
        $filters[filterName] = event.target.value === 'true';
      } else {
        $filters[filterName] = event.target.value;
      }
    }

    const state = stateContext.getState();
    const detail = {
      originalEvent: event,
      page: state.page,
      pageIndex: state.pageIndex,
      pageSize: state.pageSize,
      rows: state.filteredRows,
    };
    dispatch('advancedFilter', detail);
    stateContext.setPage(0, 0);
    stateContext.setRows(detail.rows);
    refreshLiveRegion();
  };

  function onSelectCategory(event) {
    const state = stateContext.getState();
    const detail = {
      originalEvent: event,
      category: $moduleCategoryFilter,
      page: state.page,
      pageIndex: state.pageIndex,
      pageSize: state.pageSize,
      rows: state.filteredRows,
    };
    dispatch('selectCategory', detail);
    stateContext.setPage(0, 0);
    stateContext.setRows(detail.rows);
  }

  function clearText() {
    $searchString = '';
    onSearch();
    document.getElementById('pb-text').focus();
  }

  const filterDefinitions = Object.entries(FILTERS);

  /**
   * Resets the filters to the initial values provided by the source.
   *
   * @param {boolean} clear
   *   Whether to clear all filter values (i.e., not reset them to their defaults,
   *   but actually negate them all).
   */
  const resetFilters = (clear) => {
    $filters = {};
    filterDefinitions.forEach(([name, definition]) => {
      let value;
      if (clear) {
        if (definition._type === 'boolean') {
          value = false;
        } else if (definition._type === 'multiple_choice') {
          value = [];
        }
      } else {
        value = definition.value;
      }
      $filters[name] = value;
    });
    $moduleCategoryFilter = [];
    $categoryCheckedTrack = {};
    onAdvancedFilter();
    onSelectCategory();
  };
</script>

<form class="search__form-container">
  <div
    class="search__bar-container search__form-item js-form-item form-item js-form-type-textfield form-type--textfield"
    role="search"
  >
    <label for="pb-text" class="form-item__label">{Drupal.t('Search')}</label>
    <div class="search__search-bar">
      <input
        class="search__search_term form-text form-element form-element--type-text"
        type="search"
        id="pb-text"
        name="text"
        bind:value={$searchString}
        on:keydown={(e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            onSearch(e);
          }
          if (e.key === 'Escape') {
            e.preventDefault();
            clearText();
          }
        }}
      />
      {#if $searchString}
        <button
          class="search__search-clear"
          id="clear-text"
          type="button"
          on:click={clearText}
          aria-label={Drupal.t('Clear search text')}
          tabindex="-1"
        >
          <img
            src="{FULL_MODULE_PATH}/images/cross{DARK_COLOR_SCHEME
              ? '--dark-color-scheme'
              : ''}.svg"
            alt=""
          />
        </button>
      {/if}
      <button
        class="search__search-submit"
        type="button"
        on:click={onSearch}
        aria-label={Drupal.t('Search')}
      >
        <img
          class="search__search-icon"
          id="search-icon"
          src="{FULL_MODULE_PATH}/images/search-icon{DARK_COLOR_SCHEME
            ? '--dark-color-scheme'
            : ''}.svg"
          alt=""
        />
      </button>
    </div>
  </div>
  {#if filterDefinitions.length !== 0}
    <div class="search__form-filters-container">
      <div class="search__form-filters">
        {#each filterDefinitions as [filterType, filter]}
          {#if filter._type === 'boolean'}
            <BooleanFilter
              name={filter.name}
              type={filterType}
              onLabel={filter.on_label}
              offLabel={filter.off_label}
              changeHandler={onAdvancedFilter}
            />
          {:else if filter._type === 'multiple_choice'}
            <MultipleChoiceFilter
              on:selectCategory={onSelectCategory}
              bind:this={filterComponent}
            />
          {/if}
        {/each}
      </div>
      <div
        class="search__form-sort js-form-item js-form-type-select form-type--select js-form-item-type form-item--type"
      >
        <section
          class="search__filters"
          aria-label={Drupal.t('Search results')}
        >
          <div class="search__results-count">
            {#each $moduleCategoryFilter as category}
              <FilterApplied
                label={$moduleCategoryVocabularies[category]}
                clickHandler={() => {
                  $moduleCategoryFilter.splice(
                    $moduleCategoryFilter.indexOf(category),
                    1,
                  );
                  $moduleCategoryFilter = $moduleCategoryFilter;
                  onSelectCategory();
                }}
              />
            {/each}

            {#if $filters.securityCoverage || $filters.maintenanceStatus || $filters.developmentStatus || $moduleCategoryFilter.length}
              <button
                class="search__filter-button"
                type="button"
                on:click|preventDefault={() => resetFilters(true)}
              >
                {Drupal.t('Clear filters')}
              </button>
            {/if}
            {#if !($filters.maintenanceStatus && $filters.securityCoverage && !$filters.developmentStatus && $moduleCategoryFilter.length === 0)}
              <button
                class="search__filter-button"
                type="button"
                on:click|preventDefault={() => resetFilters()}
              >
                {Drupal.t('Recommended filters')}
              </button>
            {/if}
          </div>
        </section>
        <SearchSort on:sort bind:sortText refresh={refreshLiveRegion} />
      </div>
    </div>
  {/if}
</form>

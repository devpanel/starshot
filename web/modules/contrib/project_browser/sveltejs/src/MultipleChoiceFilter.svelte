<script>
  import { createEventDispatcher, getContext, onMount } from 'svelte';
  import {
    moduleCategoryFilter,
    moduleCategoryVocabularies,
    activeTab,
  } from './stores';
  import { normalizeOptions, shallowCompare } from './util';
  import { BASE_URL, FILTERS } from './constants';

  const { Drupal } = window;
  const dispatch = createEventDispatcher();
  const stateContext = getContext('state');

  let filterVisible = false;
  let lastFocusedCheckbox = null;

  function showHideFilter() {
    filterVisible = !filterVisible;
    const dropdownItems = document.querySelector(
      '.pb-filter__multi-dropdown__items',
    );
    if (filterVisible) {
      dropdownItems.classList.add('pb-filter__multi-dropdown__items--visible');
    } else {
      dropdownItems.classList.remove(
        'pb-filter__multi-dropdown__items--visible',
      );
    }
    setTimeout(() => {
      // Ensure focus stays on the last focused checkbox
      if (lastFocusedCheckbox) {
        lastFocusedCheckbox.focus();
      } else {
        document.getElementsByClassName('pb-filter__checkbox')[0].focus();
      }
    }, 50);
  }

  function onBlur(event) {
    if (
      event.relatedTarget === null ||
      !document
        .getElementsByClassName('pb-filter__multi-dropdown')[0]
        .contains(event.relatedTarget)
    ) {
      filterVisible = false;
      const dropdownItems = document.querySelector(
        '.pb-filter__multi-dropdown__items',
      );
      dropdownItems.classList.remove(
        'pb-filter__multi-dropdown__items--visible',
      );
    }
  }

  function onKeyDown(event) {
    const checkboxes = document.querySelectorAll('.pb-filter__checkbox');
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault();
      return;
    }
    // Space to open category filter drop-down.
    if (
      event.key === ' ' &&
      event.target.classList.contains('pb-filter__multi-dropdown')
    ) {
      showHideFilter();
      event.preventDefault();
      return;
    }
    // Alt Up/Down opens/closes category filter drop-down.
    if (
      event.altKey &&
      (event.key === 'ArrowDown' || event.key === 'ArrowUp')
    ) {
      showHideFilter();
      event.preventDefault();
      return;
    }
    // Prevent tabbing out when the filter is expanded.
    if (event.key === 'Tab' && filterVisible) {
      event.preventDefault();
      return;
    }
    // Down arrow on checkbox moves to next checkbox or wraps around.
    if (
      event.target.classList.contains('pb-filter__checkbox') &&
      event.key === 'ArrowDown'
    ) {
      const nextElement =
        event.target.parentElement.parentElement.nextElementSibling;
      if (nextElement) {
        nextElement.firstElementChild.focus();
      } else {
        // Wrap to the first item
        checkboxes[0].focus();
      }
      event.preventDefault();
      return;
    }

    // Up arrow on checkbox moves to previous checkbox or wraps around.
    if (
      event.target.classList.contains('pb-filter__checkbox') &&
      event.key === 'ArrowUp'
    ) {
      const prevElement =
        event.target.parentElement.parentElement.previousElementSibling;
      if (prevElement) {
        prevElement.firstElementChild.focus();
      } else {
        // Wrap to the last item
        checkboxes[checkboxes.length - 1].focus();
      }
      event.preventDefault();
      return;
    }
    // Prevent dropdown collapse when moving focus with the arrow key.
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
    }
    // Tab moves off filter.
    if (event.key === 'Tab') {
      if (event.shiftKey) {
        // Shift+tab moves to search box.
        document.getElementById('pb-text').focus();
        event.preventDefault();
        return;
      }
      // Tab without shift moves to next filter.
      const keys = Object.keys(FILTERS);
      const filterMap = Object.fromEntries(Object.entries(FILTERS));

      const indexOfCategories = keys.indexOf('categories');
      if (indexOfCategories !== -1 && indexOfCategories + 1 < keys.length) {
        const nextKey = keys[indexOfCategories + 1];
        const nextElement = FILTERS[nextKey];
        const nextElementKey = Object.keys(filterMap).find(
          (key) => filterMap[key] === nextElement,
        );
        document.getElementsByName(nextElementKey)[0].focus();
        event.preventDefault();
      }
      return;
    }

    // Escape closes filter drop-down.
    if (
      event.target.classList.contains('pb-filter__checkbox') &&
      event.key === 'Escape'
    ) {
      filterVisible = false;
      document.getElementsByClassName('pb-filter__multi-dropdown')[0].focus();
      const dropdownItems = document.querySelector(
        '.pb-filter__multi-dropdown__items',
      );
      dropdownItems.classList.remove(
        'pb-filter__multi-dropdown__items--visible',
      );
    }
  }

  async function onSelectCategory(event) {
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
    filterVisible = true;
    const dropdownItems = document.querySelector(
      '.pb-filter__multi-dropdown__items',
    );
    dropdownItems.classList.add('pb-filter__multi-dropdown__items--visible');
    if (event.target.classList.contains('pb-filter__checkbox')) {
      lastFocusedCheckbox = event.target;
      setTimeout(() => {
        lastFocusedCheckbox.focus();
      }, 50);
    }
  }

  async function fetchAllCategories() {
    const response = await fetch(`${BASE_URL}project-browser/data/categories`);
    if (response.ok) {
      return response.json();
    }
    return [];
  }

  const apiModuleCategory = fetchAllCategories();
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export async function setModuleCategoryVocabulary() {
    apiModuleCategory.then((value) => {
      const normalizedValue = normalizeOptions(value[$activeTab]);
      const storedValue = $moduleCategoryVocabularies;
      if (
        storedValue === null ||
        !shallowCompare(normalizedValue, storedValue)
      ) {
        moduleCategoryVocabularies.set(normalizedValue);
      }
    });
  }
  onMount(async () => {
    await setModuleCategoryVocabulary();
  });
</script>

<div class="filter-group__filter-options form-item">
  <label for="pb-text" class="form-item__label"
    >{Drupal.t('Filter by category')}</label
  >
  {#await apiModuleCategory then categoryList}
    <div
      role="group"
      tabindex="0"
      class="pb-filter__multi-dropdown form-element form-element--type-select"
      on:click={() => {
        showHideFilter();
      }}
      on:blur={onBlur}
      on:keydown={onKeyDown}
    >
      <span class="pb-filter__multi-dropdown__label">
        {#if $moduleCategoryFilter.length > 0}
          {$moduleCategoryFilter.length === 1
            ? `${$moduleCategoryFilter.length} category selected`
            : `${$moduleCategoryFilter.length} categories selected`}
        {:else}
          {Drupal.t('Select categories')}
        {/if}
      </span>
      <div
        class="pb-filter__multi-dropdown__items
      pb-filter__multi-dropdown__items--{filterVisible ? 'visible' : 'hidden'}"
      >
        {#each categoryList[$activeTab] as dt}
          <div>
            <label for={dt.id}>
              <input
                type="checkbox"
                id={dt.id}
                class="pb-filter__checkbox form-checkbox form-boolean form-boolean--type-checkbox"
                bind:group={$moduleCategoryFilter}
                on:change={onSelectCategory}
                on:blur={onBlur}
                on:keydown={onKeyDown}
                value={dt.id}
              />
              {dt.name}
            </label>
          </div>
        {/each}
      </div>
    </div>
  {/await}
</div>

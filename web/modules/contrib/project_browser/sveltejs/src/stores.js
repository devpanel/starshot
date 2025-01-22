// eslint-disable-next-line import/no-extraneous-dependencies
import { writable } from 'svelte/store';

import {
  DEFAULT_SOURCE_ID, SORT_OPTIONS, FILTERS,
} from './constants';

// Store the selected tab.
const storedActiveTab = DEFAULT_SOURCE_ID;

// Store for applied advanced filters.
const defaultFilters = {};
Object.entries(FILTERS).forEach(([name, definition]) => {
  defaultFilters[name] = definition.value;
});
export const filters = writable(defaultFilters);

export const rowsCount = writable(0);

// Store for applied category filters.
const storedModuleCategoryFilter = [];
export const moduleCategoryFilter = writable(storedModuleCategoryFilter);

// Store for module category vocabularies.
const storedModuleCategoryVocabularies = JSON.parse(localStorage.getItem('moduleCategoryVocabularies')) || {};
export const moduleCategoryVocabularies = writable(storedModuleCategoryVocabularies);
moduleCategoryVocabularies.subscribe((val) => localStorage.setItem('moduleCategoryVocabularies', JSON.stringify(val)));

// Store the page the user is on.
const storedPage = 0;
export const page = writable(storedPage);

export const activeTab = writable(storedActiveTab);

// Store the current sort selected.
const storedSort = SORT_OPTIONS[0].id;
export const sort = writable(storedSort);

// Store tab-wise checked categories.
const storedCategoryCheckedTrack = {};
export const categoryCheckedTrack = writable(storedCategoryCheckedTrack);

// Store the element that was last focused.
const storedFocus = '';
export const focusedElement = writable(storedFocus);

// Store the search string.
const storedSearchString = '';
export const searchString = writable(storedSearchString);

// Store for sort criteria.
const storedSortCriteria = SORT_OPTIONS;
export const sortCriteria = writable(storedSortCriteria);

// Store the selected toggle view.
const storedPreferredView = 'Grid';
export const preferredView = writable(storedPreferredView);

// Store the selected page size.
const storedPageSize = 12;
export const pageSize = writable(storedPageSize);

// Store the value of media queries.
export const mediaQueryValues = writable(new Map());

export const updated = writable(0);

// Store for the queue list.
const storedQueueList = {};
export const queueList = writable(storedQueueList);

export function addToQueue(tabId, project) {
  queueList.update((currentList) => {
    if (!currentList[tabId]) {
      currentList[tabId] = [];
    }
    currentList[tabId].push(project);
    return currentList;
  });
}

export function removeFromQueue(tabId, projectId) {
  queueList.update((currentList) => {
    if (currentList[tabId]) {
      currentList[tabId] = currentList[tabId].filter(
        (item) => item.id !== projectId,
      );
    }
    return currentList;
  });
}

export function clearQueueForTab(tabId) {
  queueList.update((currentList) => {
    currentList[tabId] = [];
    return currentList;
  });
}

export const SORT_OPTIONS = drupalSettings.project_browser.sort_options;
export const DEFAULT_SOURCE_ID =
  drupalSettings.project_browser.default_plugin_id;
export const BASE_URL = `${window.location.protocol}//${window.location.host}${drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix}`;
export const FULL_MODULE_PATH = `${BASE_URL}${drupalSettings.project_browser.module_path}`;
export const DARK_COLOR_SCHEME =
  matchMedia('(forced-colors: active)').matches &&
  matchMedia('(prefers-color-scheme: dark)').matches;
export const ACTIVE_PLUGIN = drupalSettings.project_browser.active_plugin;
export const PACKAGE_MANAGER = drupalSettings.project_browser.package_manager;
export const FILTERS = drupalSettings.project_browser.filters || {};
export const MAX_SELECTIONS = drupalSettings.project_browser.max_selections;

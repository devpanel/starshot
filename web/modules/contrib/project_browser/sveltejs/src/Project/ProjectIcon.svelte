<script>
  import { FULL_MODULE_PATH, DARK_COLOR_SCHEME } from '../constants';

  const { Drupal } = window;
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let type = '';
  export let variant = false;
  export let classes = false;

  const typeToImg = {
    status: {
      path: 'blue-security-shield-icon',
      alt: Drupal.t('Security Note'),
      title: Drupal.t(
        'Stable releases for this project are covered by the security advisory policy.',
      ),
    },
    usage: {
      path: 'project-usage-icon',
      alt: Drupal.t('Project Usage'),
      title: Drupal.t('Shows the number of sites that use this module.'),
    },
    compatible: {
      path: 'compatible-icon',
      alt: Drupal.t('Compatible'),
      title: Drupal.t('This module is compatible with your version of Drupal.'),
    },
    maintained: {
      path: 'green-maintained-wrench-icon',
      alt: Drupal.t('Well maintained'),
      title: Drupal.t('This module is actively maintained by maintainers.'),
    },
    installed: {
      path: 'installed-check-icon',
      alt: Drupal.t('Installed'),
      title: Drupal.t('This module is installed.'),
    },
  };
</script>

{#if type === 'installed'}
  <span class="pb-project__status-icon-span" title={typeToImg[type].title}>
    <img
      src="{FULL_MODULE_PATH}/images/{typeToImg[type].path}{DARK_COLOR_SCHEME
        ? '--dark-color-scheme'
        : ''}.svg"
      class={`pb-icon pb-icon--${variant} pb-icon--${type} ${classes}`}
      alt
    />
  </span>
{:else}
  <button class="pb-project__status-icon-btn" title={typeToImg[type].title}>
    <img
      src="{FULL_MODULE_PATH}/images/{typeToImg[type].path}{DARK_COLOR_SCHEME
        ? '--dark-color-scheme'
        : ''}.svg"
      class={`pb-icon pb-icon--${variant} pb-icon--${type} ${classes}`}
      alt={typeToImg[type].alt}
    />
  </button>
{/if}

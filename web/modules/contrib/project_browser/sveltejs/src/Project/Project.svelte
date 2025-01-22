<script>
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let project;
  export let toggleView;
  import ProjectButtonBase from './ProjectButtonBase.svelte';
  import { openPopup } from '../popup';
  import DetailModal from '../DetailModal.svelte';
  import ActionButton from './ActionButton.svelte';
  import Image from './Image.svelte';
  import Categories from './Categories.svelte';
  import ProjectIcon from './ProjectIcon.svelte';
  import { numberFormatter } from '../util';
  import { focusedElement, mediaQueryValues } from '../stores';
  import { FULL_MODULE_PATH } from '../constants';

  const { Drupal } = window;

  let mqMatches;
  $: isDesktop = mqMatches;
  $: displayMode = isDesktop ? toggleView.toLowerCase() : 'list';
  mediaQueryValues.subscribe((mqlMap) => {
    mqMatches = mqlMap.get('(min-width: 1200px)');
  });
</script>

<li class="pb-project pb-project--{displayMode}">
  <div class="pb-project__logo pb-project__logo--{displayMode}">
    <Image sources={project.logo} class="pb-project__logo-image" />
  </div>
  <div class="pb-project__main pb-project__main--{displayMode}">
    <h3
      on:click={() => {
        $focusedElement = `${project.project_machine_name}_title`;
      }}
      class="pb-project__title pb-project__title--{displayMode}"
    >
      <ProjectButtonBase
        id="{project.project_machine_name}_title"
        class="pb-project__link"
        aria-haspopup="dialog"
        click={() => {
          const modalDialog = document.createElement('div');
          (() =>
            new DetailModal({
              target: modalDialog,
              props: { project },
            }))();
          openPopup(modalDialog, project);
        }}
      >
        {project.title}
      </ProjectButtonBase>
    </h3>
    <div class="pb-project__body pb-project__body--{displayMode}">
      {@html project.body.summary}
    </div>
    <Categories {toggleView} moduleCategories={project.module_categories} />
  </div>
  <div
    class="pb-project__icons pb-project__icons--{displayMode}"
    class:warnings={project.warnings && project.warnings.length > 0}
  >
    {#if project.is_covered}
      <span class="pb-project__status-icon">
        <ProjectIcon type="status" />
        <!-- Show the security policy description if it is accompanied by warnings,
             since those also have descriptions.  -->
        {#if project.warnings && project.warnings.length > 0}
          <small>{Drupal.t('Covered by the security advisory policy')}</small>
        {/if}
      </span>
    {/if}
    {#if project.is_maintained}
      <span class="pb-project__maintenance-icon">
        <ProjectIcon type="maintained" />
      </span>
    {/if}
    {#if toggleView === 'Grid' && typeof project.project_usage_total === 'number' && project.project_usage_total > 0}
      <div class="pb-project__install-count-container">
        <span class="pb-project__install-count">
          {Drupal.formatPlural(
            project.project_usage_total,
            `${numberFormatter.format(1)} install`,
            `${numberFormatter.format(project.project_usage_total)} installs`,
          )}
        </span>
      </div>
    {/if}
    {#if project.warnings && project.warnings.length > 0}
      {#each project.warnings as warning}
        <span class="pb-project__status-icon">
          <img src="{FULL_MODULE_PATH}/images/triangle-alert.svg" alt="" />
          <small>{@html warning}</small>
        </span>
      {/each}
    {/if}
    {#if toggleView === 'List' && typeof project.project_usage_total === 'number' && project.project_usage_total > 0}
      <div class="pb-project__project-usage-container">
        <div class="pb-project__image pb-project__image--{displayMode}">
          <ProjectIcon type="usage" variant="project-listing" />
        </div>
        <div class="pb-project__active-installs-text">
          {Drupal.formatPlural(
            project.project_usage_total,
            `${numberFormatter.format(1)} Active Install`,
            `${numberFormatter.format(
              project.project_usage_total,
            )} Active Installs`,
          )}
        </div>
      </div>
    {/if}
    <!--If there are no warnings, there is space to include the action button
        in the icons container -->
    {#if !project.warnings || project.warnings.length === 0}
      <ActionButton {project} />
    {/if}
  </div>
  <!--If there are warnings, the action button needs to be moved out of the
      icons container to provide space for the warning descriptions. -->
  {#if project.warnings && project.warnings.length > 0}
    <ActionButton {project} />
  {/if}
</li>

<script>
  import { get } from 'svelte/store';
  import { queueList, activeTab, updated } from './stores';
  import { processQueue } from './QueueProcessor';
  import Loading from './Loading.svelte';
  import LoadingEllipsis from './Project/LoadingEllipsis.svelte';

  let loading = false;

  const { Drupal } = window;

  const currentQueueList = get(queueList)[get(activeTab)] || [];
  const queueLength = Object.keys(currentQueueList).length;

  const handleClick = async () => {
    loading = true;
    await processQueue();
    loading = false;
    $updated = new Date().getTime();
  };
</script>

<button
  class="project__action_button project__action_button--fixed"
  on:click={handleClick}
  disabled={loading}
>
  {#if loading}
    <Loading />
    <LoadingEllipsis
      message={Drupal.formatPlural(
        queueLength,
        'Installing 1 project',
        'Installing @count projects',
      )}
    />
  {:else}
    {Drupal.t('Install selected projects')}
  {/if}
</button>

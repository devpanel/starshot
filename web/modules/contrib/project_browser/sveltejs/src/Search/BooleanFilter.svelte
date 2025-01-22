<script>
  import { filters } from '../stores';

  export let name;
  export let changeHandler;
  export let type;
  export let onLabel;
  export let offLabel;
</script>

<div class="filter-group__filter-options form-item">
  <label for={type} class="form-item__label">{name}</label>
  <select
    name={type}
    class="search__filter-select form-select form-element form-element--type-select"
    bind:value={$filters[type]}
    on:change={changeHandler}
  >
    <option value={true}>{onLabel}</option>
    <option value={false}>{offLabel}</option>
  </select>
</div>

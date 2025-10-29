{if isset($listas) && $listas|@count > 0}
  <div class="ho-listas-enlaces">
    {foreach from=$listas item=lista}
      <div class="ho-lista-bloque" role="region" aria-labelledby="ho-lista-{$lista.nombre|escape:'htmlall':'UTF-8'}">
        <h4 id="ho-lista-{$lista.nombre|escape:'htmlall':'UTF-8'}">{$lista.nombre|escape:'htmlall':'UTF-8'}</h4>
        <ul class="ho-lista-enlaces">
          {foreach from=$lista.enlaces item=enlace}
            <li>
              <a href="{$enlace.url|escape:'htmlall':'UTF-8'}" 
                 title="{$enlace.nombre|escape:'htmlall':'UTF-8'}"
                 rel="noopener noreferrer">
                 {$enlace.nombre|escape:'htmlall':'UTF-8'}
              </a>
            </li>
          {/foreach}
        </ul>
      </div>
    {/foreach}
  </div>
{else}
  <p>{$smarty.const.LANG_NO_LISTS|default:'No hay listas disponibles.'}</p>
{/if}
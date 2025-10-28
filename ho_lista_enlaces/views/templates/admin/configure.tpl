{*
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{* Panel en el que mostraremos las listas de enlaces *}
{* Las agruparemos segun el hook al que estén enganchadas *}
<div class="panel">
	<h3>Listas de enlaces existentes</h3>

  <div id="contenedorBloques">
    {if $listasPorHook|@count > 0}
      {foreach from=$listasPorHook key=hook item=listas}
        <div id="hook_{$hook}" class="hook-block" style="">
          <h4>Hook: {$hook}</h4>
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre del bloque</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
                {foreach from=$listas item=lista}
                  <tr>
                    <td>{$lista.id_lista}</td>
                    <td>{$lista.nombre}</td>
                    <td>
                      <a href="{$link->getAdminLink('AdminModules')}&configure={$module_name}&id_lista={$lista.id_lista}&accion=edit&token={$token}"
                        class="btn btn-default btn-sm">
                        Editar
                      </a>
                      <a href="{$link->getAdminLink('AdminModules')}&configure={$module_name}&id_lista={$lista.id_lista}&accion=delete&token={$token}" 
                        class="btn btn-danger btn-sm"
                        onclick="confirm('¿Seguro que desea eliminar esta lista?');">
                        Eliminar
                      </a>
                    </td>
                  </tr>
                {/foreach}
            </tbody>
          </table>
        </div>
      {/foreach}
    {else}
      <p>No hay listas creadas todavía.</p>
    {/if}
  </div>
</div>


/**
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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

$(document).ready(function() {
  // ===== Funcionalidad boton Añadir enlace =====
  let contador = 0; // Contador para los nombres de los inputs

  $('#addCustomLink').on('click', function() {
    contador++;

    // Crear un bloque de inputs
    const html = `
      <div class="custom-link-block" style="margin: 10px 0 10px 0;">
        <input type="text" name="HO_LISTA_ENLACES_CUSTOM_NAME_NEW[${contador}]" placeholder="Nombre del enlace" class="form-control" style="width:45%; display:inline-block; margin-right:5px;">
        <input type="text" name="HO_LISTA_ENLACES_CUSTOM_URL_NEW[${contador}]" placeholder="URL" class="form-control" style="width:45%; display:inline-block; margin-right:5px;">
        <button type="button" class="btn btn-danger eliminarEnlace">Eliminar</button>
      </div>
    `;

    $('#addCustomLink').before(html);
  });

  // Eliminar enlace dinámico
  $(document).on('click', '.eliminarEnlace', function() {
      $(this).closest('.custom-link-block').remove();
  });

  // ===== Desplegar / contraer secciones =====
  $(document).on('click', '.ho-collapsible-header', function() {
    const parent = $(this).closest('.ho-collapsible');
    const content = parent.find('.ho-collapsible-content');
    content.slideToggle(200);
    parent.toggleClass('open');
  });

  // --- Marcar seleccionados si estaban guardados ---
  const valoresGuardados = {
    CMS: JSON.parse($('input[name="HO_LISTA_ENLACES_URL_CMS_hidden"]').val() || '[]'),
    PRODUCTOS: JSON.parse($('input[name="HO_LISTA_ENLACES_URL_PRODUCTOS_hidden"]').val() || '[]'),
    CATEGORIAS: JSON.parse($('input[name="HO_LISTA_ENLACES_URL_CATEGORIAS_hidden"]').val() || '[]'),
    ESTATICOS: JSON.parse($('input[name="HO_LISTA_ENLACES_URL_ESTATICOS_hidden"]').val() || '[]')
  };

  $.each(valoresGuardados, function(grupo, valores) {
    valores.forEach(function(v) {
      $('input[name="HO_LISTA_ENLACES_URL_' + grupo + '[]"][value="' + v + '"]').prop('checked', true);
    });
  });

});

<?php
/**
* 2007-2025 PrestaShop
*
* AVISO DE LICENCIA
*
* Este archivo está sujeto a la Licencia Académica Gratuita (AFL 3.0)
* que se incluye con este paquete en el archivo LICENSE.txt.
* También está disponible a través de la web en:
* http://opensource.org/licenses/afl-3.0.php
* Si no recibió una copia de la licencia y no puede obtenerla
* a través de la web, envíe un correo a license@prestashop.com
* y se le enviará una copia de inmediato.
*
* DESCARGO DE RESPONSABILIDAD
*
* No edite ni agregue a este archivo si desea actualizar PrestaShop
* a versiones futuras. Si desea personalizar PrestaShop para sus
* necesidades, consulte http://www.prestashop.com para más información.
*
*  @autor    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @licencia   http://opensource.org/licenses/afl-3.0.php  Licencia Académica Gratuita (AFL 3.0)
*  Marca registrada e internacional propiedad de PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
  exit;
}

 // Extiende HelperForm y declara la propiedad para evitar el warning
class HelperFormExtended extends HelperForm {
  public $class;
}

class Ho_lista_enlaces extends Module {
  protected $config_form = false;

  public function __construct() {
    $this->name = 'ho_lista_enlaces';
    $this->tab = 'front_office_features';
    $this->version = '1.0.0';
    $this->author = 'Xurxo';
    $this->need_instance = 0;

    /**
    * Establece $this->bootstrap a true si tu módulo es compatible con bootstrap (PrestaShop 1.6)
    */
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('hoListaEnlaces');
    $this->description = $this->l('Da mayor visibilidad a tu contenido y páginas estáticas para aumentar el interés de tus visitantes.');

    $this->confirmUninstall = $this->l('¿Seguro que desea desinstalar el módulo?');

    $this->ps_versions_compliancy = array('min' => '8.2', 'max' => '9.0');
  }

  /**
  * No olvides crear métodos de actualización si es necesario:
  * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
  */
  public function install() {
    if (!parent::install()) {
      return false;
    }

    // Ejecutar SQL
    if (!include(dirname(__FILE__).'/sql/install.php')) {
      return false;
    }

    // Hooks básicos del BO y assets
    $this->registerHook('header');
    $this->registerHook('displayBackOfficeHeader');

    // === Registrar TODOS los hooks display (creando los que falten) ===
    $hooks = Db::getInstance()->executeS('
      SELECT name FROM `'._DB_PREFIX_.'hook`
      WHERE name LIKE "display%" 
      AND name NOT LIKE "%Admin%" 
      AND name NOT LIKE "%BackOffice%"
    ');

    $hooksRegistrados = array_column($hooks, 'name');

    // Hooks más comunes que pueden no existir aún
    $hooksExtra = [
      'displayFooter', 'displayHome', 'displayTop', 'displayNav1', 'displayNav2',
      'displayLeftColumn', 'displayRightColumn', 'displayFooterBefore',
      'displayFooterAfter', 'displayProductExtraContent', 'displayReassurance',
      'displayCustomerAccount', 'displayMyAccountBlock'
    ];

    foreach ($hooksExtra as $hookName) {
      if (!in_array($hookName, $hooksRegistrados)) {
        // Si el hook no existe, lo creamos
        Db::getInstance()->insert('hook', ['name' => pSQL($hookName), 'title' => pSQL($hookName)]);
        $hooksRegistrados[] = $hookName;
      }
    }

    // Registrar el módulo en todos los hooks válidos
    foreach ($hooksRegistrados as $hook) {
      $this->registerHook($hook);
    }

    return true;
  }

  public function uninstall() {
    include(dirname(__FILE__).'/sql/uninstall.php');

    return parent::uninstall();
  }

  /**
   * Recuperar las listas de la base de datos
   */
  private function getAllLists() {
    $sql = 'SELECT * FROM `'._DB_PREFIX_.'lista_enlaces` ORDER BY `id_lista` ASC';
    return Db::getInstance()->executeS($sql);
  }

  /**
  * Elimina una lista y sus relaciones
  *
  * @param int $idLista ID de la lista a eliminar
  * @return bool true si se eliminó correctamente, false en caso contrario
  */
  private function deleteList($idLista) {
    if (!$idLista) {
      return false;
    }

    // Eliminar la lista, las relaciones se eliminan automáticamente gracias a ON DELETE CASCADE
    $deleted = Db::getInstance()->delete('lista_enlaces', 'id_lista = '.(int)$idLista);

    if ($deleted) {
      // Mensaje de confirmación en BO
      $this->context->controller->confirmations[] = $this->l('Lista eliminada correctamente.');
    } else {
      // Mensaje de error
      $this->context->controller->errors[] = $this->l('No se pudo eliminar la lista.');
    }

    return $deleted;
  }

  /**
  * Carga los datos de una lista para edición y los inyecta en Configuration
  *
  * @param int $idLista
  * @return bool
  */
  private function editarLista($idLista){
    if (!$idLista) {
      $this->context->controller->errors[] = $this->l('ID de lista no válido.');
      return false;
    }

    // Cargar lista
    $lista = Db::getInstance()->getRow('
      SELECT * FROM `'._DB_PREFIX_.'lista_enlaces`
      WHERE `id_lista` = '.(int)$idLista
    );

    // Cargar los enlaces de la lista
    $enlacesSeleccionados = Db::getInstance()->executeS('
      SELECT e.id_enlace, e.nombre, e.url, e.personalizado
      FROM `'._DB_PREFIX_.'enlace` e
      INNER JOIN `'._DB_PREFIX_.'lista_enlace_relacion` rel
        ON e.id_enlace = rel.id_enlace
      WHERE rel.id_lista = '.(int)$idLista.'
    ');

    // Separar ulrs normales / personalizadas
    $urlsSeleccionadas = [];
    $idsEnlaces = [];
    $enlacesPersonalizados = [];

    foreach ($enlacesSeleccionados as $enlace){
      $idsEnlaces[] = $enlace['id_enlace'];
      $urlsSeleccionadas[] = $enlace['url'];

      if ((bool)$enlace['personalizado']){
        $enlacesPersonalizados[] = [
          'id_enlace' => (int)$enlace['id_enlace'],
          'url' => $enlace['url'],
          'nombre' => $enlace['nombre']
        ];
      }
    }

    // Guardamos los datos en configuración temporal (para que el formulario se precargue)
    Configuration::updateValue('HO_LISTA_ENLACES_ID_LISTA', (int)$lista['id_lista']);
    Configuration::updateValue('HO_LISTA_ENLACES_NOMBRE_BLOQUE', pSQL($lista['nombre']));
    Configuration::updateValue('HO_LISTA_ENLACES_HOOK', pSQL($lista['hook']));
    Configuration::updateValue('HO_LISTA_ENLACES_IDS', implode(',', $idsEnlaces));
    Configuration::updateValue('HO_LISTA_ENLACES_URLS_SELECCIONADAS', json_encode($urlsSeleccionadas));
    Configuration::updateValue('HO_LISTA_ENLACES_PERSONALIZADOS', json_encode($enlacesPersonalizados));

    $this->context->controller->confirmations[] =
      sprintf($this->l('Editando la lista "%s". Modifique los valores y guarde para aplicar los cambios.'), $lista['nombre']);

    return true;
  }

  /**
  * Recivir a chamada de AJAX para eliminar enlaces personalizados
  */
  public function eliminarEnlacePersonalizadoAJAX(){
    // Limpiar cualquier salida previa
    @ob_end_clean();
    ob_start();
    header('Content-Type: application/json');

    $id_enlace = (int)Tools::getValue('id_enlace');

    if(!$id_enlace){
      die(json_encode([
        'success' => false,
        'message' => 'ID de enlace no válido'
      ]));
    }

    // si existen, elimina las relaciones
    Db::getInstance()->delete('lista_enlace_relacion', 'id_enlace = '.(int)$id_enlace);

    // Luego elimina el enlace en sí
    $eliminado = Db::getInstance()->delete('enlace', 'id_enlace = '.(int)$id_enlace);

    if($eliminado){
      die(json_encode([
        'success' => true,
        'message' => 'Enlace eliminado correctamente'
      ]));
    }else{
      die(json_encode([
        'success' => false,
        'message' => 'Error al eliminar el enlace de la base de datos'
      ]));
    }

    echo json_encode([
      'succes' => (bool)$eliminado,
      'message' => $eliminado ? 'Enlace eliminado correctamente' : 'Error al eliminar el enlace'
    ]);

    exit;
  }

  /**
  * Limpiar los valores temporales del formulario
  */
  private function limpiarFormulario(){
    // Array con las claves de configuración y sus valores "vacios"
    $campos = [
      'HO_LISTA_ENLACES_ID_LISTA' => 0,
      'HO_LISTA_ENLACES_NOMBRE_BLOQUE' => '',
      'HO_LISTA_ENLACES_HOOK' => '',
      'HO_LISTA_ENLACES_URL_SELECCIONADAS' => json_encode([]),
      'HO_LISTA_ENLACES_IDS' => '',
      'HO_LISTA_ENLACES_PERSONALIZADOS' => json_encode([]),
    ];

    // Recorremos y limpiamos cada campo
    foreach($campos as $clave => $valor){
      Configuration::updateValue($clave, $valor);
    }
  }

  /**
  * Carga el formulario de configuración
  */
  public function getContent() {
    // Eliminar enlaces personalizados del formulario al cargar la página
    $personalizados = json_decode(Configuration::get('HO_LISTA_ENLACES_PERSONALZIADOS', []), true);

    if(!empty($personalizados)){
      foreach($personalizados as $enlace){
        // Buscar el id_enlace en la tabla 'enlace' usando una URL y nombre
      }
    }

    $idLista = (int)Tools::getValue('id_lista');
    $accion = Tools::getValue('accion');
    $token = Tools::getValue('token');
    $tokenEsperado = Tools::getAdminTokenLite('AdminModules');

    // Si no se está haciendo ninguna acción, limpiar el formulario
    if (!$accion && !Tools::isSubmit('submitHo_lista_enlacesModule')) {
      $this->limpiarFormulario();
    }

    // Validación básica del token (solo si hay acción)
    if($accion && $token !== $tokenEsperado){
      $this->context->controller->errors[] = $this->l('Token de seguridad inválido');
    }

    // === Acción: eliminar ===
    if ($accion === 'delete' && $idLista && $token === $tokenEsperado) {
      $this->deleteList($idLista);

      // Limpiar configuración temporal después de eliminar
      $this->limpiarFormulario();
    }

    // === Acción: editar ===
    if($accion === 'edit' && $idLista && $token === $tokenEsperado){
      $this->editarLista($idLista);
    }

    // Procesar formulario (crear/actualizar)
    if (((bool)Tools::isSubmit('submitHo_lista_enlacesModule')) == true) {
      $this->postProcess();
    }

    // Cargar formulario
    $formulario = $this->renderForm();

    // Cargar listas desde la base de datos
    $listas = $this->getAllLists();

    // Agrupar listas por hook
    $listasPorHook = [];
    foreach ($listas as $lista) {
      $hook = $lista['hook'] ?: 'sin_hook';
      if (!isset($listasPorHook[$hook])) {
        $listasPorHook[$hook] = [];
      }
      $listasPorHook[$hook][] = $lista;
    }

    // Asignar variables a Smarty
    $this->context->smarty->assign([
      'module_dir' => $this->_path,
      'listasPorHook' => $listasPorHook,
      'link' => $this->context->link,
      'module_name' => $this->name,
      'token' => $tokenEsperado, 
    ]);

    // Cargar la plantilla
    $plantilla = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

    return $formulario.$plantilla;
  }

  /**
  * Crea el formulario que se mostrará en la configuración del módulo
  */
  protected function renderForm() {
    $helper = new HelperFormExtended();

    $helper->show_toolbar = false;
    $helper->table = $this->table;
    $helper->module = $this;
    $helper->default_form_language = $this->context->language->id;
    $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

    $helper->identifier = $this->identifier;
    $helper->submit_action = 'submitHo_lista_enlacesModule';
    $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
      .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');

    $helper->tpl_vars = array(
      'fields_value' => $this->getConfigFormValues(), /* Agrega los valores para tus inputs */
      'languages' => $this->context->controller->getLanguages(),
      'id_language' => $this->context->language->id,
    );

    // Luego, en renderForm(), instancias la clase extendida
    $helper->id = 'ho_lista_enlaces_form';
    $helper->class = 'ho_lista_enlaces_form_class';

    return $helper->generateForm(array($this->getConfigForm()));
  }

  // =====================================================
  // Obtener los hooks del servidor de la parte del front
  // =====================================================
  private function getHooksFront() {
    $frontHooks = Db::getInstance()->executeS('
      SELECT name 
      FROM '._DB_PREFIX_.'hook 
      WHERE name LIKE "display%" 
        AND name NOT LIKE "%Admin%" 
        AND name NOT LIKE "%BackOffice%" 
      ORDER BY name ASC
    ');

    $hooksArray = [];
    foreach ($frontHooks as $hook) {
      $hooksArray[] = ['id_option' => $hook['name'], 'name' => $hook['name']];
    }

    return $hooksArray;
  }

  // ============================================
  // Obtener enlaces de las páginas CMS
  // ============================================
  private function getEnlacesCms() {
    $link = $this->context->link;
    $cmsPaginas = CMS::listCms($this->context->language->id, false, true);
    $cmsArray = [];

    foreach ($cmsPaginas as $cms) {
      $cmsArray[] = [
        'id_option' => $link->getCMSLink($cms['id_cms']),
        'name' => $cms['meta_title']
      ];
    }

    return $cmsArray;
  }

  // ============================================
  // Obtener las categorias
  // ============================================
  private function getEnlacesCategorias() {
    $link = $this->context->link;
    $categorias = Category::getCategories($this->context->language->id, true, false);
    $categoriaArray = [];

    foreach ($categorias as $categoria) {
      $categoriaArray[] = [
        'id_option' => $link->getCategoryLink($categoria['id_category']),
        'name' => $categoria['name']
      ];
    }

    return $categoriaArray;
  }

  // ============================================
  // Crear productos destacados o manuales
  // ============================================
  private function getEnlaceProductos() {
    $link = $this->context->link;

    // Parametros para coger los productos
    $idLang = $this->context->language->id; // ID del idioma
    $start = 0; // Índice inicial para paginar
    $limit = 50; // Número máximo de productos a devolver
    $orderBy = 'name'; // Campo por el que ordenar (name, price, etc.)
    $orderWay = 'ASC'; // ASC -> ascendente o DESC -> descendente

    $productos = Product::getProducts($idLang, $start, $limit, $orderBy, $orderWay); // por ejemplo top 50
    $productoArray = [];

    foreach ($productos as $producto) {
      $productoArray[] = [
        'id_option' => $link->getProductLink($producto['id_product']),
        'name' => $producto['name']
      ];
    }

    return $productoArray;
  }

  // ============================================
  // Obtener los enlaces "báscicos"
  // ============================================
  private function getEnlacesBasicos(){
    $link = $this->context->link;
    return [
      ['id_option' => $link->getPageLink('index'), 'name' => $this->l('Inicio')],
      ['id_option' => $link->getPageLink('contact'), 'name' => $this->l('Contacto')],
      ['id_option' => $link->getPageLink('sitemap'), 'name' => $this->l('Mapa del sitio')],
    ];
  }

  // ============================================
  // Agrupar los enlaces por seccion
  // ============================================
  private function getEnlacesAgrupados() {
    return [
      'cms' => [
        'titulo' => $this->l('Páginas de contenido'),
        'enlaces' => $this->getEnlacesCms(),
      ],
      'productos' => [
        'titulo' => $this->l('Páginas de producto'),
        'enlaces' => $this->getEnlaceProductos(),
      ],
      'categorias' => [
        'titulo' => $this->l('Categorías'),
        'enlaces' => $this->getEnlacesCategorias(),
      ],
      'estaticos' => [
        'titulo' => $this->l('Contenido estático'),
        'enlaces' => $this->getEnlacesBasicos(),
      ],
    ];
  } 

  /**
  * Crea la estructura de tu formulario
  */
  protected function getConfigForm() {
    $grupos = $this->getEnlacesAgrupados();

    $inputs = [
      [
        'col' => 3,
        'type' => 'text',
        'label' => $this->l('Nombre del bloque'),
        'name' => 'HO_LISTA_ENLACES_NOMBRE_BLOQUE',
      ],
      [
        'col' => 5,
        'type' => 'select',
        'label' => $this->l('Hook'),
        'name' => 'HO_LISTA_ENLACES_HOOK',
        'options' => [
          'query' => array_merge(
              [['id_option' => '', 'name' => $this->l('Selecciona un hook')]],
              $this->getHooksFront()
          ),
          'id' => 'id_option',
          'name' => 'name',
        ],
      ],
      [
      'type' => 'hidden',
      'name' => 'HO_LISTA_ENLACES_ID_LISTA',
      ],
    ];

    // Secciones con listas deplegables
    foreach ($grupos as $key => $grupo) {
    $checkbox_html = '
      <div class="ho-collapsible">
        <div class="ho-collapsible-header">
          <strong>' . $grupo['titulo'] . '</strong> <span class="toggle-icon">+</span>
        </div>
        <div class="ho-collapsible-content" style="display:none; margin-left:15px;">
    ';

    $seleccionados = (array)json_decode(Configuration::get('HO_LISTA_ENLACES_URL_SELECCIONADAS', '[]'), true);

    foreach ($grupo['enlaces'] as $enlace) {
      $checked = in_array($enlace['id_option'], $seleccionados) ? 'checked' : '';
      $checkbox_html .= '
        <div>
          <label>
            <input type="checkbox" 
                  name="HO_LISTA_ENLACES_URL_' . strtoupper($key) . '[]" 
                  value="' . $enlace['id_option'] . '"
                  class="ho-checkbox-' . $key . '" ' . $checked . '> 
            ' . $enlace['name'] . '
          </label>
        </div>';
    }

    $checkbox_html .= '</div></div>';

    $inputs[] = [
        'type' => 'html',
        'name' => 'checkbox_group_' . $key,
        'html_content' => $checkbox_html,
    ];
    }

    // Recuperar los enlaces personalizados guardados
    $personalizados = json_decode(Configuration::get('HO_LISTA_ENLACES_PERSONALIZADOS', '[]'), true);

    // Crear HTML del contenedor y precargar enlaces existentes
    $custom_html = '<button type="button" id="addCustomLink" class="btn btn-default" style="margin-top:10px;">'.$this->l('Añadir enlace personalizado').'</button>';
    $custom_html .= '<div id="customLinksContainer" style="margin-top:10px;">';

    foreach ($personalizados as $index => $enlace) {
      $custom_html .= '<div class="custom-link-item" style="width: 90%; display: flex; gap: 5px; margin-bottom:10px;">
        <input type="text" name="HO_LISTA_ENLACES_CUSTOM_NAME_NEW[]" value="'.htmlspecialchars($enlace['nombre']).'" placeholder="'.$this->l('Nombre').'">
        <input type="text" name="HO_LISTA_ENLACES_CUSTOM_URL_NEW[]" value="'.htmlspecialchars($enlace['url']).'" placeholder="URL">
        <button type="button" 
          data-id-enlace="'.(int)$enlace['id_enlace'].'" 
          class="eliminarEnlaceBD btn btn-danger btn-sm">
          Eliminar
        </button>
      </div>';
    }

    $custom_html .= '</div>';

    // Añadir al formulario
    $inputs[] = [
      'type' => 'html',
      'name' => 'add_custom_link_button',
      'html_content' => $custom_html,
    ];

    // ========== DEVOLVER EL FORMULARIO COMPLETO ==========
    return [
      'form' => [
          'legend' => [
            'title' => $this->l('Configurar lista de enlaces'),
            'icon' => 'icon-cogs',
          ],
          'input' => $inputs,
          'button' => ['algo' => $this->l('Añadir enlace personalizado')],
          'submit' => ['title' => $this->l('Guardar')],
        ],
    ];
  }

  /**
  * Establece los valores de los inputs
  */
  protected function getConfigFormValues() {
    $custom = json_decode(Configuration::get('HO_LISTA_ENLACES_CUSTOM', '{}'), true) ?: [];

    return [
      'HO_LISTA_ENLACES_HOOK' => Configuration::get('HO_LISTA_ENLACES_HOOK', null) ?? '',
      'HO_LISTA_ENLACES_NOMBRE_BLOQUE' => Configuration::get('HO_LISTA_ENLACES_NOMBRE_BLOQUE', null)  ?? '',
      'HO_LISTA_ENLACES_URL_CMS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_CMS', '[]'), true),
      'HO_LISTA_ENLACES_URL_PRODUCTOS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_PRODUCTOS', '[]'), true),
      'HO_LISTA_ENLACES_URL_CATEGORIAS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_CATEGORIAS', '[]'), true),
      'HO_LISTA_ENLACES_URL_ESTATICOS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_ESTATICOS', '[]'), true),
      'HO_LISTA_ENLACES_CUSTOM_URL' => $custom['url'] ?? '',
      'HO_LISTA_ENLACES_CUSTOM_NAME' => $custom['name'] ?? '',
      'HO_LISTA_ENLACES_IDS' => explode(',', Configuration::get('HO_LISTA_ENLACES_IDS', '')),
      'HO_LISTA_ENLACES_ID_LISTA' => Configuration::get('HO_LISTA_ENLACES_ID_LISTA', 0),
    ];
  }

  /**
  * Devuelve el nombre del enlace según la URL y el grupo
  */
  private function getTitleFromUrl($grupo, $url) {
    $gruposEnlaces = $this->getEnlacesAgrupados(); // ya tienes este método
    $key = strtolower($grupo);

    if (isset($gruposEnlaces[$key]['enlaces'])) {
      foreach ($gruposEnlaces[$key]['enlaces'] as $enlace) {
        if ($enlace['id_option'] == $url) {
          return $enlace['name'];
        }
      }
    }

    // Si no encuentra el nombre, devolver la URL como fallback
    return $url;
  }

  /**
  * Guardar los datos del formulario
  */
  protected function postProcess() {
    // Cuando el usuario envia el formulario, primero creamos o actualizamos la lista
    $idLista = (int)Tools::getValue('HO_LISTA_ENLACES_ID_LISTA');

    if ($idLista) {
      // Actualizar lista existente
      Db::getInstance()->update('lista_enlaces', [
        'nombre' => pSQL(Tools::getValue('HO_LISTA_ENLACES_NOMBRE_BLOQUE')),
        'hook'   => pSQL(Tools::getValue('HO_LISTA_ENLACES_HOOK')),
      ], 'id_lista = '.$idLista);

      // Borrar relaciones antiguas de la lista antes de insertar las nuevas para evitar duplicados
      Db::getInstance()->delete('lista_enlace_relacion', 'id_lista = '.(int)$idLista);

    }else{
      // Crear nueva lista
      Db::getInstance()->insert('lista_enlaces', [
        'nombre' => pSQL(Tools::getValue('HO_LISTA_ENLACES_NOMBRE_BLOQUE')),
        'hook'   => pSQL(Tools::getValue('HO_LISTA_ENLACES_HOOK')),
      ]);
      $idLista = Db::getInstance()->Insert_ID();
    }

    $grupos = ['CMS', 'PRODUCTOS', 'CATEGORIAS', 'ESTATICOS'];
    
    foreach ($grupos as $grupo) {
      $urls = Tools::getValue('HO_LISTA_ENLACES_URL_'.$grupo, []); 
      foreach ($urls as $pos => $url) {
        $nombre = $this->getTitleFromUrl($grupo, $url);

        // Verificar si el enlace ya existe
        $id_enlace = (int)Db::getInstance()->getValue('
          SELECT id_enlace FROM `'._DB_PREFIX_.'enlace`
          WHERE url = "'.pSQL($url).'"
        ');

        if($id_enlace){
          // Actualizar enlace existente
          Db::getInstance()->update('enlace', [
            'nombre' => pSQL($nombre),
            'posicion' => (int)$pos
          ], 'id_enlace = '.$id_enlace);
        }else{
          // Insertar nuevo enlace
          Db::getInstance()->insert('enlace', [
            'nombre' => pSQL($nombre),
            'url' => pSQL($url),
            'posicion' => (int)$pos,
            'personalizado' => 0 // false
          ]);
          $id_enlace = Db::getInstance()->Insert_ID();
        }

        // Relacionar con la lista
        Db::getInstance()->insert('lista_enlace_relacion', [
          'id_lista' => (int)$idLista,
          'id_enlace' => (int)$id_enlace,
          'posicion' => (int)$pos
        ]);
      }
    }

    // Enlaces personalizados
    $urlsPersonalizados = Tools::getValue('HO_LISTA_ENLACES_CUSTOM_URL_NEW', []);
    $nombresPersonalizados = Tools::getValue('HO_LISTA_ENLACES_CUSTOM_NAME_NEW', []);
    $enlacesPersonalizados = [];

    foreach ($urlsPersonalizados as $pos => $url) {
      $nombre = $nombresPersonalizados[$pos] ?? '';
      if (!empty($url) && !empty($nombre)) {
        // Guardar en array para configuracion
        $enlacesPersonalizados[] = [
          'url' => $url,
          'nombre' => $nombre
        ];

        // Guardar en la tabla 'enlace
        Db::getInstance()->insert('enlace', [
          'nombre' => pSQL($nombre),
          'url'    => pSQL($url),
          'posicion' => (int)$pos,
          'personalizado' => 1 // true
        ]);
        $id_enlace = Db::getInstance()->Insert_ID();

        // Relacionar con la tabla
        Db::getInstance()->insert('lista_enlace_relacion', [
          'id_lista' => (int)$idLista,
          'id_enlace' => (int)$id_enlace,
          'posicion' => (int)$pos
        ]);
      }
    }

    // Guardar los enlaces personalizados Configuracion
    if (!empty($enlacesPersonalizados)) {
      Configuration::updateValue('HO_LISTA_ENLACES_PERSONALIZADOS', json_encode($enlacesPersonalizados));
    }

    // Guardar URLs seleccionadas para mantener checkboxes marcados al editar
    $urlsSeleccionadas = [];
    $grupos = ['CMS', 'PRODUCTOS', 'CATEGORIAS', 'ESTATICOS'];
    foreach ($grupos as $grupo) {
      $urls = Tools::getValue('HO_LISTA_ENLACES_URL_'.$grupo, []);
      $urlsSeleccionadas = array_merge($urlsSeleccionadas, $urls);
    }

    Configuration::updateValue('HO_LISTA_ENLACES_URL_SELECCIONADAS', json_encode($urlsSeleccionadas));

    // Limpiar formulario despues de guardar
    Configuration::updateValue('HO_LISTA_ENLACES_ID_LISTA', 0);
    Configuration::updateValue('HO_LISTA_ENLACES_NOMBRE_BLOQUE', '');
    Configuration::updateValue('HO_LISTA_ENLACES_HOOK', '');
    Configuration::updateValue('HO_LISTA_ENLACES_URL_SELECCIONADAS', json_encode([]));
    Configuration::updateValue('HO_LISTA_ENLACES_IDS', '');
    Configuration::updateValue('HO_LISTA_ENLACES_PERSONALIZADOS', json_encode([]));
  }

  /**
  * Añadir archivos CSS y JS en el back office
  */
  public function hookDisplayBackOfficeHeader() {
    if (Tools::getValue('configure') == $this->name) {
      $this->context->controller->addJS($this->_path.'views/js/back.js');
      $this->context->controller->addCSS($this->_path.'views/css/back.css');
    }
  }

  /**
  * Añadir archivos CSS y JS en el front office
  */
  public function hookHeader() {
    $this->context->controller->addJS($this->_path.'/views/js/front.js');
    $this->context->controller->addCSS($this->_path.'/views/css/front.css');
  }

  /**
  * Muestra las listas en el hook correspondiente (front)
  */
  public function renderListsByHook($hookName){
    // Obtener las listas asociadas a este hook
    $listas = Db::getInstance()->executeS('
      SELECT * FROM `'._DB_PREFIX_.'lista_enlaces`
      WHERE `hook` = "'.pSQL($hookName).'"
      ORDER BY `id_lista` ASC
    ');

    // Si no hay listas para este hook, no mostramos nada
    if (!$listas) {
      return '';
    }

    $listasFinal = [];

    foreach ($listas as $lista) {
      // Obtener enlaces relacionados
      $enlaces = Db::getInstance()->executeS('
        SELECT e.nombre, e.url
        FROM `'._DB_PREFIX_.'enlace` e
        INNER JOIN `'._DB_PREFIX_.'lista_enlace_relacion` rel
          ON e.id_enlace = rel.id_enlace
        WHERE rel.id_lista = '.(int)$lista['id_lista'].'
        ORDER BY rel.posicion ASC
      ');

      $listasFinal[] = [
        'nombre' => $lista['nombre'],
        'enlaces' => $enlaces,
      ];
    }

    // Asignar a Smarty
    $this->context->smarty->assign([
      'listas' => $listasFinal,
    ]);

    // Renderizar plantilla
    return $this->display(__FILE__, 'views/templates/hook/lista_enlaces.tpl');
  }

  // Metodo mágico de prestashop que captura cualquier hook automaticamente
  public function __call($method, $args){
    if (preg_match('/^hookDisplay(.+)/', $method, $matches)) {
      $hookName = 'display'.$matches[1];
      return $this->renderListsByHook($hookName);
    }

    return null;
  }
}

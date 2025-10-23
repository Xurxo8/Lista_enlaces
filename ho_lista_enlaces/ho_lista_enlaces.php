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
    include(dirname(__FILE__).'/sql/install.php');

    return parent::install() &&
      $this->registerHook('header') &&
      $this->registerHook('displayBackOfficeHeader') &&
      $this->registerHook('displayFooter');
  }

  public function uninstall() {
    include(dirname(__FILE__).'/sql/uninstall.php');

    return parent::uninstall();
  }

  /**
  * Carga el formulario de configuración
  */
  public function getContent() {
    /**
    * Si se han enviado valores en el formulario, procesarlos.
    */
    if (((bool)Tools::isSubmit('submitHo_lista_enlacesModule')) == true) {
      $this->postProcess();
    }

    $this->context->smarty->assign('module_dir', $this->_path);

    $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

    return $this->renderForm().$output;
  }

  /**
  * Crea el formulario que se mostrará en la configuración del módulo
  */
  protected function renderForm() {
    $helper = new HelperForm();

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

    // Id para el formulario
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

    foreach ($grupo['enlaces'] as $enlace) {
        $checkbox_html .= '
          <div>
            <label>
              <input type="checkbox" 
                     name="HO_LISTA_ENLACES_URL_' . strtoupper($key) . '[]" 
                     value="' . $enlace['id_option'] . '"
                     class="ho-checkbox-' . $key . '"> 
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


    // Boton para crar los enlaces personalizado
    $inputs[] = [
      'type' => 'html',
      'name' => 'add_custom_link_button',
      'html_content' => '<button type="button" id="addCustomLink" class="btn btn-default" style="margin-top:10px;">
        '.$this->l('Añadir enlace personalizado').'
      </button>
      <div id="customLinksContainer" style="margin-top:10px;"></div>',
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
    return [
      'HO_LISTA_ENLACES_HOOK' => Configuration::get('HO_LISTA_ENLACES_HOOK', null),
      'HO_LISTA_ENLACES_NOMBRE_BLOQUE' => Configuration::get('HO_LISTA_ENLACES_NOMBRE_BLOQUE', null),
      'HO_LISTA_ENLACES_URL_CMS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_CMS', '[]'), true),
      'HO_LISTA_ENLACES_URL_PRODUCTOS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_PRODUCTOS', '[]'), true),
      'HO_LISTA_ENLACES_URL_CATEGORIAS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_CATEGORIAS', '[]'), true),
      'HO_LISTA_ENLACES_URL_ESTATICOS' => json_decode(Configuration::get('HO_LISTA_ENLACES_URL_ESTATICOS', '[]'), true),
      'HO_LISTA_ENLACES_CUSTOM_URL' => json_decode(Configuration::get('HO_LISTA_ENLACES_CUSTOM', '{}'), true)['url'] ?? '',
      'HO_LISTA_ENLACES_CUSTOM_NAME' => json_decode(Configuration::get('HO_LISTA_ENLACES_CUSTOM', '{}'), true)['name'] ?? '',
    ];
  }

  /**
  * Guardar los datos del formulario
  */
  protected function postProcess() {
    Configuration::updateValue('HO_LISTA_ENLACES_HOOK', Tools::getValue('HO_LISTA_ENLACES_HOOK'));
    Configuration::updateValue('HO_LISTA_ENLACES_NOMBRE_BLOQUE', Tools::getValue('HO_LISTA_ENLACES_NOMBRE_BLOQUE'));

    $grupos = ['CMS', 'PRODUCTOS', 'CATEGORIAS', 'ESTATICOS'];
    foreach ($grupos as $grupo) {
      $valores = Tools::getValue('HO_LISTA_ENLACES_URL_' . $grupo, []);
      Configuration::updateValue('HO_LISTA_ENLACES_URL_' . $grupo, json_encode($valores));
    }

    // Enlaces personalizados
    $urlsPersonalizados = Tools::getValue('HO_LISTA_ENLACES_CUSTOM_URL_NEW', []);
    $nombresPersonalizados = Tools::getValue('HO_LISTA_ENLACES_CUSTOM_NAME_NEW', []);
    $enlacesPersonalizados = [];

    if (!empty($urlsPersonalizados)) {
      foreach ($urlsPersonalizados as $clave => $url) {
        $nombre = $nombresPersonalizados[$clave] ?? '';
        if (!empty($url) && !empty($nombre)) {
          $enlacesPersonalizados[] = [
            'url' => $url,
            'nombre' => $nombre
          ];
        }
      }
    }

    if (!empty($enlacesPersonalizados)) {
      Configuration::updateValue('HO_LISTA_ENLACES_PERSONALIZADOS', json_encode($enlacesPersonalizados));
    }
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

  public function hookDisplayFooter() {
    /* Coloca tu código aquí. */
  }
}

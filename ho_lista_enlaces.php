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
    Configuration::updateValue('HO_LISTA_ENLACES_LIVE_MODE', false);

    include(dirname(__FILE__).'/sql/install.php');

    return parent::install() &&
      $this->registerHook('header') &&
      $this->registerHook('displayBackOfficeHeader') &&
      $this->registerHook('displayFooter');
  }

  public function uninstall() {
    Configuration::deleteByName('HO_LISTA_ENLACES_LIVE_MODE');

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

    return $helper->generateForm(array($this->getConfigForm()));
  }

  /**
  * Crea la estructura de tu formulario
  */
  protected function getConfigForm() {
    return array(
      'form' => array(
        'legend' => array(
          'title' => $this->l('Ajustes'),
          'icon' => 'icon-cogs',
        ),
        'input' => array(
          array(
            'type' => 'switch',
            'label' => $this->l('Modo activo'),
            'name' => 'HO_LISTA_ENLACES_LIVE_MODE',
            'is_bool' => true,
            'desc' => $this->l('Usar este módulo en modo activo'),
            'values' => array(
              array(
                'id' => 'active_on',
                'value' => true,
                'label' => $this->l('Activado')
              ),
              array(
                'id' => 'active_off',
                'value' => false,
                'label' => $this->l('Desactivado')
              )
            ),
          ),
          array(
            'col' => 3,
            'type' => 'text',
            'prefix' => '<i class="icon icon-envelope"></i>',
            'desc' => $this->l('Ingrese una dirección de correo válida'),
            'name' => 'HO_LISTA_ENLACES_ACCOUNT_EMAIL',
            'label' => $this->l('Email'),
          ),
          array(
            'type' => 'password',
            'name' => 'HO_LISTA_ENLACES_ACCOUNT_PASSWORD',
            'label' => $this->l('Contraseña'),
          ),
        ),
        'submit' => array(
          'title' => $this->l('Guardar'),
        ),
      ),
    );
  }

  /**
  * Establece los valores de los inputs
  */
  protected function getConfigFormValues() {
    return array(
      'HO_LISTA_ENLACES_LIVE_MODE' => Configuration::get('HO_LISTA_ENLACES_LIVE_MODE', true),
      'HO_LISTA_ENLACES_ACCOUNT_EMAIL' => Configuration::get('HO_LISTA_ENLACES_ACCOUNT_EMAIL', 'contact@prestashop.com'),
      'HO_LISTA_ENLACES_ACCOUNT_PASSWORD' => Configuration::get('HO_LISTA_ENLACES_ACCOUNT_PASSWORD', null),
    );
  }

  /**
  * Guardar los datos del formulario
  */
  protected function postProcess() {
    $form_values = $this->getConfigFormValues();

    foreach (array_keys($form_values) as $key) {
      Configuration::updateValue($key, Tools::getValue($key));
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

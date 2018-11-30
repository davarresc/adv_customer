<?php
/**
 * 2007-2018 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Adv_customers extends Module
{

    const CMS_PAGE = 'ADV_CUSTOMERS_CMS_PAGE';
    const CMS_PAGE_ONLY_NOT_ACTIVE = 'ADV_CUSTOMERS_CMS_PAGE_ONLY_NOT_ACTIVE';
    const ENABLE_ENTERPRISE = 'ADV_CUSTOMERS_ENABLE_ENTERPRISE';
    const ENABLE_CUSTOMER = 'ADV_CUSTOMERS_ENABLE_CUSTOMER';
    const CUSTOMER_GROUP = 'ADV_CUSTOMERS_CUSTOMER_GROUP';
    const CUSTOMER_REQUIRE_APPROVED = 'ADV_CUSTOMERS_CUSTOMER_REQUIRE_APPROVED';
    const ENTERPRISE_GROUP = 'ADV_CUSTOMERS_ENTERPRISE_GROUP';
    const ENTERPRISE_REQUIRE_APPROVED = 'ADV_CUSTOMERS_ENTERPRISE_REQUIRE_APPROVED';

    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'adv_customers';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'David Arroyo <arroyoescobardavid@gmail.com>';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customers');
        $this->description = $this->l('This module allow select if the customer is or not a enterprise');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->admin_tpl_overrides = array(
            implode(DIRECTORY_SEPARATOR, array(
                'override', 'controllers', 'admin', 'templates', 'customers', 'helpers', 'view', 'view.tpl'
            ))
        );
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $addAdminTplOverrides = $this->_addAdminTplOverrides();

        return parent::install() &&
        Configuration::updateValue(static::ENABLE_ENTERPRISE, true) &&
        Configuration::updateValue(static::ENABLE_CUSTOMER, true) &&
        Configuration::updateValue(static::CMS_PAGE, '') &&
        Configuration::updateValue(static::CMS_PAGE_ONLY_NOT_ACTIVE, true) &&
        Configuration::updateValue(static::CUSTOMER_GROUP, false) &&
        Configuration::updateValue(static::CUSTOMER_REQUIRE_APPROVED, false) &&
        Configuration::updateValue(static::ENTERPRISE_GROUP, false) &&
        Configuration::updateValue(static::ENTERPRISE_REQUIRE_APPROVED, false) &&
        $this->installDB() &&
        $addAdminTplOverrides &&
        $this->registerHook('header') &&
        $this->registerHook('validateCustomerFormFields') &&
        $this->registerHook('additionalCustomerFormFields') &&
        $this->registerHook('backOfficeHeader');
    }

    public function installDB()
    {
        $sql = [];
        include(dirname(__FILE__) . '/sql/install.php');
        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }
        return true;
    }

    private function _addAdminTplOverrides()
    {
        $module_override_path = $this->getLocalPath() . DIRECTORY_SEPARATOR;
        $result = true;
        foreach ($this->admin_tpl_overrides as $admin_tpl_path) {
            $override_file_path = $module_override_path . $admin_tpl_path;
            $dest_override_file_path = _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . $admin_tpl_path;

            if (file_exists($override_file_path)) {
                if (!copy($override_file_path, $dest_override_file_path)) {
                    $result &= false;
                }
            } else {
                $result &= false;
            }
        }
        return $result;
    }

    public function uninstall()
    {
        $removeAdminTplOverrides = $this->_removeAdminTplOverrides();

        return parent::uninstall() &&
        Configuration::deleteByName(static::ENABLE_CUSTOMER) &&
        Configuration::deleteByName(static::ENABLE_ENTERPRISE) &&
        Configuration::deleteByName(static::CMS_PAGE) &&
        Configuration::deleteByName(static::CMS_PAGE_ONLY_NOT_ACTIVE) &&
        Configuration::deleteByName(static::CUSTOMER_GROUP) &&
        Configuration::deleteByName(static::CUSTOMER_REQUIRE_APPROVED) &&
        Configuration::deleteByName(static::ENTERPRISE_GROUP) &&
        Configuration::deleteByName(static::ENTERPRISE_REQUIRE_APPROVED) &&
        $removeAdminTplOverrides &&
        $this->uninstallDB();
    }

    public function uninstallDB()
    {
        $sql = [];
        include(dirname(__FILE__) . '/sql/uninstall.php');
        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }
        return true;
    }

    private function _removeAdminTplOverrides()
    {
        $module_override_path = $this->getLocalPath() . DIRECTORY_SEPARATOR;
        $result = true;
        foreach ($this->admin_tpl_overrides as $admin_tpl_path) {
            $dest_override_file_path = _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . $admin_tpl_path;
            if (file_exists($dest_override_file_path)) {
                if (!unlink($dest_override_file_path)) {
                    $result &= false;
                }
            }
        }
        return $result;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitAdv_customersModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAdv_customersModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Clients type'),
                        'name' => 'ADV_CUSTOMERS_ENABLE',
                        'values' => array(
                            'query' => array(
                                array(
                                    'id' => 'CUSTOMER',
                                    'name' => $this->l('Customer'),
                                ),
                                array(
                                    'id' => 'ENTERPRISE',
                                    'name' => $this->l('Enterprise'),
                                )
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Customer requires approval'),
                        'name' => static::CUSTOMER_REQUIRE_APPROVED,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-group"></i>',
                        'name' => static::CUSTOMER_GROUP,
                        'label' => $this->l('Customer group by default'),
                        'options' => array(
                            'query' => Group::getGroups($this->context->language->id),
                            'id' => 'id_group',
                            'name' => 'name',
                            'default' => array(
                                'value' => false,
                                'label' => ''
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enterprise requires approval'),
                        'name' => static::ENTERPRISE_REQUIRE_APPROVED,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-group"></i>',
                        'name' => static::ENTERPRISE_GROUP,
                        'label' => $this->l('Enterprise group by default'),
                        'options' => array(
                            'query' => Group::getGroups($this->context->language->id),
                            'id' => 'id_group',
                            'name' => 'name',
                            'default' => array(
                                'value' => false,
                                'label' => ''
                            )
                        )
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-group"></i>',
                        'name' => static::CMS_PAGE,
                        'label' => $this->l('Activation CMS page'),
                        'options' => array(
                            'query' => CMS::getCMSPages($this->context->language->id),
                            'id' => 'id_cms',
                            'name' => 'meta_title',
                            'default' => array(
                                'value' => false,
                                'label' => ''
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('CMS page only available if user is not active'),
                        'name' => static::CMS_PAGE_ONLY_NOT_ACTIVE,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            static::ENABLE_CUSTOMER => Configuration::get(static::ENABLE_CUSTOMER, true),
            static::CMS_PAGE => Configuration::get(static::CMS_PAGE),
            static::CMS_PAGE_ONLY_NOT_ACTIVE => Configuration::get(static::CMS_PAGE_ONLY_NOT_ACTIVE, true),
            static::ENABLE_ENTERPRISE => Configuration::get(static::ENABLE_ENTERPRISE, true),
            static::CUSTOMER_GROUP => Configuration::get(static::CUSTOMER_GROUP, false),
            static::CUSTOMER_REQUIRE_APPROVED => Configuration::get(static::CUSTOMER_REQUIRE_APPROVED, false),
            static::ENTERPRISE_GROUP => Configuration::get(static::ENTERPRISE_GROUP, false),
            static::ENTERPRISE_REQUIRE_APPROVED => Configuration::get(static::ENTERPRISE_REQUIRE_APPROVED, false),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookAdditionalCustomerFormFields($params)
    {

        $formFields = [];

        $enableCustomer = Configuration::get('ADV_CUSTOMERS_ENABLE_CUSTOMER');
        $enableEnterprise = Configuration::get('ADV_CUSTOMERS_ENABLE_ENTERPRISE');


        if ($enableCustomer || $enableEnterprise) {
            $typeCliente = (new FormField)
                ->setName('type_client')
                ->setType('radio-buttons')
                ->setLabel(
                    $this->trans(
                        'Cliente type', [], 'Modules.Advcustomer.Labels'
                    )
                )
                ->setRequired(true);
            if ($enableCustomer) {
                $typeCliente->addAvailableValue('customer', $this->trans(
                    'Customer', [], 'Modules.Advcustomer.Labels'
                ));
            }
            if ($enableEnterprise) {
                $typeCliente->addAvailableValue('enterprise', $this->trans(
                    'Enterprise', [], 'Modules.Advcustomer.Labels'
                ));
            }

            $formFields['type_client'] = $typeCliente;
        }

        if ($enableEnterprise) {
            $formFields['cif'] = (new FormField)
                ->setName('cif')
                ->setLabel(
                    $this->trans(
                        'CIF', [], 'Modules.Advcustomer.Labels'
                    )
                )->setRequired(true);
        }

        return $formFields;
    }

    public function hookValidateCustomerFormFields($params)
    {
        if ($params['fields'][0]->getValue() != 'enterprise') {
            $params['fields'][1]->setRequired(false);
        }
    }
}


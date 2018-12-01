<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
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
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @property Customer $object
 */
class AdminCustomersController extends AdminCustomersControllerCore
{

    public $active_old_value = false;

    public function printIsActiveIcon($value, $customer)
    {
        return '<a class="list-action-enable ' . ($value ? 'action-enabled' : 'action-disabled') . '" href="index.php?' . htmlspecialchars('tab=AdminCustomers&id_customer='
            . (int)$customer['id_customer'] . '&changeIsActiveVal&token=' . Tools::getAdminTokenLite('AdminCustomers')) . '">
				' . ($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>') .
        '</a>';
    }

    /**
     * Toggle is active flag
     */
    public function processChangeIsActiveVal()
    {
        $customer = new Customer($this->id_object);
        if (!Validate::isLoadedObject($customer)) {
            $this->errors[] = $this->trans('An error occurred while updating customer information.', array(), 'Admin.Orderscustomers.Notification');
        }

        $this->active_old_value = $customer->is_active;
        $customer->is_active = $customer->is_active ? 0 : 1;

        if (!$customer->update()) {
            $this->errors[] = $this->trans('An error occurred while updating customer information.', array(), 'Admin.Orderscustomers.Notification');
        }

        $this->sendActiveClient($customer);

        Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
    }

    public function __construct()
    {
        parent::__construct();
        $titles_array = array();
        $genders = Gender::getGenders($this->context->language->id);
        foreach ($genders as $gender) {

            $titles_array[$gender->id_gender] = $gender->name;
        }
        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'title' => array(
                'title' => $this->trans('Social title', array(), 'Admin.Global'),
                'filter_key' => 'a!id_gender',
                'type' => 'select',
                'list' => $titles_array,
                'filter_type' => 'int',
                'order_key' => 'gl!name'
            ),
            'firstname' => array(
                'title' => $this->trans('First name', array(), 'Admin.Global')
            ),
            'lastname' => array(
                'title' => $this->trans('Last name', array(), 'Admin.Global')
            ),
            'email' => array(
                'title' => $this->trans('Email address', array(), 'Admin.Global')
            ),
            'cif' => array(
                'title' => $this->trans('CIF', array(), 'Admin.Global')
            ),
            'type_client' => array(
                'title' => $this->trans('Type client', array(), 'Admin.Global'),
                'align' => 'text-center',
                'type' => 'select',
                'list' => [
                    'customer' => $this->trans('Customer', array(), 'Admin.Global'),
                    'enterprise' => $this->trans('Enterprise', array(), 'Admin.Global')
                ],
                'filter_key' => 'a!type_client'
            ),
            'is_active' => array(
                'title' => $this->trans('Checked', array(), 'Admin.Global'),
                'align' => 'text-center',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!is_active',
                'callback' => 'printIsActiveIcon',
            ),
        );
        if (Configuration::get('PS_B2B_ENABLE')) {
            $this->fields_list = array_merge($this->fields_list, array(
                'company' => array(
                    'title' => $this->trans('Company', array(), 'Admin.Global')
                ),
            ));
        }
        $this->fields_list = array_merge($this->fields_list, array(
            'total_spent' => array(
                'title' => $this->trans('Sales', array(), 'Admin.Global'),
                'type' => 'price',
                'search' => false,
                'havingFilter' => true,
                'align' => 'text-right',
                'badge_success' => true
            ),
            'active' => array(
                'title' => $this->trans('Enabled', array(), 'Admin.Global'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!active'
            ),
            'newsletter' => array(
                'title' => $this->trans('Newsletter', array(), 'Admin.Global'),
                'align' => 'text-center',
                'callback' => 'printNewsIcon',
            ),
            'optin' => array(
                'title' => $this->trans('Partner offers', array(), 'Admin.Orderscustomers.Feature'),
                'align' => 'text-center',
                'callback' => 'printOptinIcon',
            ),
            'date_add' => array(
                'title' => $this->trans('Registration', array(), 'Admin.Orderscustomers.Feature'),
                'type' => 'date',
                'align' => 'text-right'
            ),
            'connect' => array(
                'title' => $this->trans('Last visit', array(), 'Admin.Orderscustomers.Feature'),
                'type' => 'datetime',
                'search' => false,
                'havingFilter' => true
            )
        ));
    }

    public function renderForm()
    {

        if (!($obj = $this->loadObject(true))) {
            return;
        }
        $genders = Gender::getGenders();
        $list_genders = array();
        foreach ($genders as $key => $gender) {

            $list_genders[$key]['id'] = 'gender_' . $gender->id;
            $list_genders[$key]['value'] = $gender->id;
            $list_genders[$key]['label'] = $gender->name;
        }
        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $days = Tools::dateDays();
        $groups = Group::getGroups($this->default_form_language, true);
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->trans('Customer', array(), 'Admin.Global'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->trans('Social title', array(), 'Admin.Global'),
                    'name' => 'id_gender',
                    'required' => false,
                    'class' => 't',
                    'values' => $list_genders
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('First name', array(), 'Admin.Global'),
                    'name' => 'firstname',
                    'required' => true,
                    'col' => '4',
                    'hint' => $this->trans('Invalid characters:', array(), 'Admin.Notifications.Info') . ' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Last name', array(), 'Admin.Global'),
                    'name' => 'lastname',
                    'required' => true,
                    'col' => '4',
                    'hint' => $this->trans('Invalid characters:', array(), 'Admin.Notifications.Info') . ' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
                ),
                array(
                    'type' => 'text',
                    'prefix' => '<i class="icon-envelope-o"></i>',
                    'label' => $this->trans('Email address', array(), 'Admin.Global'),
                    'name' => 'email',
                    'col' => '4',
                    'required' => true,
                    'autocomplete' => false
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('CIF', array(), 'Admin.Global'),
                    'name' => 'cif',
                    'col' => '4',
                    'required' => false,
                    'autocomplete' => false
                ),
            )
        );
        $enableCustomer = Configuration::get('ADV_CUSTOMERS_ENABLE_CUSTOMER');
        $enableEnterprise = Configuration::get('ADV_CUSTOMERS_ENABLE_ENTERPRISE');

        $fields = [];
        if ($enableCustomer || $enableEnterprise) {
            $options = [];
            if ($enableEnterprise)
                $options[] = ['id' => 'customer', 'name' => $this->trans('Customer', array(), 'Admin.Global')];
            if ($enableCustomer)
                $options[] = ['id' => 'enterprise', 'name' => $this->trans('Enterprise', array(), 'Admin.Global')];

            $fields[] = array(
                'type' => 'select',
                'label' => $this->trans('Type client', array(), 'Admin.Global'),
                'name' => 'type_client',
                'options' => array(
                    'query' => $options,
                    'id' => 'id',
                    'name' => 'name'
                ),
                'col' => '4'
            );
        }
        $fields = array_merge($fields, array(
                array(
                    'type' => 'password',
                    'label' => $this->trans('Password', array(), 'Admin.Global'),
                    'name' => 'passwd',
                    'required' => ($obj->id ? false : true),
                    'col' => '4',
                    'hint' => ($obj->id ? $this->trans('Leave this field blank if there\'s no change.', array(), 'Admin.Orderscustomers.Help') :
                        sprintf($this->trans('Password should be at least %s characters long.', array(), 'Admin.Orderscustomers.Help'), Validate::PASSWORD_LENGTH))
                ),
                array(
                    'type' => 'birthday',
                    'label' => $this->trans('Birthday', array(), 'Admin.Orderscustomers.Feature'),
                    'name' => 'birthday',
                    'options' => array(
                        'days' => $days,
                        'months' => $months,
                        'years' => $years
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->trans('Checked', array(), 'Admin.Global'),
                    'name' => 'is_active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'is_active_on',
                            'value' => 1,
                            'label' => $this->trans('Enabled', array(), 'Admin.Global')
                        ),
                        array(
                            'id' => 'is_active_off',
                            'value' => 0,
                            'label' => $this->trans('Disabled', array(), 'Admin.Global')
                        )
                    ),
                    'hint' => $this->trans('Enable or disable if is active.', array(), 'Admin.Orderscustomers.Help')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->trans('Enabled', array(), 'Admin.Global'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->trans('Enabled', array(), 'Admin.Global')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->trans('Disabled', array(), 'Admin.Global')
                        )
                    ),
                    'hint' => $this->trans('Enable or disable customer login.', array(), 'Admin.Orderscustomers.Help')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->trans('Partner offers', array(), 'Admin.Orderscustomers.Feature'),
                    'name' => 'optin',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'optin_on',
                            'value' => 1,
                            'label' => $this->trans('Enabled', array(), 'Admin.Global')
                        ),
                        array(
                            'id' => 'optin_off',
                            'value' => 0,
                            'label' => $this->trans('Disabled', array(), 'Admin.Global')
                        )
                    ),
                    'disabled' => (bool)!Configuration::get('PS_CUSTOMER_OPTIN'),
                    'hint' => $this->trans('This customer will receive your ads via email.', array(), 'Admin.Orderscustomers.Help')
                ),
            )
        );
        $this->fields_form['input'] = array_merge($this->fields_form['input'], $fields);
        if (Tools::isSubmit('addcustomer') && Tools::isSubmit('submitFormAjax')) {
            $visitor_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
            $guest_group = Configuration::get('PS_GUEST_GROUP');
            foreach ($groups as $key => $g) {
                if (in_array($g['id_group'], array($visitor_group, $guest_group))) {
                    unset($groups[$key]);
                }
            }
        }
        $this->fields_form['input'] = array_merge(
            $this->fields_form['input'],
            array(
                array(
                    'type' => 'group',
                    'label' => $this->trans('Group access', array(), 'Admin.Orderscustomers.Feature'),
                    'name' => 'groupBox',
                    'values' => $groups,
                    'required' => true,
                    'col' => '6',
                    'hint' => $this->trans('Select all the groups that you would like to apply to this customer.', array(), 'Admin.Orderscustomers.Help')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('Default customer group', array(), 'Admin.Orderscustomers.Feature'),
                    'name' => 'id_default_group',
                    'options' => array(
                        'query' => $groups,
                        'id' => 'id_group',
                        'name' => 'name'
                    ),
                    'col' => '4',
                    'hint' => array(
                        $this->trans('This group will be the user\'s default group.', array(), 'Admin.Orderscustomers.Help'),
                        $this->trans('Only the discount for the selected group will be applied to this customer.', array(), 'Admin.Orderscustomers.Help')
                    )
                )
            )
        );
        if ($obj->id && ($obj->is_guest && $obj->id_default_group == Configuration::get('PS_GUEST_GROUP'))) {
            foreach ($this->fields_form['input'] as $k => $field) {
                if ($field['type'] == 'password') {
                    array_splice($this->fields_form['input'], $k, 1);
                }
            }
        }
        if (Configuration::get('PS_B2B_ENABLE')) {
            $risks = Risk::getRisks();
            $list_risks = array();
            foreach ($risks as $key => $risk) {

                $list_risks[$key]['id_risk'] = (int)$risk->id;
                $list_risks[$key]['name'] = $risk->name;
            }
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->trans('Company', array(), 'Admin.Global'),
                'name' => 'company'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->trans('SIRET', array(), 'Admin.Orderscustomers.Feature'),
                'name' => 'siret'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->trans('APE', array(), 'Admin.Orderscustomers.Feature'),
                'name' => 'ape'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->trans('Website', array(), 'Admin.Orderscustomers.Feature'),
                'name' => 'website'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->trans('Allowed outstanding amount', array(), 'Admin.Orderscustomers.Feature'),
                'name' => 'outstanding_allow_amount',
                'hint' => $this->trans('Valid characters:', array(), 'Admin.Orderscustomers.Help') . ' 0-9',
                'suffix' => $this->context->currency->sign
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->trans('Maximum number of payment days', array(), 'Admin.Orderscustomers.Feature'),
                'name' => 'max_payment_days',
                'hint' => $this->trans('Valid characters:', array(), 'Admin.Orderscustomers.Help') . ' 0-9'
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->trans('Risk rating', array(), 'Admin.Orderscustomers.Feature'),
                'name' => 'id_risk',
                'required' => false,
                'class' => 't',
                'options' => array(
                    'query' => $list_risks,
                    'id' => 'id_risk',
                    'name' => 'name'
                ),
            );
        }
        $this->fields_form['submit'] = array(
            'title' => $this->trans('Save', array(), 'Admin.Actions'),
        );
        $birthday = explode('-', $this->getFieldValue($obj, 'birthday'));
        $this->fields_value = array(
            'years' => $this->getFieldValue($obj, 'birthday') ? $birthday[0] : 0,
            'months' => $this->getFieldValue($obj, 'birthday') ? $birthday[1] : 0,
            'days' => $this->getFieldValue($obj, 'birthday') ? $birthday[2] : 0,
        );
        if (!Validate::isUnsignedId($obj->id)) {
            $customer_groups = array();
        } else {
            $customer_groups = $obj->getGroups();
        }
        $customer_groups_ids = array();
        if (is_array($customer_groups)) {
            foreach ($customer_groups as $customer_group) {
                $customer_groups_ids[] = $customer_group;
            }
        }
        if (empty($customer_groups_ids)) {
            $preselected = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'), Configuration::get('PS_CUSTOMER_GROUP'));
            $customer_groups_ids = array_merge($customer_groups_ids, $preselected);
        }
        foreach ($groups as $group) {
            $this->fields_value['groupBox_' . $group['id_group']] =
                Tools::getValue('groupBox_' . $group['id_group'], in_array($group['id_group'], $customer_groups_ids));
        }
        return AdminController::renderForm();
    }

    public function initProcess()
    {
        parent::initProcess();

        if (Tools::isSubmit('submitGuestToCustomer') && $this->id_object) {
            if ($this->access('edit')) {
                $this->action = 'guest_to_customer';
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        } elseif (Tools::isSubmit('changeNewsletterVal') && $this->id_object) {
            if ($this->access('edit')) {
                $this->action = 'change_newsletter_val';
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        } elseif (Tools::isSubmit('changeOptinVal') && $this->id_object) {
            if ($this->access('edit')) {
                $this->action = 'change_optin_val';
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        } elseif (Tools::isSubmit('changeIsActiveVal') && $this->id_object) {
            if ($this->access('edit')) {
                $this->action = 'change_is_active_val';
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', array(), 'Admin.Notifications.Error');
            }
        }

        // When deleting, first display a form to select the type of deletion
        if ($this->action == 'delete' || $this->action == 'bulkdelete') {
            if (Tools::getValue('deleteMode') == 'real' || Tools::getValue('deleteMode') == 'deleted') {
                $this->delete_mode = Tools::getValue('deleteMode');
            } else {
                $this->action = 'select_delete';
            }
        }
    }

    private function sendActiveClient(Customer $customer)
    {
        $require_validation = (
            ($customer->type_client === 'customer' && Configuration::get('ADV_CUSTOMERS_CUSTOMER_REQUIRE_APPROVED', null, null, null, false)) ||
            ($customer->type_client === 'enterprise' && Configuration::get('ADV_CUSTOMERS_ENTERPRISE_REQUIRE_APPROVED', null, null, null, false))
        );


        if ($require_validation && $customer->is_active && !$this->active_old_value) {
            Mail::Send(
                $this->context->language->id,
                'account_approved',
                $this->translator->trans(
                    'Your account has been approved!',
                    array(),
                    'Emails.Subject'
                ),
                array(
                    '{firstname}' => $customer->firstname,
                    '{lastname}' => $customer->lastname,
                    '{email}' => $customer->email,
                ),
                $customer->email,
                $customer->firstname . ' ' . $customer->lastname,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . 'adv_customers/mails/'
            );
        }
    }

    public function processUpdate()
    {
        if (Validate::isLoadedObject($this->object)) {
            $customer_email = strval(Tools::getValue('email'));

            $customer = new Customer();
            if (Validate::isEmail($customer_email)) {
                $customer->getByEmail($customer_email);
                if ($customer)
                    $this->active_old_value = $customer->is_active;
            }
        }


        return parent::processUpdate();
    }

    /**
     * @param ObjectModel $object
     * @return bool
     */
    protected function afterUpdate($object)
    {
        $this->sendActiveClient($object);
        return true;
    }
}

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
class FrontController extends FrontControllerCore
{

    public function init()
    {
        parent::init();

        if ($this->context->customer->isLogged()) {
            if ($this->auth && $this->requireValidation()) {
                $id_cms = Configuration::get('ADV_CUSTOMERS_CMS_PAGE');
                Tools::redirect("index.php?id_cms={$id_cms}&controller=cms&id_lang={$this->context->customer->id_lang}");
            }

            if ($this->context->controller->php_self == 'cms' && Configuration::get('ADV_CUSTOMERS_CMS_PAGE_ONLY_NOT_ACTIVE', true) &&
                Tools::getValue('id_cms') === Configuration::get('ADV_CUSTOMERS_CMS_PAGE')
            ) {
                Tools::redirect('index.php');
            }
        }
    }

    private function requireValidation()
    {
        $customer = $this->context->customer;
        return !$customer->is_active && (
            ($customer->type_client === 'customer' && Configuration::get('ADV_CUSTOMERS_CUSTOMER_REQUIRE_APPROVED', null, null, null, false)) ||
            ($customer->type_client === 'enterprise' && Configuration::get('ADV_CUSTOMERS_ENTERPRISE_REQUIRE_APPROVED', null, null, null, false))
        );
    }
}
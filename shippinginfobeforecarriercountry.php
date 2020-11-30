<?php
/**
 * 2007-2020 PrestaShop
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
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShippingInfoBeforeCarrierCountry extends Module
{
    private $html = '';

    protected $config_form = false;
    protected $support_url = 'https://addons.prestashop.com/contact-form.php?id_product=50445';

    public function __construct()
    {
        $this->name = 'shippinginfobeforecarriercountry';
        $this->tab = 'checkout';
        $this->version = '1.0.0';
        $this->author = 'Mathieu Thollet';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '994e0e0bd626020148aad4794cff8cec';

        parent::__construct();

        $this->displayName = $this->l('Shipping info before carrier with country condition');
        $this->description = $this->l('Displays info before carrier in purchase page, enabled or disabled with country condition');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeCarrier');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if ((bool)Tools::isSubmit('submitShippingInfoBeforeCarrierCountry')) {
            $this->postProcess();
        }
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('support_url', $this->support_url);
        $output = $this->html .
            $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configuration.tpl') .
            $this->renderConfigurationForm() .
            $this->context->smarty->fetch($this->local_path . 'views/templates/admin/support.tpl');
        return $output;
    }


    /**
     * Rendering of configuration form
     * @return mixed
     */
    protected function renderConfigurationForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShippingInfoBeforeCarrierCountry';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        // Form values
        if (Configuration::get('SHIPPINGINFOBEFORECARRIERCOUNTRY_CONFIG')) {
            $configuration = json_decode(Configuration::get('SHIPPINGINFOBEFORECARRIERCOUNTRY_CONFIG'), true);
        }
        if (isset($configuration) && is_array($configuration) && isset($configuration['active'])) {
            $helper->fields_value['active'] = $configuration['active'];
        } else {
            $helper->fields_value['active'] = false;
        }
        if (isset($configuration) && is_array($configuration) && isset($configuration['shipping_info']) && is_array($configuration['shipping_info'])) {
            foreach ($this->context->controller->getLanguages() as $lang) {
                $helper->fields_value['shipping_info'][$lang['id_lang']] = $configuration['shipping_info'][$lang['id_lang']];
            }
        } else {
            $helper->fields_value['shipping_info'] = [];
            foreach ($this->context->controller->getLanguages() as $lang) {
                $helper->fields_value['shipping_info'][$lang['id_lang']] = '';
            }
        }
        if (isset($configuration) && is_array($configuration) && isset($configuration['id_country']) && is_array($configuration['id_country'])) {
            foreach ($configuration['id_country'] as $id_country) {
                $helper->fields_value['id_country_' . $id_country] = 1;
            }
        }
        return $helper->generateForm(array($this->getConfigurationForm()));
    }


    /**
     * Structure of the configuration form
     * @return array
     */
    protected function getConfigurationForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Shipping info before carrier with country condition'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enabled'),
                        'name' => 'active',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Shipping information'),
                        'name' => 'shipping_info',
                        'required' => true,
                        'autoload_rte' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => $this->l('Country'),
                        'name' => 'id_country',
                        'values' => array(
                            'query' => Country::getCountries($this->context->language->id, true),
                            'id' => 'id_country',
                            'name' => 'name'
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'id' => 'submitShippingInfoBeforeCarrierCountry',
                    'icon' => 'process-icon-save'
                ),
            ),
        );
    }


    /**
     * PostProcess
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submitShippingInfoBeforeCarrierCountry')) {
            $this->processSaveConfiguration();
        }
    }


    /**
     * Save configuration
     */
    protected function processSaveConfiguration()
    {
        $active = Tools::getValue('active');
        $shipping_info = [];
        $id_country = [];
        foreach ($this->context->controller->getLanguages() as $lang) {
            $shipping_info[$lang['id_lang']] = Tools::getValue('shipping_info_' . $lang['id_lang']);
        }
        foreach (Country::getCountries($this->context->language->id, true) as $country) {
            if (Tools::getValue('id_country_' . $country['id_country'])) {
                $id_country[] = $country['id_country'];
            }
        }
        $configuration = [
            'active' => $active,
            'shipping_info' => $shipping_info,
            'id_country' => $id_country,
        ];
        Configuration::updateValue('SHIPPINGINFOBEFORECARRIERCOUNTRY_CONFIG', json_encode($configuration, JSON_HEX_QUOT | JSON_HEX_TAG));
        $this->setSuccessMessage($this->l('Settings have been saved.'));
    }


    /**
     * Add CSS file
     * @param $params
     */
    public function hookDisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css', 'all');
    }


    /**
     * Display shipping info
     * @param $params
     * @return mixed
     */
    public function hookDisplayBeforeCarrier($params)
    {
        $idAddressDelivery = $params['cart']->id_address_delivery;
        $idLang = $this->context->language->id;
        if ($idAddressDelivery) {
            if (Configuration::get('SHIPPINGINFOBEFORECARRIERCOUNTRY_CONFIG')) {
                $configuration = json_decode(Configuration::get('SHIPPINGINFOBEFORECARRIERCOUNTRY_CONFIG'), true);
                if (is_array($configuration)
                    && isset($configuration['active']) && $configuration['active']
                    && isset($configuration['id_country']) && is_array($configuration['id_country'])
                    && isset($configuration['shipping_info']) && is_array($configuration['shipping_info']) && isset($configuration['shipping_info'][$idLang])) {
                    $addressDelivery = new Address($idAddressDelivery);
                    if (in_array($addressDelivery->id_country, $configuration['id_country'])) {
                        $this->context->smarty->assign('shipping_info', $configuration['shipping_info'][$idLang]);
                        return $this->display(__FILE__, 'views/templates/hooks/hookDisplayBeforeCarrier.tpl');
                    }
                }
            }
        }
    }


    /**
     * Sets success message
     * @param $message
     */
    protected function setSuccessMessage($message)
    {
        $this->context->smarty->assign('message', $message);
        $this->html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/alert-success.tpl');
    }
}

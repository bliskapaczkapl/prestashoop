<?php

// @codingStandardsIgnoreFile

/**
 * @property Order $object
 * @SuppressWarnings(PHPMD)
 */
class AdminBliskaOrdersController extends AdminOrdersControllerCore
{
    public $toolbar_title;

    protected $statuses_array = array();

    /**
     * Constructor
     */
    public function __construct()
    {

        $this->_where = 'AND id_carrier IN (SELECT id_carrier FROM ps_carrier WHERE name = "bliskapaczka" OR name = "bliskapaczka_courier")';

        $this->bulk_actions = array(
            'getReport' => array('text' => $this->l('Get Report'), 'icon' => 'icon-download-alt')
        );

        parent::__construct();
    }

    /**
     * Init page header yoolbar
     */
    public function initPageHeaderToolbar()
    {
        AdminController::initPageHeaderToolbar();

        unset($this->toolbar_btn['new']);
        $this->toolbar_btn['close'] = array(
            'desc' => $this->l('Close buffer'),
            'href' => AdminController::$currentIndex.'&buffer=1&operatorName=POCZTA'.
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'icon' => 'save'
        );
    }

    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tpl_name filename
     * @return Smarty_Internal_Template
     */
    public function createTemplate($tpl_name)
    {
        // Use override tpl if it exists
        // If view access is denied, we want to use the default template that will be used to display an error
        if ($this->viewAccess() && $this->override_folder) {
            if (!Configuration::get('PS_DISABLE_OVERRIDES')
                && file_exists(
                    $this->context->smarty->getTemplateDir(1) . DIRECTORY_SEPARATOR . $this->override_folder . $tpl_name
                )
            ) {
                return $this->context->smarty->createTemplate(
                    $this->override_folder . $tpl_name,
                    $this->context->smarty
                );
            } elseif (file_exists(
                $this->context->smarty->getTemplateDir(0) . 'controllers' . DIRECTORY_SEPARATOR .
                    $this->override_folder . $tpl_name
            )
            ) {
                return $this->context->smarty->createTemplate(
                    'controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $tpl_name,
                    $this->context->smarty
                );
            }
        }

        if (file_exists(
            $this->context->smarty->getTemplateDir(0) . 'controllers' . DIRECTORY_SEPARATOR . 'orders' .
                DIRECTORY_SEPARATOR . $tpl_name
        )
        ) {
            return $this->context->smarty->createTemplate(
                $this->context->smarty->getTemplateDir(0) . 'controllers' . DIRECTORY_SEPARATOR . 'orders' .
                DIRECTORY_SEPARATOR . $tpl_name
            );
        }

        return $this->context->smarty->createTemplate(
            $this->context->smarty->getTemplateDir(0) . $tpl_name,
            $this->context->smarty
        );
    }

    /**
     * Mass action get report for choosen orders
     */
    public function processBulkGetReport()
    {
        if (Tools::isSubmit('submitBulkgetReportorder')) {
            /* We need call autoloader */
            Module::getInstanceByName('bliskapaczka');

            $numbers = '';

            foreach (Tools::getValue('orderBox') as $id_order) {
                $order = new Order($id_order);

                if ($numbers && $order->number) {
                    $numbers .= ',' . $order->number;
                } else {
                    $numbers = $order->number;
                }
            }

            /* @var Bliskapaczka\Prestashop\Core\Helper $bliskapaczkaHelper */
            $bliskapaczkaHelper = new Bliskapaczka\Prestashop\Core\Helper();

            /* @var $apiClient \Bliskapaczka\ApiClient\Bliskapaczka\Order */
            $apiClient = $bliskapaczkaHelper->getApiClientReport();
            $apiClient->setNumbers($numbers);

            try {
                $content = $apiClient->get();
            } catch (Exception $e) {
                $this->errors[] = Tools::displayError('The report file has not been downloaded. ' . $e->getMessage());
            }

            if ($content) {
                header("Content-Type: application/pdf");
                header("Content-Disposition: inline; filename=filename.pdf");
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                echo $content;
                exit();
            }
        }
    }

    /**
     * Render orders list
     */
    public function renderView()
    {
        parent::renderView();

        $tpl_file = _PS_MODULE_DIR_.'bliskapaczka/override/views/admin/bliskaorders/view.tpl';

        $tpl = $this->context->smarty->createTemplate($tpl_file, $this->context->smarty);

        $tpl->assign($this->tpl_view_vars);

        return $tpl->fetch();
    }

    /**
     * Post process
     */
    public function postProcess()
    {
        // If id_order is sent, we instanciate a new Order object
        if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0) {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                $this->errors[] = Tools::displayError('The order cannot be found within your database.');
            }
            ShopUrl::cacheMainDomainForShop((int)$order->id_shop);
        }

        $adminController = $this->createAdminController();
        if (Tools::isSubmit('bliskaCancel') && isset($order)) {
            try {
                $adminController->bliskaCancelAction();
            } catch (Exception $exception) {
                $this->errors[] = Tools::displayError($exception->getMessage());
            }
        } elseif (Tools::isSubmit('bliskaAdvice') && isset($order)) {
            try {
                $adminController->bliskaAdviceAction();
            } catch (Exception $exception) {
                $this->errors[] = Tools::displayError($exception->getMessage());
            } catch (CarrierNameException $carrierNameException) {
                $this->errors[] = Tools::displayError($carrierNameException->getMessage());
                parent::postProcess();
                return;
            }
        } elseif (Tools::isSubmit('bliskaUpdate') && isset($order)) {
            try {
                $adminController->bliskaUpdateAction();
            } catch (Exception $exception) {
                $this->errors[] = Tools::displayError($exception->getMessage());
            }
        } elseif (Tools::isSubmit('bliskaWaybill') && isset($order)) {
            try {
                $adminController->bliskaWaybillAction();
            } catch (Exception $exception) {
                $this->errors[] = Tools::displayError($exception->getMessage());
            }
        } elseif (Tools::getValue('buffer') == 1) {
            try {
                $adminController->bliskaCloseBufferAction();
            } catch (Exception $exception) {
                $this->errors[] = Tools::displayError($exception->getMessage());
            }
        }

        parent::postProcess();
    }

    /**
     * @return \Bliskapaczka\Prestashop\Core\Controller\AdminController
     */
    protected function createAdminController()
    {
        Module::getInstanceByName('bliskapaczka');
        $order = new Order(Tools::getValue('id_order'));

        $commandBus = new \Bliskapaczka\Prestashop\Core\Command\CommandBus();
        $bliskapaczkaHelper = new Bliskapaczka\Prestashop\Core\Helper();
        $restWaybillQuery = new \Bliskapaczka\Prestashop\Core\Query\RestWaybillQuery(
            $bliskapaczkaHelper->getApiClientWaybill()
        );
        $adminController = new \Bliskapaczka\Prestashop\Core\Controller\AdminController(
            $order,
            $commandBus,
            $restWaybillQuery,
            $bliskapaczkaHelper
        );
        return $adminController;
    }
}

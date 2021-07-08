<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 20.08.18
 * Time: 15:08
 */

namespace Bliskapaczka\Prestashop\Core\Controller;

use Bliskapaczka\ApiClient\Bliskapaczka\Order;
use Bliskapaczka\Prestashop\Core\Command\AdviceCommand;
use Bliskapaczka\Prestashop\Core\Command\AdviceCommandHandler;
use Bliskapaczka\Prestashop\Core\Command\CancelCommand;
use Bliskapaczka\Prestashop\Core\Command\CancelCommandHandler;
use Bliskapaczka\Prestashop\Core\Command\CloseBufferCommand;
use Bliskapaczka\Prestashop\Core\Command\CloseBufferCommandHandler;
use Bliskapaczka\Prestashop\Core\Command\CommandBusInterface;
use Bliskapaczka\Prestashop\Core\Command\UpdateCommand;
use Bliskapaczka\Prestashop\Core\Command\UpdateCommandHandler;
use Bliskapaczka\Prestashop\Core\Helper;
use Bliskapaczka\Prestashop\Core\Query\WaybillQueryInterface;

/**
 * Class AdminController
 *
 * @package Bliskapaczka\Prestashop\Core\Controller
 */
class AdminController
{
    /**
 * @var CommandBusInterface
*/
    protected $commandBus;
    /**
 * @var WaybillQueryInterface
*/
    protected $waybillQuery;

    /**
 * @var \Order
*/
    protected $order;

    /**
     * AdminController constructor.
     *
     * @param Order                 $order
     * @param CommandBusInterface   $commandBus
     * @param WaybillQueryInterface $waybillQuery
     * @param Helper                $helper
     */
    public function __construct(
        \Order $order,
        CommandBusInterface $commandBus,
        WaybillQueryInterface $waybillQuery,
        Helper $helper
    ) {
    
        $this->order = $order;
        $this->commandBus = $commandBus;
        $this->waybillQuery = $waybillQuery;
        $cancelHandler = new CancelCommandHandler($helper->getApiClientCancel());
        $updateHandler = new UpdateCommandHandler($helper->getApiClientOrder());
        $adviceHandler = new AdviceCommandHandler();
        $closeBufferHandler = new CloseBufferCommandHandler($helper->getApiClientConfirm());
        $this->commandBus->registerHandler(
            "Bliskapaczka\Prestashop\Core\Command\CancelCommand",
            $cancelHandler
        );
        $this->commandBus->registerHandler(
            "Bliskapaczka\Prestashop\Core\Command\UpdateCommand",
            $updateHandler
        );
        $this->commandBus->registerHandler(
            "Bliskapaczka\Prestashop\Core\Command\AdviceCommand",
            $adviceHandler
        );
        $this->commandBus->registerHandler(
            "Bliskapaczka\Prestashop\Core\Command\CloseBufferCommand",
            $closeBufferHandler
        );
    }

    /**
     * Action for print waybill
     *
     * @throws \Exception
     */
    public function bliskaWaybillAction()
    {
        $waybillView = $this->waybillQuery->getByOrderId($this->order->number);
        $content = file_get_contents($waybillView->getUrl());

        if ($content === false) {
            throw new \Exception('Content is empty');
        }

        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename=filename.pdf");
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        echo $content;
        exit();
    }

    /**
     * Action for update bliska status
     */
    public function bliskaUpdateAction()
    {
        $updateCommand = new UpdateCommand($this->order);
        $this->commandBus->handle($updateCommand);
    }

    /**
     * @throws \Bliskapaczka\Prestashop\Core\Command\Exception\CarrierNameException
     */
    public function bliskaAdviceAction()
    {
        $adviceCommand = new AdviceCommand($this->order);
        $this->commandBus->handle($adviceCommand);
    }

    /**
     * Action for cancel order
     */
    public function bliskaCancelAction()
    {

        $cancelCommand = new CancelCommand($this->order);
        $this->commandBus->handle($cancelCommand);
    }

    /**
     * Action for close buffer PP
     */
    public function bliskaCloseBufferAction()
    {
        $closeBufferCommand = new CloseBufferCommand($this->order, 'POCZTA');
        $this->commandBus->handle($closeBufferCommand);
    }
}

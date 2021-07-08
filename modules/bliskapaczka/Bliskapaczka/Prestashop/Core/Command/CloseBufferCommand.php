<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.08.18
 * Time: 08:55
 */

namespace Bliskapaczka\Prestashop\Core\Command;

/**
 * Class CancelCommand
 *
 * @package Bliskapaczka\Prestashop\Core\Command
 */
class CloseBufferCommand extends CommandAbstract
{
    /** @var string */
    protected $operatorName;

    /**
     * CloseBufferCommand constructor.
     * @param \Order $order
     * @param $operatorName
     */
    public function __construct(\Order $order, $operatorName)
    {
        $this->operatorName = $operatorName;
        parent::__construct($order);
    }

    /**
     * @return string
     */
    public function getOperatorName()
    {
        return $this->operatorName;
    }
}

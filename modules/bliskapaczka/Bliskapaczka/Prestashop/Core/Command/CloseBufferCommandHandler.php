<?php
/**
 * Created by PhpStorm.
 * User: pawel
 * Date: 21.08.18
 * Time: 08:55
 */

namespace Bliskapaczka\Prestashop\Core\Command;

/**
 * Class CancelCommandHandler
 *
 * @package Bliskapaczka\Prestashop\Core\Command
 */
class CloseBufferCommandHandler extends CommandHandlerAbstract
{

    /**
     * @param CommandAbstract $command
     * @throws \Exception
     */
    public function handle(CommandAbstract $command)
    {
        $this->apiClient->setOperator($command->getOperatorName());
        $this->apiClient->confirm();
    }
}

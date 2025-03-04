<?php

declare(strict_types=1);

namespace terpz710\pocketshop\command;

use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use terpz710\pocketshop\form\ShopForm;

use CortexPE\Commando\BaseCommand;

class ShopCommand extends BaseCommand {

    protected function prepare() : void{
        $this->setPermission("pocketshop.cmd");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game!");
            return;
        }

        ShopForm::getInstance()->openMainShop($sender);
    }
}

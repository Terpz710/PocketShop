<?php

declare(strict_types=1);

namespace terpz710\pocketshop;

use pocketmine\plugin\PluginBase;

use terpz710\pocketshop\command\ShopCommand;

use CortexPE\Commando\PacketHooker;

final class PocketShop extends PluginBase {

    protected static self $instance;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->saveResource("shop.yml");

        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        $this->getServer()->getCommandMap()->register("PocketShop", new ShopCommand($this, "shop", "opens a shop menu"));
    }

    public static function getInstance() : self{
        return self::$instance;
    }
}

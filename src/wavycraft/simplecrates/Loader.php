<?php

declare(strict_types=1);

namespace wavycraft\simplecrates;

use pocketmine\plugin\PluginBase;

use wavycraft\simplecrates\commands\KeyCommand;
use wavycraft\simplecrates\commands\KeyAllCommand;
use wavycraft\simplecrates\commands\CreateCrateCommand;

final class Loader extends PluginBase {

    protected static $instance;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->saveDefaultConfig();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $this->getServer()->getCommandMap()->registerAll("SimpleCrates", [
            new KeyCommand(),
            new KeyAllCommand(),
            new CreateCrateCommand()
        ]);
    }

    public static function getInstance() : self{
        return self::$instance;
    }
}
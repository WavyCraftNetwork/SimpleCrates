<?php

declare(strict_types=1);

namespace wavycraft\simplecrates\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\player\Player;

use pocketmine\Server;

use pocketmine\utils\TextFormat as TextColor;

use wavycraft\simplecrates\utils\KeyManager;

class KeyCommand extends Command {

    public function __construct() {
        parent::__construct("key");
        $this->setDescription("Give a crate key to a player");
        $this->setPermission("simplecrates.key");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        $targetPlayer = Server::getInstance()->getPlayerByPrefix($args[0] ?? "");
        if (!$targetPlayer instanceof Player) {
            $sender->sendMessage(TextColor::RED . "That player cannot be found");
            return false;
        }

        $crateType = $args[1] ?? null;
        if ($crateType === null) {
            $sender->sendMessage("§l§f(§4!§f)§r§f Please specify a crate type!");
            return false;
        }

        $amount = isset($args[2]) && is_numeric($args[2]) ? max(1, (int)$args[2]) : 1;
        KeyManager::getInstance()->giveCrateKey($targetPlayer, $crateType, $amount);
        $sender->sendMessage("§l§f(§a!§f)§r§f Given §e" . $amount . "§f '$crateType' crate key(s) to §e" . $targetPlayer->getName() . "§f!");
        $targetPlayer->sendMessage("§l§f(§a!§f)§r§f You have been given §e" . $amount . "§f '$crateType' crate key(s) by §e" . $sender->getName() . "§f!");

        return true;
    }
}

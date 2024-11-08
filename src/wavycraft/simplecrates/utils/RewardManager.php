<?php

declare(strict_types=1);

namespace wavycraft\simplecrates\utils;

use pocketmine\Server;

use pocketmine\player\Player;

use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;

use pocketmine\utils\SingletonTrait;

use wavycraft\simplecrates\Loader;

final class RewardManager {
    use SingletonTrait;

    public function givePrize(Player $player, string $crateType): void {
        $config = Loader::getInstance()->getConfig();
        $prizes = $config->get("prizes", []);

        if (!isset($prizes[$crateType])) {
            $player->sendMessage("§l§f(§4!§f)§r§f No prizes are configured for crate type '$crateType' in the config!");
            return;
        }

        $cratePrizes = $prizes[$crateType];
        if (empty($cratePrizes)) {
            $player->sendMessage("§l§f(§4!§f)§r§f No prizes available for '$crateType' crate!");
            return;
        }

        $weightedPrizes = [];
        foreach ($cratePrizes as $prize) {
            $chance = $prize["chance"] ?? 1;
            for ($i = 0; $i < $chance; $i++) {
                $weightedPrizes[] = $prize;
            }
        }

        if (empty($weightedPrizes)) {
            $player->sendMessage("§l§f(§4!§f)§r§f No valid prizes for '$crateType' crate!");
            return;
        }

        $randomPrize = $weightedPrizes[array_rand($weightedPrizes)];

        $item = StringToItemParser::getInstance()->parse($randomPrize["item"] ?? '');
        if ($item === null) {
            $player->sendMessage("§l§f(§4!§f)§r§f Invalid item in '$crateType' prize configuration!");
            return;
        }

        $item->setCount((int) ($randomPrize["count"] ?? 1));

        if (isset($randomPrize["custom_name"])) {
            $item->setCustomName($randomPrize["custom_name"]);
        }

        if (isset($randomPrize["lore"]) && is_array($randomPrize["lore"])) {
            $item->setLore($randomPrize["lore"]);
        }

        if (isset($randomPrize["enchantments"]) && is_array($randomPrize["enchantments"])) {
            foreach ($randomPrize["enchantments"] as $enchantData) {
                $enchant = StringToEnchantmentParser::getInstance()->parse($enchantData["type"]);
                $level = (int) ($enchantData["level"] ?? 1);

                if ($enchant !== null) {
                    $item->addEnchantment(new EnchantmentInstance($enchant, $level));
                }
            }
        }

        $player->getInventory()->addItem($item);

        $prizeMessage = "§l§f(§a!§f)§r§f Player " . $player->getName() . " has won §b" . $item->getName() . "§f from a $crateType crate!";
        Server::getInstance()->broadcastMessage($prizeMessage);
    }
}

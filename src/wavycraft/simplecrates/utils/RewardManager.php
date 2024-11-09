<?php

declare(strict_types=1);

namespace wavycraft\simplecrates\utils;

use pocketmine\Server;

use pocketmine\player\Player;

use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;

use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as TextColor;

use wavycraft\simplecrates\Loader;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;

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

    public function previewCrate(Player $player, string $crateType) {
        $config = Loader::getInstance()->getConfig();
        $crateItems = $config->get("prizes", [])[$crateType] ?? [];

        if (empty($crateItems)) {
            $player->sendMessage("§l§f(§4!§f)§r§f No prizes available for '$crateType' crate!");
            return;
        }

        usort($crateItems, function ($a, $b) {
            return ($b['chance'] ?? 1) - ($a['chance'] ?? 1);
        });

        $totalChances = array_sum(array_column($crateItems, 'chance'));

        $menuType = count($crateItems) > 27 ? InvMenuTypeIds::TYPE_DOUBLE_CHEST : InvMenuTypeIds::TYPE_CHEST;
        $menu = InvMenu::create($menuType);
        $menu->setListener(InvMenu::readonly());
        $menu->setName("Crate Preview: " . ucfirst($crateType));

        $slot = 0;
        foreach ($crateItems as $crateItem) {
            if ($slot > 53) break;

            $item = StringToItemParser::getInstance()->parse($crateItem["item"] ?? '');
            if ($item === null) {
                continue;
            }

            $item->setCount((int) ($crateItem["count"] ?? 1));

            if (isset($crateItem["custom_name"])) {
                $item->setCustomName(TextColor::RESET . $crateItem["custom_name"]);
            }

            $chancePercentage = round(($crateItem["chance"] / $totalChances) * 100, 2);
            $item->setLore([
                TextColor::RESET,
                TextColor::RESET . "Chance: " . $chancePercentage . "%"
            ]);

            $menu->getInventory()->setItem($slot, $item);
            $slot++;
        }

        $menu->send($player);
    }
}

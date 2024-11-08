<?php

declare(strict_types=1);

namespace wavycraft\simplecrates;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\block\BlockTypeIds;

use pocketmine\player\Player;

use pocketmine\world\Position;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TextColor;
use pocketmine\utils\SingletonTrait;

use wavycraft\simplecrates\utils\FloatingText;
use wavycraft\simplecrates\utils\CrateManager;

use wavycraft\core\utils\SoundUtils;

class EventListener implements Listener {
    use SingletonTrait;

    private $plugin;
    private $cooldowns = [];

    public function __construct() {
        $this->plugin = Loader::getInstance();
    }

    public function onPlace(BlockPlaceEvent $event) {
        $item = $event->getItem();
        $nbt = $item->getNamedTag();

        if ($nbt->getTag("Key")) {
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (CrateManager::getInstance()->isCrateBlock($block)) {
            $player->sendMessage(TextColor::RED . "You may not break the crate!");
            SoundUtils::getInstance()->playSound($player, "note.bass");
            $event->cancel();
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $blockPos = $block->getPosition();
        $playerName = $player->getName();

        if (CrateManager::getInstance()->isCreatingCrate($player)) {
            if ($block->getTypeId() === BlockTypeIds::CHEST) {
                CrateManager::getInstance()->finishCrateCreation($player, $blockPos);
                $event->cancel();
            } else {
                $player->sendMessage(TextColor::RED . "You need to interact with a chest block to create a crate.");
            }
            return;
        }

        if (isset($this->cooldowns[$playerName]) && time() - $this->cooldowns[$playerName] < 5) {
            $timeLeft = 5 - (time() - $this->cooldowns[$playerName]);
            $player->sendMessage("§l§f(§c!§f)§r§f Please wait §e" . $timeLeft . " seconds §fbefore opening another crate!");
            $event->cancel();
            return;
        }

        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            if (CrateManager::getInstance()->isCrateBlock($block)) {
                $crateType = CrateManager::getInstance()->getCrateTypeByPosition($blockPos);
                if ($crateType !== null) {
                    CrateManager::getInstance()->openCrate($player, $crateType);
                    $this->cooldowns[$playerName] = time();
                    $event->cancel();
                } else {
                    $player->sendMessage(TextColor::RED . "Unknown crate type.");
                }
            }
        }
    }

    public function onChunkLoad(ChunkLoadEvent $event) {
        $filePath = $this->plugin->getDataFolder() . "floating_text.json";
        FloatingText::loadFromFile($filePath);
    }

    public function onChunkUnload(ChunkUnloadEvent $event) {
        FloatingText::saveFile();
    }

    public function onWorldUnload(WorldUnloadEvent $event) {
        FloatingText::saveFile();
    }

    public function onEntityTeleport(EntityTeleportEvent $event) {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            $fromWorld = $event->getFrom()->getWorld();
            $toWorld = $event->getTo()->getWorld();

            if ($fromWorld !== $toWorld) {
                foreach (FloatingText::$floatingText as $tag => [$position, $floatingText]) {
                    if ($position->getWorld() === $fromWorld) {
                        FloatingText::makeInvisible($tag);
                    }
                }
            }
        }
    }
}

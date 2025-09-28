<?php

namespace Pickaxe\Listener;

use Class\UUIDGenerator;
use Pickaxe\Main;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\enchantment\EnchantmentInstance;

class EventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    private function updateLore(\pocketmine\item\Item $item, int $blocks, int $effi): \pocketmine\item\Item {
        $nextLevel = $effi + 1;
        $threshold = $nextLevel === 1 ? 100 : $nextLevel * 300;
        $remaining = max(0, $threshold - $blocks);

        $lore = [
            "§eNiveau: §f" . $effi,
            "§bBlocs minés: §f" . $blocks,
            "§aProchain niveau dans: §f" . $remaining . " blocs"
        ];
        $item->setLore($lore);

        return $item;
    }

    public function onBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        if ($item->getTypeId() !== VanillaItems::IRON_PICKAXE()->getTypeId()) {
            return;
        }

        $nbt = $item->getNamedTag();

        if (!$nbt->getTag("god_pickaxe_id")) {
            $uuid = UUIDGenerator::v4();

            $nbt->setString("god_pickaxe_id", $uuid);
            $item->setNamedTag($nbt);
            $item->setCustomName("§6Pickaxe of the God");

            $item = $this->updateLore($item, 0, 0);

            $player->getInventory()->setItemInHand($item);

            $stmt = $this->plugin->db->prepare("INSERT INTO god_pickaxes (uuid) VALUES (?)");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $stmt->close();

            return;
        }

        $uuid = $nbt->getString("god_pickaxe_id");

        $stmt = $this->plugin->db->prepare("SELECT blocks_mined, efficiency_level FROM god_pickaxes WHERE uuid=?");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if (!$data) return;

        $blocks = $data["blocks_mined"] + 1;
        $effi = $data["efficiency_level"];

        $nextLevel = $effi + 1;
        $threshold = $nextLevel === 1 ? 100 : $nextLevel * 300;

        if ($blocks >= $threshold) {
            $effi++;
            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), $effi));
            $player->sendMessage("§aTa pioche évolue en Efficacité " . $effi . " !");
        }

        $item = $this->updateLore($item, $blocks, $effi);
        $player->getInventory()->setItemInHand($item);

        $stmt = $this->plugin->db->prepare("UPDATE god_pickaxes SET blocks_mined=?, efficiency_level=? WHERE uuid=?");
        $stmt->bind_param("iis", $blocks, $effi, $uuid);
        $stmt->execute();
        $stmt->close();
    }
}
<?php

namespace Pickaxe\Commands;

use Class\UUIDGenerator;
use Pickaxe\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\enchantment\EnchantmentInstance;

class GiveGodPickCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("givegodpick", "Donner une Pickaxe of the God", "/givegodpick <joueur>");
        $this->setPermission("pickaxeofgod.give");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): void {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage("§cTu n’as pas la permission !");
            return;
        }

        if (count($args) < 1) {
            $sender->sendMessage("§eUsage : /givegodpick <joueur>");
            return;
        }

        $target = $this->plugin->getServer()->getPlayerExact($args[0]);
        if (!$target instanceof Player) {
            $sender->sendMessage("§cLe joueur " . $args[0] . " est introuvable.");
            return;
        }

        $uuid = UUIDGenerator::v4();
        $pickaxe = VanillaItems::IRON_PICKAXE();
        $pickaxe->setCustomName("§6Pickaxe of the God");
        $pickaxe->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 0));

        $pickaxe = VanillaItems::IRON_PICKAXE();
        $pickaxe->setCustomName("§6Pickaxe of the God");
        $pickaxe->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 0));

        $pickaxe->setLore([
            "§eNiveau: §f0",
            "§bBlocs minés: §f0",
            "§aProchain niveau dans: §f100 blocs"
        ]);


        $nbt = $pickaxe->getNamedTag();
        $nbt->setString("god_pickaxe_id", $uuid);
        $pickaxe->setNamedTag($nbt);

        $stmt = $this->plugin->db->prepare("INSERT INTO god_pickaxes (uuid) VALUES (?)");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $stmt->close();

        $target->getInventory()->addItem($pickaxe);
        $sender->sendMessage("§aTu as donné une Pickaxe of the God à §e" . $target->getName());
        $target->sendMessage("§aTu as reçu une §6Pickaxe of the God §a!");
    }
}

<?php

declare(strict_types=1);

namespace HipliAI\ClearLaggUI;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\scheduler\Task;

class Main extends PluginBase {
    private $interval = 300; // 5 minutes par défaut
    private $task;
    private $enabled = true;

    public function onEnable(): void {
        $this->getLogger()->info("ClearLaggUI plugin activé!");
        $this->scheduleClearLaggTask();
    }

    public function onDisable(): void {
        $this->getLogger()->info("ClearLaggUI plugin désactivé!");
        $this->cancelClearLaggTask();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "clearlagg") {
            if (!$sender instanceof Player) {
                $sender->sendMessage(TextFormat::RED . "Cette commande ne peut être utilisée que par un joueur.");
                return true;
            }

            if (!$sender->hasPermission("clearlagg.command")) {
                $sender->sendMessage(TextFormat::RED . "Vous n'avez pas la permission d'utiliser cette commande.");
                return true;
            }

            $this->openMainUI($sender);
            return true;
        }
        return false;
    }

    private function openMainUI(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;

            switch ($data) {
                case 0:
                    $this->enabled = true;
                    $this->scheduleClearLaggTask();
                    $player->sendMessage(TextFormat::GREEN . "ClearLagg activé avec un intervalle de " . $this->interval . " secondes.");
                    break;
                case 1:
                    $this->enabled = false;
                    $this->cancelClearLaggTask();
                    $player->sendMessage(TextFormat::YELLOW . "ClearLagg désactivé.");
                    break;
                case 2:
                    $this->openIntervalUI($player);
                    break;
            }
        });

        $form->setTitle("Configuration ClearLagg");
        $form->setContent("Configurez les paramètres du ClearLagg");
        $form->addButton("Activer ClearLagg");
        $form->addButton("Désactiver ClearLagg");
        $form->addButton("Définir l'intervalle");
        $form->sendToPlayer($player);
    }

    private function openIntervalUI(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) return;

            $this->interval = (int) $data[0];
            $this->scheduleClearLaggTask();
            $player->sendMessage(TextFormat::GREEN . "Intervalle de ClearLagg défini à " . $this->interval . " secondes.");
        });

        $form->setTitle("Définir l'intervalle de ClearLagg");
        $form->addSlider("Intervalle (secondes)", 60, 3600, 60, $this->interval);
        $form->sendToPlayer($player);
    }

    private function scheduleClearLaggTask(): void {
        if ($this->task !== null) {
            $this->getScheduler()->cancelAllTasks();
        }
        $this->task = $this->getScheduler()->scheduleRepeatingTask(new ClearLaggTask($this), $this->interval * 20);
    }

    private function cancelClearLaggTask(): void {
        if ($this->task !== null) {
            $this->getScheduler()->cancelAllTasks();
            $this->task = null;
        }
    }

    public function clearLagg(): void {
        if (!$this->enabled) return;

        $count = 0;
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $level) {
            foreach ($level->getEntities() as $entity) {
                if (!$entity instanceof Player) {
                    $entity->close();
                    $count++;
                }
            }
        }
        $this->getServer()->broadcastMessage(TextFormat::GREEN . "ClearLagg: " . $count . " entités ont été supprimées.");
    }
}

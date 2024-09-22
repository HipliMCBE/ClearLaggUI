<?php

declare(strict_types=1);

namespace HipliAI\ClearLaggUI;

use pocketmine\scheduler\Task;

class ClearLaggTask extends Task {
    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        $this->plugin->clearLagg();
    }
}

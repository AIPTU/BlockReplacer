<?php

/*
 * Copyright (c) 2021-2024 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer;

use aiptu\blockreplacer\config\BadConfigurationException;
use aiptu\blockreplacer\config\Configuration;
use aiptu\blockreplacer\data\BlockDataManager;
use aiptu\blockreplacer\task\TaskHandler;
use aiptu\sounds\SoundFactory;
use aiptu\blockreplacer\libs\_379728ea83b3ad86\JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use function class_exists;
use function in_array;

class BlockReplacer extends PluginBase {
	use SingletonTrait;

	private Configuration $configuration;

	public function onEnable() : void {
		self::setInstance($this);

		if (!$this->validateVirions()) {
			return;
		}

		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());

		try {
			$this->configuration = Configuration::fromData($this->getConfig()->getAll());
		} catch (BadConfigurationException $e) {
			$this->getLogger()->alert('Failed to load the configuration: ' . $e->getMessage());
			$this->getLogger()->alert('Please fix the errors and restart the server.');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->getScheduler()->scheduleRepeatingTask(new TaskHandler(), 20);

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);
	}

	public function onDisable() : void {
		BlockDataManager::getInstance()->restoreAllBlocks();
	}

	public function getConfiguration() : Configuration {
		return $this->configuration;
	}

	public function checkWorld(World $world) : bool {
		$blacklist = $this->getConfiguration()->getWorld()->isWorldBlacklistEnabled();
		$whitelist = $this->getConfiguration()->getWorld()->isWorldWhitelistEnabled();
		$world_name = $world->getFolderName();

		if ($blacklist === $whitelist) {
			return true;
		}

		if ($blacklist) {
			$disallowed_worlds = $this->getConfiguration()->getWorld()->getBlacklistedWorlds();
			return !in_array($world_name, $disallowed_worlds, true);
		}

		if ($whitelist) {
			$allowed_worlds = $this->getConfiguration()->getWorld()->getWhitelistedWorlds();
			return in_array($world_name, $allowed_worlds, true);
		}

		return false;
	}

	/**
	 * Checks if the required virions/libraries are present before enabling the plugin.
	 */
	private function validateVirions() : bool {
		$requiredVirions = [
			'Sounds' => SoundFactory::class,
			'UpdateNotifier' => UpdateNotifier::class,
		];

		$return = true;

		foreach ($requiredVirions as $name => $class) {
			if (!class_exists($class)) {
				$this->getLogger()->error($name . ' virion was not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer.');
				$this->getServer()->getPluginManager()->disablePlugin($this);
				$return = false;
				break;
			}
		}

		return $return;
	}
}
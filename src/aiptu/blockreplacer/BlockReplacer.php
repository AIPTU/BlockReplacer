<?php

/*
 * Copyright (c) 2021-2025 AIPTU
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
use aiptu\blockreplacer\libs\_72490a5c9c384c0c\aiptu\libsounds\SoundBuilder;
use aiptu\blockreplacer\libs\_72490a5c9c384c0c\JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use function class_exists;
use function in_array;

class BlockReplacer extends PluginBase {
	use SingletonTrait;

	private Configuration $configuration;

	protected function onEnable() : void {
		self::setInstance($this);

		$this->validateVirions();

		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());

		try {
			$this->configuration = Configuration::fromData($this->getConfig()->getAll());
		} catch (BadConfigurationException $e) {
			$this->getLogger()->alert('Failed to load the configuration: ' . $e->getMessage());
			throw new DisablePluginException();
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
		$world_name = $world->getFolderName();
		$worldConfig = $this->getConfiguration()->getWorld();

		$isBlacklistEnabled = $worldConfig->isWorldBlacklistEnabled();
		$isWhitelistEnabled = $worldConfig->isWorldWhitelistEnabled();

		if ($isBlacklistEnabled === $isWhitelistEnabled) {
			return true;
		}

		if ($isBlacklistEnabled) {
			return !in_array($world_name, $worldConfig->getBlacklistedWorlds(), true);
		}

		if ($isWhitelistEnabled) {
			return in_array($world_name, $worldConfig->getWhitelistedWorlds(), true);
		}

		return false;
	}

	/**
	 * Checks if the required virions/libraries are present before enabling the plugin.
	 *
	 * @throws DisablePluginException
	 */
	private function validateVirions() : void {
		$requiredVirions = [
			'libsounds' => SoundBuilder::class,
			'UpdateNotifier' => UpdateNotifier::class,
		];

		foreach ($requiredVirions as $name => $class) {
			if (!class_exists($class)) {
				$this->getLogger()->error($name . ' virion was not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer.');
				throw new DisablePluginException();
			}
		}
	}
}
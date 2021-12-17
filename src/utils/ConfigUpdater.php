<?php

declare(strict_types=1);

namespace aiptu\blockreplacer\utils;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use function basename;
use function explode;
use function rename;
use function str_replace;
use function trim;

final class ConfigUpdater
{
	public static function checkUpdate(PluginBase $plugin, Config $config, string $configKey, int $latestVersion, string $updateMessage = ''): bool
	{
		if (($config->exists($configKey)) && ((int) $config->get($configKey) === $latestVersion)) {
			return false;
		}

		$configData = self::getConfigData($config);
		$configPath = $configData['configPath'];
		$originalConfig = $configData['configName'];
		$oldConfig = $configData['oldConfigName'];

		if (trim($updateMessage) === '') {
			$updateMessage = "Your {$originalConfig} file is outdated. Your old {$originalConfig} has been saved as {$oldConfig} and a new {$originalConfig} file has been generated. Please update accordingly.";
		}

		rename($configPath . $originalConfig, $configPath . $oldConfig);

		$plugin->saveResource($originalConfig);

		$task = new ClosureTask(function () use ($plugin, $updateMessage): void {
			$plugin->getLogger()->critical($updateMessage);
		});

		$plugin->getScheduler()->scheduleDelayedTask($task, 3 * 20);

		return true;
	}

	private static function getConfigData(Config $config): array
	{
		$configPath = $config->getPath();
		$configData = explode('.', basename($configPath));

		$configName = $configData[0];
		$configExtension = $configData[1];

		$originalConfigName = $configName . '.' . $configExtension;
		$oldConfigName = $configName . '_old.' . $configExtension;

		$configPath = str_replace($originalConfigName, '', $configPath);
		$pluginPath = str_replace('plugin_data', 'plugins', $configPath);

		return [
			'configPath' => $configPath,
			'pluginPath' => $pluginPath,
			'configName' => $originalConfigName,
			'oldConfigName' => $oldConfigName,
		];
	}
}

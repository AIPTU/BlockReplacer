<?php

declare(strict_types=1);

namespace aiptu\blockreplacer;

use aiptu\blockreplacer\utils\ConfigProperty;
use aiptu\blockreplacer\utils\ConfigUpdater;
use aiptu\blockreplacer\utils\UpdateNotifyTask;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use function array_keys;
use function count;
use function explode;
use function gettype;
use function implode;
use function in_array;

final class BlockReplacer extends PluginBase
{
	private const MODE_BLACKLIST = 0;
	private const MODE_WHITELIST = 1;

	private const CONFIG_VERSION = 4;

	private int $mode;

	private ConfigProperty $configProperty;

	public function onEnable(): void
	{
		$this->configProperty = new ConfigProperty($this->getConfig());
		$this->checkConfig();

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);

		if ($this->getConfigProperty()->getPropertyBool('check-update', true)) {
			$this->getServer()->getAsyncPool()->submitTask(new UpdateNotifyTask($this->getDescription()->getName(), $this->getDescription()->getVersion()));
		}
	}

	public function getConfigProperty(): ConfigProperty
	{
		return $this->configProperty;
	}

	public function checkWorlds(World $world): bool
	{
		if ($this->mode === self::MODE_BLACKLIST) {
			return !(in_array($world->getFolderName(), $this->getConfigProperty()->getPropertyArray('worlds.list', []), true));
		}

		return in_array($world->getFolderName(), $this->getConfigProperty()->getPropertyArray('worlds.list', []), true);
	}

	public function checkPermission(): string
	{
		$configProperty = $this->getConfigProperty();

		$permission = new Permission($configProperty->getPropertyString('permission.name', 'blockreplacer.bypass'), $configProperty->getPropertyString('permission.description', 'Allows users to bypass block replacement.'));
		$permission_manager = PermissionManager::getInstance();
		$permission_manager->addPermission($permission);
		$permission_default_register = [
			'op' => static function () use ($permission_manager, $permission): void {
				$permission_manager->getPermission(DefaultPermissions::ROOT_OPERATOR)?->addChild($permission->getName(), true);
			},
			'all' => static function () use ($permission_manager, $permission): void {
				$permission_manager->getPermission(DefaultPermissions::ROOT_USER)?->addChild($permission->getName(), true);
			},
		];

		if (isset($permission_default_register[$permission_defaults = $configProperty->getPropertyString('permission.defaults', 'op')])) {
			$permission_default_register[$permission_defaults]();
		} else {
			throw new \InvalidArgumentException("Invalid permission.defaults value configured: \"{$permission_defaults}\" (expected one of: " . implode(', ', array_keys($permission_default_register)) . ')');
		}

		return $permission->getName();
	}

	private function checkConfig(): void
	{
		$this->getConfigProperty()->save();

		ConfigUpdater::checkUpdate($this, $this->getConfig(), 'config-version', self::CONFIG_VERSION);

		$this->checkPermission();
		$this->parseItems();
		$this->parseWorlds();
	}

	private function parseItems(): void
	{
		try {
			$defaultBlock = LegacyStringToItemParser::getInstance()->parse($this->getConfigProperty()->getPropertyString('blocks.default-replace', 'minecraft:bedrock'));
		} catch (LegacyStringToItemParserException $e) {
			throw $e;
		}

		foreach ($this->getConfigProperty()->getPropertyArray('blocks.list', []) as $value) {
			$explode = explode('=', $value);

			try {
				if (count($explode) === 1) {
					$fromBlock = LegacyStringToItemParser::getInstance()->parse($value);
				} elseif (count($explode) === 2) {
					$fromBlock = LegacyStringToItemParser::getInstance()->parse($explode[0]);
					$toBlock = LegacyStringToItemParser::getInstance()->parse($explode[1]);
				}
			} catch (LegacyStringToItemParserException $e) {
				throw $e;
			}
		}
	}

	private function parseWorlds(): void
	{
		match ($this->getConfigProperty()->getPropertyString('worlds.mode', 'blacklist')) {
			'blacklist' => $this->mode = self::MODE_BLACKLIST,
			'whitelist' => $this->mode = self::MODE_WHITELIST,
			default => throw new \InvalidArgumentException('Invalid mode selected, must be either "blacklist" or "whitelist"!'),
		};
	}
}

<?php

/*
 * Copyright (c) 2021-2022 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\utils;

use aiptu\blockreplacer\BlockReplacer;
use DiamondStrider1\Sounds\SoundFactory;
use DiamondStrider1\Sounds\SoundImpl;
use pocketmine\block\Block;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use function array_keys;
use function array_map;
use function count;
use function explode;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function rename;
use function trim;

final class ConfigHandler
{
	use SingletonTrait;

	private const CONFIG_VERSION = 2.0;

	private Config $config;

	private string $permission_defaults;
	private bool $auto_pickup;
	private Block $default_replace;
	private int $default_time;
	/** @array<int, array<int, int|Block|null>> */
	private array $list_blocks;
	private ?string $particle_from;
	private ?string $particle_to;
	private ?SoundImpl $sound_from;
	private ?SoundImpl $sound_to;
	private bool $enable_world_blacklist;
	/** @var array<string> */
	private array $blacklisted_worlds;
	private bool $enable_world_whitelist;
	/** @var array<string> */
	private array $whitelisted_worlds;

	public function __construct()
	{
		$this->initConfiguration();
	}

	public function initConfiguration(): void
	{
		BlockReplacer::getInstance()->saveDefaultConfig();
		$config = BlockReplacer::getInstance()->getConfig();
		if (!$config->exists('config-version') || ($config->get('config-version', self::CONFIG_VERSION) !== self::CONFIG_VERSION)) {
			BlockReplacer::getInstance()->getLogger()->warning('An outdated config was provided attempting to generate a new one...');
			if (!rename(BlockReplacer::getInstance()->getDataFolder() . 'config.yml', BlockReplacer::getInstance()->getDataFolder() . 'config.old.yml')) {
				BlockReplacer::getInstance()->getLogger()->critical('An unknown error occurred while attempting to generate the new config');
				BlockReplacer::getInstance()->getServer()->getPluginManager()->disablePlugin(BlockReplacer::getInstance());
			}
			BlockReplacer::getInstance()->reloadConfig();

			try {
				$config->set('config-version', self::CONFIG_VERSION);
				$config->save();
			} catch (\JsonException $e) {
				BlockReplacer::getInstance()->getLogger()->critical('An error occurred while attempting to generate the new config, ' . $e->getMessage());
			}
		}
		$this->config = $config;

		$this->permission_defaults = $this->expectString('permission-defaults', 'op');
		$permission = new Permission(BlockReplacer::PERMISSION, BlockReplacer::PERMISSION_DESCRIPTION);
		$permission_manager = PermissionManager::getInstance();
		$permission_manager->addPermission($permission);
		$permission_default_register = [
			'op' => static function () use ($permission_manager, $permission): void {
				$into_permission = $permission_manager->getPermission(DefaultPermissions::ROOT_OPERATOR) ?? throw new \RuntimeException('Could not obtain permission: ' . DefaultPermissions::ROOT_OPERATOR);
				$into_permission->addChild($permission->getName(), true);
			},
			'all' => static function () use ($permission_manager, $permission): void {
				$into_permission = $permission_manager->getPermission(DefaultPermissions::ROOT_USER) ?? throw new \RuntimeException('Could not obtain permission: ' . DefaultPermissions::ROOT_USER);
				$into_permission->addChild($permission->getName(), true);
			},
			'none' => static function (): void {
			},
		];

		if (isset($permission_default_register[$permission_defaults = $this->permission_defaults])) {
			$permission_default_register[$permission_defaults]();
		} else {
			throw new \InvalidArgumentException("Invalid permission-defaults value configured: \"{$permission_defaults}\" (expected one of: " . implode(', ', array_keys($permission_default_register)) . ')');
		}

		$this->auto_pickup = $this->expectBool('auto-pickup', true);

		$this->default_replace = BlockReplacer::getInstance()->getBlock($this->expectString('blocks.default-replace', 'bedrock'));
		$this->default_time = $this->expectInt('blocks.default-time', 60);

		$this->list_blocks = array_map(static function (string $blockInfo): array {
			$parts = explode(':', $blockInfo);
			$arr = [];

			if (count($parts) === 3) {
				$arr = [BlockReplacer::getInstance()->getBlock($parts[0]), BlockReplacer::getInstance()->getBlock($parts[1]), 20 * (int) $parts[2]];
			} elseif (count($parts) === 2) {
				$arr = [BlockReplacer::getInstance()->getBlock($parts[0]), BlockReplacer::getInstance()->getBlock($parts[1]), null];
			} else {
				$arr = [BlockReplacer::getInstance()->getBlock($parts[0]), null, null];
			}
			return $arr;
		}, $this->expectStringList('blocks.list', []));

		$particle_from = null;
		$particle_to = null;
		if ($this->expectBool('particles.enable', true)) {
			$from = $this->expectString('particles.from', 'minecraft:villager_happy');
			if (trim($from) !== '') {
				$particle_from = $from;
			}
			$to = $this->expectString('particles.to', 'minecraft:explosion_particle');
			if (trim($to) !== '') {
				$particle_to = $to;
			}
		}
		$this->particle_from = $particle_from;
		$this->particle_to = $particle_to;

		$sound_from = null;
		$sound_to = null;
		if ($this->expectBool('sounds.enable', true)) {
			$volume = $this->expectNumber('sounds.volume', 1.0);
			$pitch = $this->expectNumber('sounds.pitch', 1.0);

			$from = $this->expectString('sounds.from', 'random.orb');
			if (trim($from) !== '') {
				$sound_from = SoundFactory::create($from, $volume, $pitch);
			}
			$to = $this->expectString('sounds.to', 'random.explode');
			if (trim($to) !== '') {
				$sound_to = SoundFactory::create($to, $volume, $pitch);
			}
		}
		$this->sound_from = $sound_from;
		$this->sound_to = $sound_to;

		$this->enable_world_blacklist = $this->expectBool('worlds.enable-world-blacklist', false);
		$this->blacklisted_worlds = $this->expectStringList('worlds.blacklisted-worlds', []);
		$this->enable_world_whitelist = $this->expectBool('worlds.enable-world-whitelist', false);
		$this->whitelisted_worlds = $this->expectStringList('worlds.whitelisted-worlds', []);
	}

	public function isAutoPickupEnable(): bool
	{
		return $this->auto_pickup;
	}

	public function getDefaultReplace(): Block
	{
		return $this->default_replace;
	}

	public function getDefaultTime(): int
	{
		return 20 * $this->default_time;
	}

	public function getListBlocks(): array
	{
		return $this->list_blocks;
	}

	public function getParticleFrom(): ?string
	{
		return $this->particle_from;
	}

	public function getParticleTo(): ?string
	{
		return $this->particle_to;
	}

	public function getSoundFrom(): ?SoundImpl
	{
		return $this->sound_from;
	}

	public function getSoundTo(): ?SoundImpl
	{
		return $this->sound_to;
	}

	public function isWorldBlacklistEnable(): bool
	{
		return $this->enable_world_blacklist;
	}

	public function getBlacklistedWorlds(): array
	{
		return $this->blacklisted_worlds;
	}

	public function isWorldWhitelistEnable(): bool
	{
		return $this->enable_world_whitelist;
	}

	public function getWhitelistedWorlds(): array
	{
		return $this->whitelisted_worlds;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function expectInt(string $key, int $default): int
	{
		$value = $this->config->getNested($key, $default);

		if (!is_int($value)) {
			throw new \InvalidArgumentException('An error occurred in the configuration with the key ' . $key . ': Expected integer, got ' . gettype($value));
		}

		return $value;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function expectNumber(string $key, float $default): float
	{
		$value = $this->config->getNested($key, $default);

		if (!is_int($value) && !is_float($value)) {
			throw new \InvalidArgumentException('An error occurred in the configuration with the key ' . $key . ': Expected number, got ' . gettype($value));
		}

		return (float) $value;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function expectBool(string $key, bool $default): bool
	{
		$value = $this->config->getNested($key, $default);

		if (!is_bool($value)) {
			throw new \InvalidArgumentException('An error occurred in the configuration with the key ' . $key . ': Expected true/false, got ' . gettype($value));
		}

		return $value;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function expectString(string $key, string $default): string
	{
		$value = $this->config->getNested($key, $default);

		if (!is_string($value)) {
			throw new \InvalidArgumentException('An error occurred in the configuration with the key ' . $key . ': Expected string, got ' . gettype($value));
		}

		return $value;
	}

	/**
	 * @param list<string> $default
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return list<string>
	 */
	private function expectStringList(string $key, array $default): array
	{
		$value = $this->config->getNested($key, $default);

		if (!is_array($value)) {
			throw new \InvalidArgumentException('An error occurred in the configuration with the key ' . $key . ': Expected a list, got ' . gettype($value));
		}

		return $value;
	}
}

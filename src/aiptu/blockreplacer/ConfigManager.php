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

namespace aiptu\blockreplacer;

use DiamondStrider1\Sounds\SoundFactory;
use DiamondStrider1\Sounds\SoundImpl;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use function array_map;
use function count;
use function explode;
use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function rename;
use function trim;

final class ConfigManager
{
	use SingletonTrait;

	private const CONFIG_VERSION = 1.9;

	private Config $config;

	private int $cooldown;
	private bool $autoPickup;
	private Item $defaultReplace;
	/** @var array<array{Item, Item|null}> */
	private array $listBlocks;
	private ?string $particleFrom;
	private ?string $particleTo;
	private ?SoundImpl $soundFrom;
	private ?SoundImpl $soundTo;
	private bool $enableWorldBlacklist;
	/** @var array<string> */
	private array $blacklistedWorlds;
	private bool $enableWorldWhitelist;
	/** @var array<string> */
	private array $whitelistedWorlds;

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

		$this->cooldown = $this->expectInt('cooldown', 60);
		$this->autoPickup = $this->expectBool('auto-pickup', true);

		$this->defaultReplace = BlockReplacer::getInstance()->checkItem($this->expectString('blocks.default-replace', 'bedrock'));
		$this->listBlocks = array_map(static function (string $item): array {
			$explode = explode('=', $item);
			return (count($explode) === 2) ? [BlockReplacer::getInstance()->checkItem($explode[0]), BlockReplacer::getInstance()->checkItem($explode[1])] : [BlockReplacer::getInstance()->checkItem($item), null];
		}, $this->expectStringList('blocks.list', []));

		$particleFrom = null;
		$particleTo = null;
		if ($this->expectBool('particles.enable', true)) {
			$from = $this->expectString('particles.from', 'minecraft:villager_happy');
			if (trim($from) !== '') {
				$particleFrom = $from;
			}
			$to = $this->expectString('particles.to', 'minecraft:explosion_particle');
			if (trim($to) !== '') {
				$particleTo = $to;
			}
		}
		$this->particleFrom = $particleFrom;
		$this->particleTo = $particleTo;

		$soundFrom = null;
		$soundTo = null;
		if ($this->expectBool('sounds.enable', true)) {
			$volume = $this->expectNumber('sounds.volume', 1.0);
			$pitch = $this->expectNumber('sounds.pitch', 1.0);

			$from = $this->expectString('sounds.from', 'random.orb');
			if (trim($from) !== '') {
				$soundFrom = SoundFactory::create($from, $volume, $pitch);
			}
			$to = $this->expectString('sounds.to', 'random.explode');
			if (trim($to) !== '') {
				$soundTo = SoundFactory::create($to, $volume, $pitch);
			}
		}
		$this->soundFrom = $soundFrom;
		$this->soundTo = $soundTo;

		$this->enableWorldBlacklist = $this->expectBool('worlds.enable-world-blacklist', false);
		$this->blacklistedWorlds = $this->expectStringList('worlds.blacklisted-worlds', []);
		$this->enableWorldWhitelist = $this->expectBool('worlds.enable-world-whitelist', false);
		$this->whitelistedWorlds = $this->expectStringList('worlds.whitelisted-worlds', []);
	}

	public function isAutoPickupEnable(): bool
	{
		return $this->autoPickup;
	}

	public function getCooldown(): int
	{
		return 20 * $this->cooldown;
	}

	public function getDefaultReplace(): Item
	{
		return $this->defaultReplace;
	}

	/**
	 * @return array<array{Item, Item|null}>
	 */
	public function getListBlocks(): array
	{
		return $this->listBlocks;
	}

	public function getParticleFrom(): ?string
	{
		return $this->particleFrom;
	}

	public function getParticleTo(): ?string
	{
		return $this->particleTo;
	}

	public function getSoundFrom(): ?SoundImpl
	{
		return $this->soundFrom;
	}

	public function getSoundTo(): ?SoundImpl
	{
		return $this->soundTo;
	}

	public function isWorldBlacklistEnable(): bool
	{
		return $this->enableWorldBlacklist;
	}

	/**
	 * @return array<string>
	 */
	public function getBlacklistedWorlds(): array
	{
		return $this->blacklistedWorlds;
	}

	public function isWorldWhitelistEnable(): bool
	{
		return $this->enableWorldWhitelist;
	}

	/**
	 * @return array<string>
	 */
	public function getWhitelistedWorlds(): array
	{
		return $this->whitelistedWorlds;
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

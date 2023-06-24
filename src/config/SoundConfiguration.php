<?php

/*
 * Copyright (c) 2021-2023 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\config;

use DiamondStrider1\Sounds\SoundFactory;
use DiamondStrider1\Sounds\SoundImpl;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use function trim;

final class SoundConfiguration
{
	public function __construct(
		private bool $enabled,
		private ?SoundImpl $from,
		private ?SoundImpl $to,
	) {}

	/**
	 * @param array<int|string, mixed> $data
	 */
	public static function fromData(array $data): self
	{
		$volume = ConfigurationHelper::readNumber($data, 'volume');
		$pitch = ConfigurationHelper::readNumber($data, 'pitch');
		$from = ConfigurationHelper::readString($data, 'from');
		$to = ConfigurationHelper::readString($data, 'to');
		$instance = new self(
			ConfigurationHelper::readBool($data, 'enabled'),
			(trim($from) === '') ? null : SoundFactory::create($from, $volume, $pitch),
			(trim($to) === '') ? null : SoundFactory::create($to, $volume, $pitch),
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function addFrom(World $world, Vector3 $position): void
	{
		$sound = $this->from;
		if ($sound !== null) {
			$this->add($world, $position, $sound);
		}
	}

	public function addTo(World $world, Vector3 $position): void
	{
		$sound = $this->to;
		if ($sound !== null) {
			$this->add($world, $position, $sound);
		}
	}

	private function add(World $world, Vector3 $position, SoundImpl $sound): void
	{
		if (!$this->enabled) {
			return;
		}

		$world->addSound($position, $sound);
	}
}

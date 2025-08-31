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

namespace aiptu\blockreplacer\config;

use aiptu\blockreplacer\libs\_29235721a4d510a4\aiptu\libsounds\SoundBuilder;
use aiptu\blockreplacer\libs\_29235721a4d510a4\aiptu\libsounds\SoundInstance;
use aiptu\blockreplacer\libs\_29235721a4d510a4\aiptu\libsounds\SoundTypes;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use function trim;

class SoundConfiguration {
	public function __construct(
		private bool $enabled,
		private ?SoundInstance $from,
		private ?SoundInstance $to,
	) {}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$volume = ConfigurationHelper::readNumber($data, 'volume');
		$pitch = ConfigurationHelper::readNumber($data, 'pitch');
		$from = ConfigurationHelper::readString($data, 'from');
		$to = ConfigurationHelper::readString($data, 'to');
		$instance = new self(
			ConfigurationHelper::readBool($data, 'enabled'),
			(trim($from) === '') ? null : SoundBuilder::create(SoundTypes::fromValue($from), $volume, $pitch),
			(trim($to) === '') ? null : SoundBuilder::create(SoundTypes::fromValue($to), $volume, $pitch),
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function addFrom(World $world, Vector3 $position) : void {
		$sound = $this->from;
		if ($sound !== null) {
			$this->add($world, $position, $sound);
		}
	}

	public function addTo(World $world, Vector3 $position) : void {
		$sound = $this->to;
		if ($sound !== null) {
			$this->add($world, $position, $sound);
		}
	}

	private function add(World $world, Vector3 $position, SoundInstance $sound) : void {
		if (!$this->enabled) {
			return;
		}

		$world->addSound($position, $sound);
	}
}
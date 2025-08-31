<?php

/*
 * Copyright (c) 2024-2025 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/libsounds
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\libs\_72490a5c9c384c0c\aiptu\libsounds;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\world\sound\Sound;

class SoundInstance implements Sound {
	private float $volume = 1.0;
	private float $pitch = 1.0;

	public function __construct(
		private string $name,
	) {}

	public function getVolume() : float {
		return $this->volume;
	}

	public function setVolume(float $volume) : void {
		$this->volume = $volume;
	}

	public function getPitch() : float {
		return $this->pitch;
	}

	public function setPitch(float $pitch) : void {
		$this->pitch = $pitch;
	}

	public function encode(Vector3 $pos) : array {
		return [PlaySoundPacket::create($this->name, $pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $this->volume, $this->pitch)];
	}
}
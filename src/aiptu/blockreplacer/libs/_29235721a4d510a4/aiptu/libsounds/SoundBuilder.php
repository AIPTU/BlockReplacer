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

namespace aiptu\blockreplacer\libs\_29235721a4d510a4\aiptu\libsounds;

class SoundBuilder {
	public static function create(
		SoundTypes $soundType,
		float $volume = 1.0,
		float $pitch = 1.0
	) : SoundInstance {
		$sound = new SoundInstance($soundType->value);
		$sound->setVolume($volume);
		$sound->setPitch($pitch);

		return $sound;
	}
}
<?php

/*
 * Copyright (c) 2024 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/libsounds
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\libs\_54bf091beaf2dda0\aiptu\libsounds;

class InvalidSoundTypeException extends \Exception {
	public function __construct(string $value) {
		parent::__construct("Invalid sound type: {$value}");
	}
}
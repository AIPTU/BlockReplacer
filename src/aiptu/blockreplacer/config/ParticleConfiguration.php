<?php

/*
 * Copyright (c) 2021-2024 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\config;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\World;
use function trim;

class ParticleConfiguration {
	public function __construct(
		private bool $enabled,
		private ?string $from,
		private ?string $to,
	) {}

	/**
	 * @param array<int|string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$from = ConfigurationHelper::readString($data, 'from');
		$to = ConfigurationHelper::readString($data, 'to');
		$instance = new self(
			ConfigurationHelper::readBool($data, 'enabled'),
			(trim($from) === '') ? null : $from,
			(trim($to) === '') ? null : $to,
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function addFrom(World $world, Vector3 $position) : void {
		$particle = $this->from;
		if ($particle !== null) {
			$this->add($world, $position, $particle);
		}
	}

	public function addTo(World $world, Vector3 $position) : void {
		$particle = $this->to;
		if ($particle !== null) {
			$this->add($world, $position, $particle);
		}
	}

	private function add(World $world, Vector3 $position, string $particle) : void {
		if (!$this->enabled) {
			return;
		}

		NetworkBroadcastUtils::broadcastPackets($world->getPlayers(), [
			SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $position->up(), $particle, null),
		]);
	}
}
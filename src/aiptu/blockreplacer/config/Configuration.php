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

class Configuration {
	public function __construct(
		private AutoPickupConfiguration $auto_pickup_configuration,
		private BlockConfiguration $block_configuration,
		private ParticleConfiguration $particle_configuration,
		private SoundConfiguration $sound_configuration,
		private WorldConfiguration $world_configuration,
		private NotificationConfiguration $notification_configuration,
	) {}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$permission_configuration = PermissionConfiguration::fromData(ConfigurationHelper::readMap($data, 'permission'));
		$auto_pickup_configuration = AutoPickupConfiguration::fromData(ConfigurationHelper::readMap($data, 'auto-pickup'));
		$block_configuration = BlockConfiguration::fromData(ConfigurationHelper::readMap($data, 'blocks'));
		$particle_configuration = ParticleConfiguration::fromData(ConfigurationHelper::readMap($data, 'particles'));
		$sound_configuration = SoundConfiguration::fromData(ConfigurationHelper::readMap($data, 'sounds'));
		$world_configuration = WorldConfiguration::fromData(ConfigurationHelper::readMap($data, 'worlds'));
		$notification_configuration = NotificationConfiguration::fromData(ConfigurationHelper::readMap($data, 'notifications'));
		ConfigurationHelper::checkForUnread($data);
		return new self($auto_pickup_configuration, $block_configuration, $particle_configuration, $sound_configuration, $world_configuration, $notification_configuration);
	}

	public function getAutoPickup() : AutoPickupConfiguration {
		return $this->auto_pickup_configuration;
	}

	public function getBlock() : BlockConfiguration {
		return $this->block_configuration;
	}

	public function getParticle() : ParticleConfiguration {
		return $this->particle_configuration;
	}

	public function getSound() : SoundConfiguration {
		return $this->sound_configuration;
	}

	public function getWorld() : WorldConfiguration {
		return $this->world_configuration;
	}

	public function getNotification() : NotificationConfiguration {
		return $this->notification_configuration;
	}
}
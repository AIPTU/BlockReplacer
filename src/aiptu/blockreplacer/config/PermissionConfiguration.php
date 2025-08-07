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

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use function array_keys;
use function implode;

class PermissionConfiguration {
	public const NAME = 'blockreplacer.bypass';
	public const DESCRIPTION = 'Allows users to bypass block replacement';

	public function __construct(
		private string $defaults,
	) {
		$permission = new Permission(self::NAME, self::DESCRIPTION);
		$permission_manager = PermissionManager::getInstance();
		$permission_manager->addPermission($permission);
		$permission_default_register = [
			'op' => static function () use ($permission_manager, $permission) : void {
				$into_permission = $permission_manager->getPermission(DefaultPermissions::ROOT_OPERATOR) ?? throw new BadConfigurationException('Could not obtain permission: ' . DefaultPermissions::ROOT_OPERATOR);
				$into_permission->addChild($permission->getName(), true);
			},
			'all' => static function () use ($permission_manager, $permission) : void {
				$into_permission = $permission_manager->getPermission(DefaultPermissions::ROOT_USER) ?? throw new BadConfigurationException('Could not obtain permission: ' . DefaultPermissions::ROOT_USER);
				$into_permission->addChild($permission->getName(), true);
			},
			'none' => static function () : void {},
		];

		if (isset($permission_default_register[$permission_defaults = $this->defaults])) {
			$permission_default_register[$permission_defaults]();
		} else {
			throw new BadConfigurationException("Invalid permission.defaults value configured: \"{$permission_defaults}\" (expected one of: " . implode(', ', array_keys($permission_default_register)) . ')');
		}
	}

	/**
	 * @param array<int|string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$instance = new self(
			ConfigurationHelper::readString($data, 'defaults'),
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}
}
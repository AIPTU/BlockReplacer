<?php

declare(strict_types=1);

namespace aiptu\blockreplacer\utils;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use function json_decode;
use function version_compare;
use function vsprintf;

final class UpdateNotifyTask extends AsyncTask
{
	private const POGGIT_RELEASES_URL = 'https://poggit.pmmp.io/releases.min.json?name=';

	public function __construct(private string $pluginName, private string $pluginVersion)
	{
	}

	public function onRun(): void
	{
		$json = Internet::getURL(self::POGGIT_RELEASES_URL . $this->pluginName, 10, [], $err);
		$highestVersion = $this->pluginVersion;
		$artifactUrl = '';
		$api = '';
		if ($json !== null) {
			$releases = json_decode($json->getBody(), true);
			if ($releases !== null) { // Poggit is Down!
				foreach ($releases as $release) {
					if (version_compare($highestVersion, $release['version'], '>=')) {
						continue;
					}
					$highestVersion = $release['version'];
					$artifactUrl = $release['artifact_url'];
					$api = $release['api'][0]['from'] . ' - ' . $release['api'][0]['to'];
				}
			}
		}

		$this->setResult([$highestVersion, $artifactUrl, $api, $err]);
	}

	public function onCompletion(): void
	{
		$plugin = Server::getInstance()->getPluginManager()->getPlugin($this->pluginName);
		if ($plugin === null) {
			return;
		}

		[$highestVersion, $artifactUrl, $api, $err] = $this->getResult();
		if ($err !== null) {
			$plugin->getLogger()->error("Update notify error: {$err}");

			return;
		}

		if ($highestVersion !== $this->pluginVersion) {
			$artifactUrl = $artifactUrl . '/' . $this->pluginName . '_' . $highestVersion . '.phar';
			$plugin->getLogger()->notice(vsprintf('Version %s has been released for API %s. Download the new release at %s', [$highestVersion, $api, $artifactUrl]));
		}
	}
}

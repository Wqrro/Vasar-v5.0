<?php

declare(strict_types=1);

namespace Warro\utilities;

use InvalidArgumentException;
use JsonException;
use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\LegacySkinAdapter;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Warro\Base;

class SkinAdapterPersona extends LegacySkinAdapter
{

	private array $bounds = [];

	public function __construct(private Base $plugin)
	{
		$cubes = $this->getCubes(json_decode(stream_get_contents($this->plugin->getResource('humanoid.json')), true)['geometry.humanoid']);
		$this->bounds[0] = $this->getBounds($cubes);
		$this->bounds[1] = $this->getBounds($cubes, 2.0);
	}

	/**
	 * @throws JsonException
	 */
	public function fromSkinData(SkinData $data): Skin
	{
		if ($data->isPersona() or $data->isPremium()) {
			return Base::getInstance()->utils->vasarSkin;
		} else {
			$capeData = $data->isPersonaCapeOnClassic() ? '' : $data->getCapeImage()->getData();
			$resourcePatch = json_decode($data->getResourcePatch(), true);
			if (is_array($resourcePatch) and isset($resourcePatch['geometry']['default']) and is_string($resourcePatch['geometry']['default'])) {
				$geometryName = $resourcePatch['geometry']['default'];
			} else {
				throw new InvalidSkinException('Missing geometry name field');
			}
			$skin = new Skin($data->getSkinId(), $data->getSkinImage()->getData(), $capeData, $geometryName, $data->getGeometryData());
		}
		if ($this->getSkinTransparencyPercentage($skin->getSkinData()) > 4) {
			return Base::getInstance()->utils->vasarSkin;
		}
		return $skin;
	}

	private function getSkinTransparencyPercentage(string $skinData): int
	{
		switch (strlen($skinData)) {
			case 8192:
				$maxX = 64;
				$maxY = 32;
				$bounds = $this->bounds[0];
				break;
			case 16384:
				$maxX = 64;
				$maxY = 64;
				$bounds = $this->bounds[0];
				break;
			case 65536:
				$maxX = 128;
				$maxY = 128;
				$bounds = $this->bounds[1];
				break;
			default:
				throw new InvalidArgumentException('Inappropriate skin data length: ' . strlen($skinData));
		}
		$transparentPixels = $pixels = 0;
		foreach ($bounds as $bound) {
			if ($bound['max']['x'] > $maxX || $bound['max']['y'] > $maxY) {
				continue;
			}
			for ($y = $bound['min']['y']; $y <= $bound['max']['y']; $y++) {
				for ($x = $bound['min']['x']; $x <= $bound['max']['x']; $x++) {
					$key = (($maxX * $y) + $x) * 4;
					$a = ord($skinData[$key + 3]);
					if ($a < 127) {
						++$transparentPixels;
					}
					++$pixels;
				}
			}
		}
		return (int)round($transparentPixels * 100 / max(1, $pixels));
	}

	private function getCubes(array $geometryData): array
	{
		$cubes = [];
		foreach ($geometryData['bones'] as $bone) {
			if (!isset($bone['cubes'])) {
				continue;
			}
			if ($bone['mirror'] ?? false) {
				throw new InvalidArgumentException('Unsupported geometry data');
			}
			foreach ($bone['cubes'] as $cubeData) {
				$cube = [];
				$cube['x'] = $cubeData['size'][0];
				$cube['y'] = $cubeData['size'][1];
				$cube['z'] = $cubeData['size'][2];
				$cube['uvX'] = $cubeData['uv'][0];
				$cube['uvY'] = $cubeData['uv'][1];
				$cubes[] = $cube;
			}
		}
		return $cubes;
	}

	private function getBounds(array $cubes, float $scale = 1.0): array
	{
		$bounds = [];
		foreach ($cubes as $cube) {
			$x = (int)($scale * $cube['x']);
			$y = (int)($scale * $cube['y']);
			$z = (int)($scale * $cube['z']);
			$uvX = (int)($scale * $cube['uvX']);
			$uvY = (int)($scale * $cube['uvY']);
			$bounds[] = ['min' => ['x' => $uvX + $z, 'y' => $uvY], 'max' => ['x' => $uvX + $z + (2 * $x) - 1, 'y' => $uvY + $z - 1]];
			$bounds[] = ['min' => ['x' => $uvX, 'y' => $uvY + $z], 'max' => ['x' => $uvX + (2 * ($z + $x)) - 1, 'y' => $uvY + $z + $y - 1]];
		}
		return $bounds;
	}
}
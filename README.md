# BlockReplacer

[![](https://poggit.pmmp.io/shield.state/BlockReplacer)](https://poggit.pmmp.io/p/BlockReplacer)
[![](https://poggit.pmmp.io/shield.dl.total/BlockReplacer)](https://poggit.pmmp.io/p/BlockReplacer)

A PocketMine-MP plugin that replaces a block with another block at a predetermined time.

# Features

- Automatic update checker.
- Permission bypass.
- Custom cooldown replacement.
- Automatic item pickup support.
- Custom block replacement.
- Custom drop block.
- World blacklist and whitelist support.
- Support for sound customization.
- Support for particle customization.
- Support for block replacement when the server is stopped.
- Support block replacement in different time.
- Lightweight and open source ❤️

# Permissions

- Permission `blockreplacer.bypass` allows the user to bypass block replacement.

# Default Config
```yaml
---
# Permission defaults for the "blockreplacer.bypass" permission
# This permission allows players to bypass block replacement.
# Valid values:
#   op: all server operators (ops) are assigned this permission by default
#   all: everyone is assigned this permission by default
#   none: no one is assigned this permission by default
permission:
  defaults: "op"

# Dropped items will be automatically added to the player's inventory.
# If the player's inventory is full, the item will be automatically dropped near the player.
# This will also include experience points.
auto-pickup:
  enabled: true

blocks:
  # The default block is used as a replacement.
  default-replace: "air"
  # The default time is used as a replacement.
  # The time in seconds when the block will be replaced with the previous block.
  default-time: 60
  # List of blocks to be replaced.
  # This should also always be wrapped in quotes to ensure it is parsed correctly.
  list:
    # This should follow the format: "block_from=block_to=time".
    # If "block_to" is not set, it will replaced to the default replacement block.
    # If "time" is not set, it will replaced to the default replacement time.
    "cobblestone=stone=5": # It will be replaced from cobblestone to stone and will be replaced with the previous block within 5 seconds.
      # This should follow the format: "item:amount:chance".
      drops: []
    "oak_log=spruce_log=10":
      drops:
        - item: "spruce_log"
          amount: 1
          chance: 70
        - item: "spruce_leaves"
          amount: 2
          chance: 50
    "coal_ore=stone": # It will be replaced to the default replacement time.
      drops:
        - item: "coal"
          amount: 2
          chance: 90
    "diamond_ore=stone":
      drops:
        - item: "diamond_sword"
          amount: 1
          chance: 1
          name: "&cLifesteal &4Sword"
          lore:
            - "&6Steals health upon hitting enemy."
            - "&b!!!"
          enchantments:
            - name: "sharpness"
              level: 3
            - name: "lifesteal"
              level: 1
    "gold_ore": # It will be replaced to the default replacement block and default replacement time.
      drops:
        - item: "gold_ingot"
          amount: 1
          chance: 20
        - item: "gold_nugget"
          amount: 1
          chance: 10
    "iron_ore=bedrock":
      drops:
        - item: "iron_ingot"
          amount: 1
          chance: 20
        - item: "iron_nugget"
          amount: 1
          chance: 10
    "sweet_berry_bush:3=sweet_berry_bush:1=5":
      drops: []

particles:
  # Whether to add particles when destroying blocks.
  enabled: true
  # The name of the particle that will be added when destroying the previous block.
  from: "minecraft:villager_happy"
  # The name of the particle that will be added when replacing the block after it.
  to: "minecraft:explosion_particle"

sounds:
  # Whether to add sound when destroying blocks.
  enabled: true
  # Set the volume sound.
  volume: 1
  # Set the pitch sound.
  pitch: 1
  # The name of the sound that will be added when destroying the previous block.
  from: "random.orb"
  # The name of the sound that will be added when replacing the block after it.
  to: "random.explode"

worlds:
  # Set this to true if you want to use the blacklisted-worlds setting.
  # If both enabled-world-blacklist and enabled-world-whitelist are set to the same setting,
  # the block will be replaced for all worlds.
  enabled-world-blacklist: false
  # If enabled-world-blacklist is set to true, the block will be replaced for all worlds,
  # except the world mentioned here.
  blacklisted-worlds:
    - "blacklistedworld1"
    - "blacklistedworld2"
  # Set this to true if you want to use the whitelisted-worlds setting.
  # If both enabled-world-blacklist and enabled-world-blacklist are set to the same setting,
  # the block will not be replaced for all worlds.
  enabled-world-whitelist: false
  # If enabled-world-whitelist is set to true, blocks will not be replaced for all worlds,
  # except the worlds mentioned here.
  whitelisted-worlds:
    - "whitelistedworld1"
    - "whitelistedworld2"
...

```

# Upcoming Features

- Currently none planned. You can contribute or suggest for new features.

# Additional Notes

- If you find bugs or want to give suggestions, please visit [here](https://github.com/AIPTU/BlockReplacer/issues).
- We accept all contributions! If you want to contribute, please make a pull request in [here](https://github.com/AIPTU/BlockReplacer/pulls).
- Icons made from [www.flaticon.com](https://www.flaticon.com)

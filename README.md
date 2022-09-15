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
- World blacklist and whitelist support.
- Support for sound customization.
- Support for particle customization.
- Lightweight and open source ❤️

# Permissions

- Permission `blockreplacer.bypass` allows the user to bypass block replacement.

# Default Config
```yaml
---
# Do not change this (Only for internal use)!
config-version: 2.0

# The time in seconds when the block will be replaced with the previous block.
cooldown: 60 

# Dropped items will be automatically added to the player's inventory.
# If the player's inventory is full, the item will be automatically dropped near the player.
auto-pickup: true

blocks:
  # The default block to use as a replacement.
  default-replace: "bedrock"
  # List of blocks to be replaced.
  list:
    - "cobblestone=stone" # It will be replaced from cobblestone to stone.
    - "dirt=grass"
    - "oak_log=spruce_log"
    - "coal_ore" # It will be replaced to the default replacement block.
    - "diamond_ore"
    - "gold_ore"
    - "iron_ore"

# Add particles when you destroy blocks.
particles:
  # The name of the particle that will be added when destroying the previous block.
  from: "minecraft:villager_happy"
  # The name of the particle that will be added when replacing the block after it.
  to: "minecraft:explosion_particle"

# Add sound when you destroy blocks.
sounds:
  # Do you want to add sound?
  enable: true
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
  # If both enable-world-blacklist and disable-world-blacklist are set to the same setting,
  # the block will be replaced for all worlds.
  enable-world-blacklist: false
  # If enable-world-blacklist is set to true, the block will be replaced for all worlds,
  # except the world mentioned here.
  blacklisted-worlds:
    - "blacklistedworld1"
    - "blacklistedworld2"
  # Set this to true if you want to use the whitelisted-worlds setting.
  # If both enable-world-blacklist and disable-world-blacklist are set to the same setting,
  # the block will not be replaced for all worlds.
  enable-world-whitelist: false
  # If enable-world-whitelist is set to true, blocks will not be replaced for all worlds,
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

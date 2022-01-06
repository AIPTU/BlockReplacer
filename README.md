# BlockReplacer

[![Discord](https://img.shields.io/discord/830063409000087612?color=7389D8&label=discord)](https://discord.com/invite/EggNF9hvGv)
[![Poggit State](https://poggit.pmmp.io/shield.state/BlockReplacer)](https://poggit.pmmp.io/p/BlockReplacer)
[![Poggit Download Total](https://poggit.pmmp.io/shield.dl.total/BlockReplacer)](https://poggit.pmmp.io/p/BlockReplacer)

A PocketMine-MP plugin which replaces block to another block at predefined time.

# Features

- Permission bypass.
- Custom block replacement.
- Custom cooldown replacement.
- Auto pickup support.
- Auto update checker.
- Per world support.
- Lightweight and open source ❤️

# Permissions

- Permission `blockreplacer.bypass` allows the user to bypass block replacement.

# Default Config
```yaml
---
# Do not change this (Only for internal use)!
config-version: 1.3

# The total amount of time in a matter of seconds that the block will be replaced with the previous block.
cooldown: 60 

# Dropped items will be automatically added to the player's inventory.
# If the player inventory is full, the item will automatically be dropped near the player.
auto-pickup: true

# # This permission allows the player to change blocks.
permission:
  # Permission name.
  name: "blockreplacer.bypass"
  # Permission description.
  description: "Allows the user to bypass block replacement."
  # op: all server operators (ops) are granted this permission by default.
  # all: everyone is granted this permission by default.
  defaults: "op"

blocks:
  # Default block to use as a replacement.
  # If the block has meta, you can use the format "minecraft:id:meta".
  default-replace: "minecraft:bedrock"
  # List of blocks to be replaced.
  # If the block has a meta, you can use the format "minecraft:id:meta".
  # If you want multiple blocks replaced differently, you can use the format "minecraft:id:meta=minecraft:id:meta".
  list:
    - "minecraft:coal_ore"
    - "minecraft:diamond_ore"
    - "minecraft:gold_ore"
    - "minecraft:iron_ore"
    - "minecraft:log" # Oak log.
    - "minecraft:log:1" # Spruce log.
    - "minecraft:cobblestone=minecraft:stone" # Cobblestone will be replaced as Stone.
    - "minecraft:dirt=minecraft:grass" # Dirt will be replaced as Grass.

worlds:
  # The mode can be either "blacklist" or "whitelist".
  # Blacklist mode will not replace blocks according to the name of a predefined world folder and will only replace blocks around the world.
  # Whitelist mode will only replace blocks according to the name of a predefined world folder and will not replace blocks around the world.
  mode: "blacklist"
  # List of world folder names to blacklist/whitelist (depending on the mode set above).
  # Leave it blank if you want blocks replaced all over the world.
  list:
    - "world"
...

```

# Upcoming Features

- Currently none planned. You can contribute or suggest for new features.

# Additional Notes

- If you find bugs or want to give suggestions, please visit [here](https://github.com/AIPTU/BlockReplacer/issues).
- We accept any contributions! If you want to contribute please make a pull request in [here](https://github.com/AIPTU/BlockReplacer/pulls).
- Icons made from [www.flaticon.com](https://www.flaticon.com)

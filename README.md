# BlockReplacer

[![](https://poggit.pmmp.io/shield.state/BlockReplacer)](https://poggit.pmmp.io/p/BlockReplacer)
[![](https://poggit.pmmp.io/shield.dl.total/BlockReplacer)](https://poggit.pmmp.io/p/BlockReplacer)

A PocketMine-MP plugin which replaces block to another block at predefined time.

# Features

- Auto update checker.
- Permission bypass.
- Custom cooldown replacement.
- Auto pickup support.
- Custom block replacement.
- World blacklist and whitelist support.
- Lightweight and open source ❤️

# Permissions

- Permission `blockreplacer.bypass` allows the user to bypass block replacement.

# Default Config
```yaml
---
# Do not change this (Only for internal use)!
config-version: 1.7

# The time in seconds when the block will be replaced with the previous block.
cooldown: 60 

# Dropped items will be automatically added to the player's inventory.
# If the player inventory is full, the item will automatically be dropped near the player.
auto-pickup: true

# Default block to use as a replacement.
# If the block has meta, you can use the format "id:meta".
default-replace: "bedrock"
# List of blocks to be replaced.
# If the block has a meta, you can use the format "id:meta".
list-blocks:
  - "cobblestone=stone" # Cobblestone will be replaced to stone.
  - "dirt=grass"
  - "oak_log=oak_log:1"
  - "coal_ore" # It will be replaced to the default replacement block.
  - "diamond_ore"
  - "gold_ore"
  - "iron_ore"

# Set this to true if you want to use the blacklisted-worlds settings.
# If both enable-world-blacklist and disable-world-blacklist are set to the same setting,
# block will be replaced for all worlds.
enable-world-blacklist: false
# If enable-world-blacklist is set to true, block will be replaced for all worlds,
# except the worlds mentioned here.
blacklisted-worlds:
  - "blacklistedworld1"
  - "blacklistedworld2"

# Set this to true if you want to use the whitelisted-worlds settings.
# If both enable-world-blacklist and disable-world-blacklist are set to the same setting,
# block will not be replaced for all worlds.
enable-world-whitelist: false
# If enable-world-whitelist is set to true, block will not be replaced for all worlds,
# except the worlds mentioned here.
whitelisted-worlds:
  - "whitelistedworld1"
  - "whitelistecworld2"
...

```

# Upcoming Features

- Currently none planned. You can contribute or suggest for new features.

# Additional Notes

- If you find bugs or want to give suggestions, please visit [here](https://github.com/AIPTU/BlockReplacer/issues).
- We accept all contributions! If you want to contribute, please make a pull request in [here](https://github.com/AIPTU/BlockReplacer/pulls).
- Icons made from [www.flaticon.com](https://www.flaticon.com)

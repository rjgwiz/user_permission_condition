# User Permission Condition

Machine name: _user_permission_condition_

## Overview

Provides a 'User Permission' condition for Drupal 8.4.x, useful for context aware conditions based on user permissions.

Ideally this makes its way into Drupal Core's user module alongside `user/src/Plugin/Condition/UserRole.php`.

## Use Cases

- Block visibility
- Page Manager pages `Page access`
- Page Manager variants `Selection criteria` (variant access).
- Chaos tool suite (ctools)
- Anything that uses a context aware condition plugin (e.g. uses `ContextAwarePluginManagerInterface` to find classes extend `ConditionPluginBase` which implements `ContainerFactoryPluginInterface`).

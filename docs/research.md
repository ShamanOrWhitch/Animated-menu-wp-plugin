# Radial menu research

The 0.3.0 layout was redesigned after comparing established radial/orbit UI implementations.

## Zumerlab Orbit

https://github.com/zumerlab/orbit

Relevant model: center/gravity spot, orbit/ring, satellites, with radial layout as a composable primitive. The project is MIT licensed. Official examples include a radial-menu layout.

## SyntaxSerenity RadialFlow

https://github.com/SyntaxSerenity-dev/RadialFlow

Relevant features: full-circle and arc layouts, responsive behavior, viewport-aware positioning, compact menu item handling, real anchor support, and configurable visible item counts. The project is MIT licensed.

## axln radial-menu-js

https://github.com/axln/radial-menu-js

Relevant interaction model: explicit menu-item selection rather than choosing the nearest DOM element to arbitrary pointer movement. The project is MIT licensed.

## Design decisions for this plugin

- Menu layout is calculated from item count, ring and order.
- Pointer focus belongs to the actual anchor under the pointer (pointerenter/focusin).
- The central object stays in place; look-at changes only its rotation around the configured pivot.
- The central media is not circularly cropped unless the administrator explicitly enables the round mask.
- The plugin keeps real anchor navigation in the DOM.
- The fixed Three.js r128 layer remains a separate future enhancement.

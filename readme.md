# WalkingYog Animated Space Menu

Standalone WordPress plugin for a spatial animated menu centered around a GIF/MP4 object.

## 0.2.0 MVP

- Shortcode: [wyg_animated_menu]
- Central GIF or MP4
- Independent pivot point (X/Y %) for eye-level or other focal points
- WordPress admin repeater for flying menu items
- URL, title and description per item
- Internal and external URLs
- Optional new tab
- Icon and preview media per item
- X/Y/Z starting position
- Orbit radius, start angle, speed, amplitude, phase and scale
- Desktop pointer interaction
- Touch focus with a second tap used for activation
- Real HTML anchors remain the navigation baseline

## Planned next

- Visual pivot calibration overlay
- Drag-and-drop scene editor
- Live position editing
- Per-item preview playback
- Three.js r128 bundled locally under vendor/three-r128/
- Optional 3D object layer
- public/development/hidden item visibility
- JSON import/export
- Portal-style transitions

## Three.js rule

Do not auto-upgrade Three.js. The intended baseline is the exact r128 build used by the WalkingYog project. Bundle that exact build inside this repository before enabling the 3D layer.

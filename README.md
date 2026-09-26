# WalkingYog Animated Space Menu

Standalone WordPress plugin for an animated spatial menu around a central GIF/MP4 object.

## Current version

0.3.0

## Included in 0.2.0

- Shortcode [wyg_animated_menu]
- Central GIF/MP4 media
- WordPress Media Library picker
- Independent pivot X/Y for the central object
- Repeater UI in Settings → WalkingYog Animated Menu
- Title, URL and description per item
- Internal and external URLs
- Optional new-tab behavior
- Icon and preview media per item
- Enable/disable per item
- X/Y/Z starting position
- Orbit radius, angle, speed, amplitude, phase and scale
- Desktop pointer interaction
- Mobile first-touch focus / second-tap activation baseline
- Real HTML links as the fallback/navigation layer
- Circular central-object mask with contain rendering
- Pointer tilt rotates around the configured pivot without translating the head off-screen

## Install

Copy the plugin folder into wp-content/plugins/WalkingYog-Animated-Menu/ and activate it in WordPress. Configure it under Settings → WalkingYog Animated Menu, then insert [wyg_animated_menu].

When nothing is configured, the shortcode outputs nothing so it does not place setup text over the existing page.

## Research basis

The radial layout follows established radial/orbit UI patterns rather than manual free positioning. See docs/research.md.

## Planned

- Visual scene editor in WP admin
- Click-to-set pivot/focal point
- Live scene preview
- Per-item preview playback
- Bundled Three.js r128 only
- Optional 2D/3D scene modes
- Public/development/hidden visibility
- JSON import/export
- Portal-style transitions

# WalkingYog Animated Space Menu

Standalone WordPress plugin for a spatial animated menu around one central GIF/PNG/MP4 visual.

## Version 0.4.0

The public shortcode is unchanged:

[wyg_animated_menu]

The existing attributes class, media, media_type, pivot_x and pivot_y remain supported.

### What was rebuilt

- Visual Pivot editor: select the focal point directly on the actual GIF, PNG, JPG or MP4 preview.
- Pivot X/Y are stored as normalized metadata; the visible percentages are diagnostic, not the primary control.
- Media Library selection detects image/video type automatically.
- Mount/replacement mode for the existing .cover container removes the old background head so the scene contains one central head.
- Radial/orbit layout follows established center -> ring -> satellite geometry instead of manual per-item X/Y.
- Default layout is wide and side-balanced; the upper center above the head is intentionally kept free.
- Ring and order decide grouping; actual positions are calculated automatically.
- Real item boxes are measured after rendering and the radial engine corrects collisions and viewport overflow.
- Desktop focus belongs to the actual hovered/focused anchor, not to a guessed nearest item.
- Touch uses first-tap focus and second-tap activation.
- The central head stays in place and rotates only around the calibrated Pivot.
- Item preview media appears beside the focused item and does not replace the central head.
- Real anchor navigation remains in the DOM.
- No CDN dependency was introduced and the existing Three.js r128 project requirement is untouched.

## Research basis

The redesign was checked against established implementations rather than invented from scratch:

- WordPress FocalPointPicker for visual image/video focal-point selection.
- WordPress.org Media Focus Point for a production WordPress click-to-focus workflow.
- Zumerlab Orbit for center/orbit/satellite geometry and radial ranges.
- SyntaxSerenity RadialFlow for automatic radial/arc distribution and viewport correction.
- axln radial-menu-js for explicit item-based interaction instead of nearest-element selection.

See docs/research.md for the links and design mapping.

## WalkingYog setup

For the current WalkingYog header, leave the mount selector as .cover and keep replacement enabled. The shortcode can be placed there; the runtime removes the old .cover background image before using that same container as the animated scene.

## Future 3D layer

Three.js is not required by the radial engine. Any future 3D enhancement must remain local and use the project's fixed Three.js r128 build rather than a newer CDN release.

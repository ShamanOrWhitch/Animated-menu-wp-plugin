# Architecture

## 1. Public HTML first

Every menu node is a real <a href> element. The animated layer enhances the navigation but does not replace the underlying links.

## 2. Central visual pivot

The image/video can be visually off-center. pivot_x and pivot_y define the logical focal point (for the head, normally around the eyes). CSS transform-origin is set to this point.

## 3. Desktop

Pointer distance to menu nodes controls focus and a subtle central-object tilt. Hover is an enhancement; clicking the anchor remains the navigation action.

## 4. Touch

There is no assumption that touch has hover. First touch focuses a node and can trigger its preview. The next tap activates the link. The exact gesture can later be made configurable.

## 5. Three.js

The future 3D layer must use the project's fixed Three.js r128 build bundled inside this repository/plugin. It must not load an arbitrary current CDN version. The DOM anchor layer remains the accessible navigation baseline.

## 6. Development pages

A future visibility module should allow each node/page to be public, development, or hidden. Development pages should not be advertised through the public menu and should emit appropriate robots directives when configured. This is separate from merely using display:none.

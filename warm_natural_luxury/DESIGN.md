---
name: Warm Natural Luxury
colors:
  surface: '#fff8f3'
  surface-dim: '#e4d8ca'
  surface-bright: '#fff8f3'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#fef2e3'
  surface-container: '#f8ecde'
  surface-container-high: '#f3e6d8'
  surface-container-highest: '#ede1d3'
  on-surface: '#201b12'
  on-surface-variant: '#46483f'
  inverse-surface: '#362f26'
  inverse-on-surface: '#fbefe0'
  outline: '#76786f'
  outline-variant: '#c6c7bd'
  surface-tint: '#59614a'
  primary: '#3b432d'
  on-primary: '#ffffff'
  primary-container: '#525a43'
  on-primary-container: '#c8d1b4'
  inverse-primary: '#c1caad'
  secondary: '#5b614d'
  on-secondary: '#ffffff'
  secondary-container: '#dde2c9'
  on-secondary-container: '#5f6551'
  tertiary: '#3d4235'
  on-tertiary: '#ffffff'
  tertiary-container: '#54594b'
  on-tertiary-container: '#cacfbe'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dee6c8'
  primary-fixed-dim: '#c1caad'
  on-primary-fixed: '#171e0b'
  on-primary-fixed-variant: '#424a34'
  secondary-fixed: '#dfe5cc'
  secondary-fixed-dim: '#c3c9b1'
  on-secondary-fixed: '#181d0e'
  on-secondary-fixed-variant: '#434936'
  tertiary-fixed: '#e0e5d3'
  tertiary-fixed-dim: '#c4c9b7'
  on-tertiary-fixed: '#181d12'
  on-tertiary-fixed-variant: '#43493b'
  background: '#fff8f3'
  on-background: '#201b12'
  surface-variant: '#ede1d3'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 56px
    fontWeight: '700'
    lineHeight: 64px
    letterSpacing: -0.03em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 38px
    fontWeight: '700'
    lineHeight: 46px
    letterSpacing: -0.025em
  display-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 44px
    fontWeight: '600'
    lineHeight: 52px
    letterSpacing: -0.025em
  display-md-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '600'
    lineHeight: 34px
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
    letterSpacing: -0.005em
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
    letterSpacing: 0em
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 20px
    letterSpacing: 0.005em
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.04em
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.06em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.08em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  space-2xs: 0.25rem
  space-xs: 0.5rem
  space-sm: 0.75rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
  space-2xl: 3rem
  space-3xl: 4.5rem
  space-4xl: 6rem
  gutter-mobile: 1rem
  gutter-desktop: 2rem
  margin-mobile: 1.25rem
  margin-desktop: 4rem
  max-width: 1360px
---

## Brand & Style
This design system pairs organic warmth with disciplined, modern architectural restraint. Rooted in tactile, earthy luxury, it rejects harsh digital white in favor of rich sand and limestone undertones. The interface evokes the experience of an atelier catalog or a high-end architectural monograph: tranquil, curated, confident, and tactile.

The design movement combines **warm organic minimalism** with **editorial modernism**. Surfaces feature generous negative space, deliberate typographic scale, and structural borders rendered in muted botanical tones. Tactility is delivered through micro-tonal depth rather than exaggerated artificial shadows, cultivating an ambiance of enduring heritage, understated wealth, and quiet focus.

## Colors
The palette is built upon a canvas of warm sand `#E7DBCD`, grounding the digital experience in natural paper and travertine textures. Rather than pure black, typography and high-contrast lines utilize deep blackened-olive `#1A1D16` and dark forest `#2C3125`, maintaining readable contrast without breaking organic warmth.

- **Primary (`#525A43` Deep Olive Forest):** Used for focal CTAs, prominent active states, anchor typography, and hero interactive elements.
- **Secondary (`#9CA28B` Muted Sage):** Employed for structural rules, subtle badge fills, hover states, disabled indicators, and secondary supportive iconography.
- **Tertiary (`#2C3125` Blackened Olive):** Reserved for ultra-high-contrast elements, headline copy, deep footers, and sharp contrast borders.
- **Neutral Surface (`#E7DBCD` Warm Cream / Sand):** The fundamental base canvas. Container surfaces step upward to `#EFE6DA` and drop to `#DECFC0` for recessed sections.

## Typography
Plus Jakarta Sans is utilized holistically across headlines, body copy, and micro-labels to reinforce a sculptural, geometric balance. To maintain the light luxury sensibility:
- Large display headings leverage tight letter-spacing and substantial font weights to create an architectural, deliberate presence against open canvas space.
- Small labels and category markers use uppercase formatting with widened tracking (`0.06em` to `0.08em`), evoking catalog metadata and gallery placards.
- Body text retains an open `1.6` line-height ratio to prevent fatigue against the warm tinted neutral background.

## Layout & Spacing
The layout follows a 12-column responsive fluid grid with strict max-width constraints to protect generous horizontal margins on wide viewports. 

- **Desktop (1024px+):** 12 columns, 32px (`2rem`) gutters, and 64px (`4rem`) outer margins. Content zones lean on asymmetrical block structures (e.g., 5-column descriptor beside a 7-column media frame).
- **Tablet (768px - 1023px):** 8 columns, 24px (`1.5rem`) gutters, 32px (`2rem`) outer margins.
- **Mobile (0px - 767px):** 4 columns, 16px (`1rem`) gutters, 20px (`1.25rem`) outer margins. Spacing drops proportionally, with section dividers stepping from `6rem` to `3rem`.

Whitespace is treated as an active structural element rather than void. High vertical padding between thematic groupings reinforces calm pacing and intentional browsing.

## Elevation & Depth
In place of heavy commercial drop shadows, visual depth is achieved through **tonal layering** paired with **warm ambient light**:

1. **Surface 0 (Base Canvas):** `#E7DBCD` Warm Cream.
2. **Surface 1 (Elevated Cards/Panels):** `#EFE6DA` Light Limestone. Separated by fine 1px outlines in `#525A43` at 12% opacity (`rgba(82, 90, 67, 0.12)`).
3. **Surface 2 (Popovers, Overlays, Floating Menus):** `#F6EFE5` Warm Alabaster, combined with an ambient diffuse shadow tinted with deep olive: `0 12px 32px -4px rgba(44, 49, 37, 0.08), 0 4px 12px -2px rgba(44, 49, 37, 0.04)`.
4. **Recessed Wells (Inputs, Inactive wells):** `#DECFC0` Sandstone Tint, sitting flush or sunken via crisp 1px inset tonal boundaries.

## Shapes
A restrained roundedness level of `1` (Soft) is enforced throughout the interface:
- Standard elements (buttons, text inputs, badges) feature a tight `4px` (`0.25rem`) radius.
- Medium containers (cards, modal dialogues) apply `8px` (`0.5rem`) corner rounding (`rounded-lg`).
- High-level aesthetic frames or hero media cards cap at `12px` (`0.75rem`) (`rounded-xl`).

This subdued curvature avoids the informality of pill shapes and the aggressive sharpness of brutalism, complementing architectural grid lines with subtle tactile softness.

## Components

### Buttons
- **Primary:** Filled `#525A43` (Deep Olive Forest) background with `#F6EFE5` high-contrast text. Subtle hover state darkens to `#3D4332`. Active state compresses slightly (transform scale 0.99). Border radius `4px`.
- **Secondary:** Outlined with a 1.5px stroke of `#525A43`, transparent background, and `#2C3125` text. On hover, fills with `#525A43` at 8% opacity.
- **Tertiary / Text:** No border or background fill. `#525A43` bold text with an animated 1px underline that expands from left to right on hover.

### Chips & Badges
- **Status Badges:** Filled with `#9CA28B` at 20% opacity, bordered by `#9CA28B` at 40%, text rendered in `#2C3125`. Radius `4px`.
- **Filter Chips:** Unselected chips feature `#EFE6DA` background and 1px `#D5C6B4` border. Selected chips invert to `#525A43` background with `#E7DBCD` text.

### Input Fields
- Inputs rest on an `#EAE0D3` tinted base with a 1px border of `#C9BEAF`.
- On focus, the border shifts crisply to `#525A43` with a 2px offset ring of `rgba(82, 90, 67, 0.15)`. No loud glowing outlines.
- Labels are positioned top-aligned in uppercase `label-sm` with `#525A43` color.

### Checkboxes & Radio Buttons
- 18px structural footprint with 1.5px borders in `#525A43`.
- When checked, fills with `#525A43` displaying a sand `#E7DBCD` checkmark or center radio dot.
- Focus state provides a subtle `3px` ambient perimeter ring in `rgba(156, 162, 139, 0.3)`.

### Cards & Panels
- Resting card surfaces use `#EFE6DA` framed by a hairline 1px border in `rgba(82, 90, 67, 0.12)`.
- Interactive cards exhibit a subtle lift: vertical translation of `-2px` alongside an ambient olive shadow `0 8px 24px -4px rgba(44, 49, 37, 0.07)`.

### Dividers & Rules
- Section dividers employ solid 1px lines in `#9CA28B` at 30% opacity, grounding editorial text blocks without visual fragmentation.
---
name: quiqqer_frontend_css_variables
description: Use when writing or changing CSS for QUIQQER controls or modules, when binding control or project settings into styling, or when working with theming, colors, or CSS custom properties. Covers the three-layer pattern for configurable CSS variables and the rules against hardcoded colors.
category: developer
---

# QUIQQER Frontend CSS Variables

Use this skill when styling QUIQQER controls and similar modules where part of the presentation comes from
settings (project config, control attributes) and must stay overridable through theming at the same time.

Goal: PHP only sets the config values, the CSS defines fallbacks and theming hooks in exactly one place.
No inline `style` with long variable names, no scattered fallbacks in the CSS body.

Load `quiqqer_frontend_accessibility` as well when the change also touches markup.

## The Three-Layer Pattern

Every configurable CSS property passes through three layers:

1. Theming layer: a long, collision-safe variable, overridable from outside (theme or project CSS):
   `--<vendor>-<control>-<name>` (fully written out).
2. Config layer: the value set by PHP from the settings: `--_q-controlConf-<name>`.
3. Fallback: the default value directly in the CSS.

Nested from outside to inside, bound to a short internal variable `--_<name>`:

```css
--_<name>: var(--<vendor>-<control>-<name>, var(--_q-controlConf-<name>, <fallback>));
```

Concrete example from the Breadcrumb control in quiqqer/core (`Breadcrumb.Slider.css`):

```css
--_font-size: var(--quiqqer-core-controls-breadcrumb-font-size, var(--_q-controlConf-font-size, 0.9em));
```

### Naming Convention `--_`

The underscore prefix `--_NAME` marks: do not override this variable from outside. It is only used
internally in the CSS body. Overriding happens exclusively through the long theming variable (layer 1).

## Implementation

### 1. PHP: Set The Config Values

A small private helper method writes the config layer onto the control element. Empty values are skipped,
so the CSS fallback applies:

```php
/**
 * Write a control config CSS variable to the root element style. The CSS
 * picks it up via the long, themeable variable as a fallback layer.
 */
private function setCustomVariable(string $name, string $value): void
{
    if ($name === '' || $value === '') {
        return;
    }

    $this->setStyle('--_q-controlConf-' . $name, $value);
}
```

Call it per setting, only when a value exists:

```php
$sectionGap = (string)$Project->getConfig($prefix . 'sectionGap');

if (isset(self::SECTION_GAP_PRESETS[$sectionGap])) {
    $this->setCustomVariable('sectionGap', self::SECTION_GAP_PRESETS[$sectionGap]);
}
```

Note: `setStyle()` writes the `style="…"` onto the outer control wrapper element. If the actual CSS root
element lives inside the template (for example an `<article>` inside the wrapper), that is fine: CSS custom
properties inherit, so the variable is available in the inner element through `var()`.

### 2. CSS: The Root Element Defines All Variables

Define the short `--_NAME` variables once on the unique root element. The complete fallback and theming
logic lives here:

```css
.<vendor>-<control> {
    /* config layer: long themeable variable > control config > fallback */
    --_sectionGap: var(--<vendor>-<control>-sectionGap, var(--_q-controlConf-sectionGap, 4rem));
    --_title-size: var(--<vendor>-<control>-title-size, var(--_q-controlConf-title-size, 2rem));

    /* theming layer: pure theming variables (without PHP config) */
    --_fields-gap: var(--<vendor>-<control>-fields-gap, 2rem);

    row-gap: var(--_sectionGap);
}
```

### 3. CSS Body: Use Only The Short Variables

The rest of the CSS uses exclusively `var(--_NAME)`, without inline fallbacks (they already live in the
root definition):

```css
.<vendor>-<control>-title {
    font-size: var(--_title-size);
}
```

## Pure Theming Variables (Without Config)

Recurring values that do not come from settings but should stay adjustable through theming in one place
also get a `--_NAME` variable on the root, just without the config layer:

```css
--_fields-gap: var(--<vendor>-<control>-fields-gap, 2rem);
```

This allows overriding, for example, a uniform spacing that repeats across several selectors centrally.

For breakpoints, override the short variable locally instead of setting the value directly:

```css
@media (max-width: 768px) {
    .<vendor>-<control>-split {
        --_split-gap: 2rem;
        grid-template-columns: 1fr;
    }
}
```

## Existing Components First

Before writing new CSS, check what already exists: the active template usually ships component classes and
utility classes (buttons, cards, badges, …) with their own CSS variable hooks. Use those in the markup and
adjust variants through their hooks instead of rewriting properties like padding, border, or transition.

Writing custom button or chip CSS (reset, padding, radius, hover) parallel to an existing component
duplicates the design system and drifts apart on theme changes.

## Colors: No Module-Level Hardcodes

Modules and controls do not introduce their own color values (no `color: #23303d` or similar in module
CSS). Instead, in this order:

1. Use the cascade: when the context (body, card container, …) already provides the correct color, do not
   set `color` or `background` at all. Often removing a hardcoded color is the correct fix.
2. Use the design system variables of the active template when a color must be set explicitly. Look up the
   canonical token schema of the active template instead of blindly copying variable names from existing
   code, which often carries a legacy schema.
3. Use a theming variable following the three-layer pattern when the color must be configurable per project
   or control, with a design system variable (not a hex value) as the fallback.

Hex or RGB values directly in module CSS are only acceptable for truly module-specific, non-themeable
effects (for example skeleton shimmer or overlays with `rgba(0,0,0,…)`).

## Checklist

- [ ] PHP: a private `setCustomVariable()` writes `--_q-controlConf-<name>` and skips empty values.
- [ ] CSS root: every configurable property as `--_NAME: var(long, var(--_q-controlConf-NAME, fallback))`.
- [ ] CSS body: only `var(--_NAME)`, no scattered fallbacks.
- [ ] Recurring spacings as pure theming `--_NAME` variables on the root.
- [ ] No hardcoded colors in module CSS: cascade > design system variable > theming variable.
- [ ] Checked whether the template already ships a component class before writing new CSS.
- [ ] Default values (fallbacks) match the previous behavior exactly (no unintended visual change).

## Reference Implementation

- Breadcrumb (quiqqer/core): `src/QUI/Controls/Breadcrumb.php` plus `Breadcrumb.Slider.css`. Origin of the
  `--_q-controlConf-` convention.

-- Migration: per-tenant fully custom (uploaded) menu template.
-- Holds the bespoke HTML template (Mustache-subset) rendered when
-- themes.template = 'custom'. Run once on existing installs.

ALTER TABLE themes
  ADD COLUMN custom_html LONGTEXT DEFAULT NULL AFTER custom_css;

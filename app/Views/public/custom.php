<?php
/**
 * Wrapper for a tenant's fully custom (uploaded) template.
 * $rendered is already produced by the safe TemplateEngine, so it is
 * emitted as-is inside the public layout (which provides the <head>,
 * fonts, theme CSS variables and custom CSS).
 *
 * @var string $rendered
 */
echo $rendered;

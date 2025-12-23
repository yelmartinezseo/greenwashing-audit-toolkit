# Greenwashing Audit Toolkit

![Version](https://img.shields.io/badge/version-3.1-blue)
![License](https://img.shields.io/badge/license-GPL--3.0-green)
![WordPress](https://img.shields.io/badge/WordPress-Plugin-blue)

Herramienta completa de auditoría de greenwashing para WordPress que analiza sitios web en busca de prácticas engañosas de marketing ambiental.

## ✨ Características

- **Análisis automático** de URLs en tiempo real
- **Puntuación 0-100** con código de colores
- **Detección de 6 categorías** de greenwashing
- **Listado de incumplimientos** específicos
- **Recomendaciones** personalizadas
- **Integración con GitHub** Issues

## 📦 Instalación

1. Descarga el plugin desde [Releases](../../releases)
2. Sube a `/wp-content/plugins/` de tu WordPress
3. Activa el plugin desde el panel de administración
4. Usa el shortcode `[greenwashing_audit]` en cualquier página

## 🎯 Uso

```php
// Shortcode básico
[greenwashing_audit]

// Shortcode con parámetros (futuras versiones)
[greenwashing_audit depth="medium" lang="es"]

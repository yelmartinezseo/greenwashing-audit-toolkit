# 🍃 Greenwashing Audit Toolkit

[![Version](https://img.shields.io/badge/version-4.0-blue)](https://github.com/yelmartinezseo/greenwashing-audit-toolkit)
[![License](https://img.shields.io/badge/license-GPL--3.0-green)](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/blob/main/LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-Plugin-blue)](https://github.com/yelmartinezseo/greenwashing-audit-toolkit)
[![Normativa UE](https://img.shields.io/badge/Normativa-UE%202024%2F825-darkgreen)](https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:32024L0825)

Herramienta completa de auditoría de greenwashing para WordPress. Analiza sitios web en busca de prácticas engañosas de marketing ambiental, aplica la normativa europea y española vigente, y devuelve una puntuación 0–100 con incumplimientos detallados y referencias legales por categoría.

---

## ✨ Características — v4.0

| Categoría | Detalle |
|---|---|
| 🔍 Análisis automático | Analiza HTML público de cualquier URL en tiempo real |
| 📊 Puntuación 0–100 | Con código de colores y bonificaciones por buenas prácticas |
| ⚖️ Normativa por incumplimiento | Cada issue cita la directiva o ley concreta aplicable |
| 🏭 Análisis por sector | Reglas específicas para financiero, alimentación, textil, energía… |
| 🏢 Análisis por tamaño | Distintas obligaciones para PYME, gran empresa y cotizada |
| 🌐 Multipágina inteligente | Rastrea rutas de sostenibilidad típicas en modo medio/profundo |
| 🗄️ Histórico de auditorías | Guarda resultados en base de datos WordPress |
| 📋 Checklist descargable | PDF con criterios normativos para auditoría manual |
| 🤝 Integración GitHub | Plantillas de issues para solicitar auditoría completa |
| 🔒 Seguridad WordPress | Nonce, sanitización, esc_url en todos los inputs |

---

## 📜 Normativa cubierta

El plugin aplica y referencia explícitamente las siguientes normas en sus resultados:

- **Directiva 2024/825/UE** – Empowering Consumers for the Green Transition
- **Green Claims Directive** – Propuesta COM/2023/166 (en tramitación)
- **Reglamento de Taxonomía UE (2020/852)** – Actividades medioambientalmente sostenibles
- **CSRD – Directiva 2022/2464/UE** – Corporate Sustainability Reporting
- **NFRD – Directiva 2014/95/UE** – Non-Financial Reporting
- **Directiva 2005/29/CE** – Prácticas Comerciales Desleales
- **Reglamento SFDR (2019/2088/UE)** – Divulgación en servicios financieros
- **Reglamento EMAS (CE) n.º 1221/2009**
- **Ley 11/2018 (España)** – Información no financiera y diversidad
- **Ley 7/2022 (España)** – Residuos y Suelo Contaminado (biodegradabilidad)
- **ISO 14021** – Autodeclaraciones medioambientales
- **ISO 14064** – Cuantificación y reporte de GEI
- **GHG Protocol** – Alcances 1, 2 y 3 de emisiones
- **FTC Green Guides** – Referencia internacional

---

## 🚀 Instalación

### Opción 1: Desde WordPress (recomendado)

1. Descarga el ZIP desde [Releases](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/releases/latest)
2. Ve a **WordPress → Plugins → Añadir nuevo → Subir plugin**
3. Sube el archivo ZIP y activa el plugin
4. La tabla de base de datos se crea automáticamente al activar

### Opción 2: Manual

```bash
git clone https://github.com/yelmartinezseo/greenwashing-audit-toolkit.git
cp -r greenwashing-audit-toolkit/wordpress-plugin/ /ruta/a/wp-content/plugins/greenwashing-audit/
```

Activa "Greenwashing Audit Toolkit" desde **Plugins** en el panel de WordPress.

---

## 🎯 Uso

### 1. Insertar el shortcode en cualquier página o entrada

```
[greenwashing_audit]
```

### 2. Campos del formulario

| Campo | Opciones | Función |
|---|---|---|
| URL | Cualquier URL pública | Página o sitio a analizar |
| Sector | General, E-commerce, Financiero, Manufactura, Alimentación, Textil, Construcción, Energía, Turismo, Servicios | Activa reglas normativas específicas por sector |
| Tamaño empresa | Autónomo, PYME, Mediana, Gran empresa, Cotizada | Escala obligaciones de reporting |
| Profundidad | Básico / Medio / Profundo | Controla cuántas páginas se analizan |

### 3. Profundidad de análisis

- **Básico**: solo la URL indicada
- **Medio**: + 18 rutas típicas de sostenibilidad (`/sostenibilidad`, `/csr`, `/politica-ambiental`, etc.)
- **Profundo**: + enlaces internos con keywords relevantes (hasta 30 páginas)

---

## 🔬 Categorías de detección

| Categoría | Severidad base | Normativa principal |
|---|---|---|
| Término vago no sustanciado | Media | Directiva 2024/825/UE, Art. 3 |
| Certificación sin enlace verificable | Alta | Green Claims Directive |
| Sello no reconocido por la UE | Alta | Directiva 2024/825/UE, Art. 3.3 |
| Declaración de carbono sin métricas | Alta | ISO 14064, GHG Protocol |
| Lenguaje absoluto (100%, cero impacto…) | Alta | Directiva 2024/825/UE, Anexo I |
| Biodegradabilidad sin condiciones | Media | Ley 7/2022, EN 13432 |
| Imágenes engañosas + términos vagos | Baja | Directiva 2005/29/CE |
| Ausencia de política de sostenibilidad | Media/Alta | CSRD, Ley 11/2018 |
| Producto/empresa sostenible sin alcance | Media | Green Claims Directive, ISO 14021 |
| Producto financiero sin indicadores SFDR | Alta | SFDR (2019/2088/UE) |

### Bonificaciones por buenas prácticas

- +5 pts: métricas específicas con % de reducción
- +3 pts: referencia a norma ISO
- +3 pts: año objetivo concreto (meta 2030, etc.)
- +4 pts: verificación por tercero independiente

---

## 📋 Checklist descargable

Disponible en PDF para auditoría manual. Cubre los criterios normativos más relevantes con sistema de puntuación 0–100 y referencias legales por pregunta.

📥 [Descargar Checklist Inicial (PDF)](https://yel-martinez-portfolio.com/wp-content/uploads/checklist-greenwashing-inicial-1.pdf)

---

## 🤝 Cómo contribuir

### Para usuarios

1. **Reportar problemas** → [Crear un Issue](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new)
2. **Solicitar una auditoría** → [Plantilla Auditoría Completa](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=auditoria-completa.yml)
3. **Mejorar el checklist** → [Proponer nueva pregunta](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=nueva-pregunta-checklist.yml)

### Para desarrolladores

```bash
# 1. Fork del repositorio
# 2. Crea una rama
git checkout -b feature/nueva-funcionalidad

# 3. Haz commit
git commit -m 'Añade nueva funcionalidad'

# 4. Push
git push origin feature/nueva-funcionalidad

# 5. Abre un Pull Request
```

### Plantillas de Issues disponibles

| Plantilla | Uso |
|---|---|
| [Auditoría Completa](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=auditoria-completa.yml) | Solicitar análisis de una web concreta |
| [Nueva Pregunta Checklist](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=nueva-pregunta-checklist.yml) | Proponer un nuevo criterio normativo |

---

## 🗂️ Estructura del repositorio

```
greenwashing-audit-toolkit/
├── wordpress-plugin/
│   └── greenwashing-audit.php     # Plugin principal (v4.0)
├── ISSUE_TEMPLATE/
│   ├── auditoria-completa.yml
│   └── nueva-pregunta-checklist.yml
├── documentation/
│   └── checklist-greenwashing.pdf # Checklist descargable
├── .gitignore
├── LICENSE
└── README.md
```

---

## ⚠️ Aviso legal

Este plugin y su documentación son herramientas orientativas. Los resultados **no constituyen asesoramiento jurídico ni auditoría oficial** conforme a ninguna normativa. Deben ser interpretados y validados por un profesional cualificado antes de tomar decisiones legales o comerciales.

---

## 📄 Licencia

Este proyecto está bajo la **Licencia GPL-3.0**. Ver el archivo [LICENSE](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/blob/main/LICENSE) para más detalles.

---

## 👤 Autor

**Yel Martínez** · Consultora de Sostenibilidad y Marketing Digital

- GitHub: [@yelmartinezseo](https://github.com/yelmartinezseo)
- Portfolio: [yel-martinez-portfolio.com](https://yel-martinez-portfolio.com)
- Repositorio: [greenwashing-audit-toolkit](https://github.com/yelmartinezseo/greenwashing-audit-toolkit)

---

## 🙏 Agradecimientos

- Basado en los principios de la [Directiva de Prácticas Comerciales Desleales de la UE](https://ec.europa.eu/)
- Referenciado con las [FTC Green Guides](https://www.ftc.gov/news-events/topics/truth-advertising/green-guides)
- Inspirado en la necesidad real de herramientas accesibles para PYMES y consultoras de sostenibilidad

---

> ¿Encontraste un caso de greenwashing? **[Audítalo y comparte los resultados](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=auditoria-completa.yml)**
>
> ¿Tienes sugerencias? **[Abre un Issue o contribuye al proyecto](https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new)**

<?php
/**
 * Plugin Name: Greenwashing Audit Toolkit – Auditor Activo
 * Description: Analiza URLs en busca de greenwashing y devuelve puntuación, incumplimientos y referencias normativas (UE y España)
 * Version:     4.0
 * Author:      Yel Martinez
 * Author URI:  https://github.com/yelmartinezseo
 * Plugin URI:  https://github.com/yelmartinezseo/greenwashing-audit-toolkit
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: greenwashing-audit
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// ============================================================
// TABLAS DE BASE DE DATOS
// ============================================================

register_activation_hook(__FILE__, 'ga_create_tables');

function ga_create_tables() {
    global $wpdb;
    $table = $wpdb->prefix . 'greenwashing_audits';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        url VARCHAR(500) NOT NULL,
        score INT(3) NOT NULL,
        total_issues INT(5) NOT NULL,
        sector VARCHAR(100) DEFAULT '',
        company_size VARCHAR(50) DEFAULT '',
        depth VARCHAR(20) DEFAULT 'basic',
        issues_json LONGTEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// ============================================================
// NORMATIVA DE REFERENCIA
// ============================================================

function ga_get_normativa() {
    return array(
        'termino_vago' => array(
            'label' => 'Declaración vaga no sustanciada',
            'normas' => array(
                'Directiva 2024/825/UE (Empowering Consumers) – Art. 3: prohíbe declaraciones medioambientales genéricas sin sustanciación.',
                'Directiva 2005/29/CE sobre Prácticas Comerciales Desleales – transpuesta en España por RDL 1/2007.',
                'Directiva 2024/825/UE – Anexo I, punto 4: prohíbe afirmaciones como "ecológico", "verde" o "amigo del medio ambiente" sin prueba.',
                'Guía de la Comisión Europea sobre declaraciones verdes (COM/2021/891).',
            ),
        ),
        'certificacion_sin_detalles' => array(
            'label' => 'Certificación sin respaldo verificable',
            'normas' => array(
                'Green Claims Directive (propuesta COM/2023/166) – exige que las certificaciones sean de terceros acreditados y verificables.',
                'Directiva 2024/825/UE – Art. 3.3: los sellos de sostenibilidad deben estar respaldados por sistemas de certificación aprobados.',
                'Reglamento (CE) n.º 66/2010 sobre la Etiqueta Ecológica de la UE (EU Ecolabel).',
                'Reglamento EMAS (CE) n.º 1221/2009 – verificación por organismo acreditado.',
            ),
        ),
        'declaracion_carbono_sin_metricas' => array(
            'label' => 'Declaración de carbono sin métricas verificables',
            'normas' => array(
                'Green Claims Directive (COM/2023/166) – las declaraciones de "carbono neutro" o "net zero" deben basarse en metodologías reconocidas.',
                'ISO 14064 (Cuantificación y reporte de GEI) – referencia técnica obligada para métricas de carbono.',
                'Reglamento de Taxonomía UE (2020/852) – define qué actividades son medioambientalmente sostenibles con criterios técnicos.',
                'Directiva 2024/825/UE – Considerando 14: las compensaciones de carbono sin detalles son prácticas engañosas.',
            ),
        ),
        'imagen_enganosa' => array(
            'label' => 'Posible uso engañoso de imágenes de naturaleza',
            'normas' => array(
                'Directiva 2005/29/CE – Art. 6: prácticas comerciales engañosas incluyen la presentación visual que induzca a error.',
                'Directiva 2024/825/UE – Considerando 12: la comunicación visual forma parte de la declaración medioambiental.',
            ),
        ),
        'falta_politica_sostenibilidad' => array(
            'label' => 'Ausencia de política de sostenibilidad documentada',
            'normas' => array(
                'CSRD – Directiva 2022/2464/UE: obliga a grandes empresas a publicar información de sostenibilidad verificada (aplica por umbrales).',
                'NFRD – Directiva 2014/95/UE (vigente para empresas ya obligadas hasta transición CSRD).',
                'Ley 11/2018 (España) sobre información no financiera y diversidad – obliga a publicar estado de información no financiera.',
            ),
        ),
        'lenguaje_absoluto' => array(
            'label' => 'Lenguaje absoluto o hiperbólico sobre sostenibilidad',
            'normas' => array(
                'Directiva 2024/825/UE – Anexo I, punto 4: expresamente prohíbe "100% ecológico", "completamente sostenible", "totalmente verde".',
                'Directiva 2005/29/CE – Art. 5: práctica desleal si crea una impresión falsa sobre características medioambientales.',
                'FTC Green Guides (referencia internacional) – desaconseja afirmaciones absolutas de impacto ambiental.',
            ),
        ),
        'sello_no_reconocido' => array(
            'label' => 'Posible uso de sello o etiqueta no reconocido oficialmente',
            'normas' => array(
                'Directiva 2024/825/UE – Art. 3.3b: los sellos voluntarios deben estar aprobados por autoridades públicas o sistemas de certificación reconocidos.',
                'Green Claims Directive (COM/2023/166) – prohíbe sellos privados sin base en un sistema de certificación verificado.',
            ),
        ),
        'obligacion_financiera' => array(
            'label' => 'Posible incumplimiento de obligaciones de divulgación en servicios financieros',
            'normas' => array(
                'Reglamento SFDR (2019/2088/UE) – obliga a entidades financieras a divulgar riesgos de sostenibilidad de sus productos.',
                'MiFID II (Directiva 2014/65/UE, modificada) – incorpora factores ESG en la idoneidad del asesoramiento.',
                'Reglamento de Taxonomía UE (2020/852) – fondos que se declaren sostenibles deben demostrar alineación.',
            ),
        ),
        'declaracion_biodegradable' => array(
            'label' => 'Declaración de biodegradabilidad o compostabilidad sin especificación',
            'normas' => array(
                'Ley 7/2022 de Residuos y Suelo Contaminado (España) – Art. 14: regula y restringe el uso de "biodegradable" y "compostable".',
                'Directiva 2024/825/UE – Anexo I, punto 5: prohíbe afirmar que un producto es "biodegradable" sin especificar condiciones.',
                'Norma EN 13432 – referencia técnica para compostabilidad industrial.',
            ),
        ),
        'sin_alcance_definido' => array(
            'label' => 'Declaración ambiental sin alcance definido',
            'normas' => array(
                'Green Claims Directive (COM/2023/166) – las declaraciones deben especificar a qué parte del ciclo de vida se refieren.',
                'ISO 14021 (Autodeclaraciones medioambientales) – exige que el alcance sea explícito.',
                'Reglamento de Taxonomía UE (2020/852) – criterios de no causar daño significativo (DNSH) aplicables por actividad.',
            ),
        ),
    );
}

// ============================================================
// SELLOS SOSPECHOSOS (no reconocidos por la UE)
// ============================================================

function ga_get_sellos_sospechosos() {
    return array(
        'eco certified', 'eco approved', 'green certified', 'planet friendly certified',
        'climate hero', 'carbon champion', 'green leader', 'eco warrior',
        'sustainable brand certified', 'green seal of approval', 'environmental champion',
        'eco trust', 'green trust mark', 'sustainable certified',
    );
}

// ============================================================
// SHORTCODE PRINCIPAL
// ============================================================

add_shortcode('greenwashing_audit', 'ga_audit_tool');

function ga_audit_tool($atts) {
    $result_html = '';
    $url = '';
    $sector = '';
    $company_size = '';
    $depth = 'basic';

    if (isset($_POST['start_audit']) && isset($_POST['audit_url'])) {
        if (!isset($_POST['ga_nonce']) || !wp_verify_nonce($_POST['ga_nonce'], 'ga_audit_action')) {
            $result_html = '<div class="ga-error">❌ Error de seguridad. Recarga la página e inténtalo de nuevo.</div>';
        } else {
            $url          = esc_url_raw($_POST['audit_url']);
            $depth        = isset($_POST['audit_depth'])   ? sanitize_text_field($_POST['audit_depth'])   : 'basic';
            $sector       = isset($_POST['audit_sector'])  ? sanitize_text_field($_POST['audit_sector'])  : 'general';
            $company_size = isset($_POST['company_size'])  ? sanitize_text_field($_POST['company_size'])  : 'pyme';
            $result_html  = ga_perform_audit($url, $depth, $sector, $company_size);
        }
    }

    ob_start();
    ?>
    <div class="ga-tool">

        <div class="ga-form-section">
            <h3>🔍 Auditoría de Greenwashing</h3>
            <p class="ga-subtitle">Analiza cualquier página web en busca de prácticas de greenwashing según la normativa europea y española vigente.</p>

            <form method="post" class="ga-form" action="">
                <?php wp_nonce_field('ga_audit_action', 'ga_nonce'); ?>

                <div class="ga-form-grid">

                    <div class="ga-form-group ga-full-width">
                        <label for="audit_url">🌐 URL a auditar <span class="ga-required">*</span></label>
                        <input type="url"
                               id="audit_url"
                               name="audit_url"
                               value="<?php echo esc_url($url); ?>"
                               placeholder="https://ejemplo.com"
                               required
                               class="ga-input">
                    </div>

                    <div class="ga-form-group">
                        <label for="audit_sector">🏭 Sector de actividad</label>
                        <select id="audit_sector" name="audit_sector" class="ga-input">
                            <option value="general"      <?php selected($sector, 'general');     ?>>General / No especificado</option>
                            <option value="ecommerce"    <?php selected($sector, 'ecommerce');   ?>>E-commerce / Retail</option>
                            <option value="financiero"   <?php selected($sector, 'financiero');  ?>>Servicios financieros / Inversión</option>
                            <option value="manufactura"  <?php selected($sector, 'manufactura'); ?>>Manufactura / Industria</option>
                            <option value="alimentacion" <?php selected($sector, 'alimentacion');?>>Alimentación / Bebidas</option>
                            <option value="textil"       <?php selected($sector, 'textil');      ?>>Moda / Textil</option>
                            <option value="construccion" <?php selected($sector, 'construccion');?>>Construcción / Inmobiliaria</option>
                            <option value="energia"      <?php selected($sector, 'energia');     ?>>Energía / Utilities</option>
                            <option value="turismo"      <?php selected($sector, 'turismo');     ?>>Turismo / Hostelería</option>
                            <option value="servicios"    <?php selected($sector, 'servicios');   ?>>Servicios profesionales</option>
                        </select>
                    </div>

                    <div class="ga-form-group">
                        <label for="company_size">🏢 Tamaño de empresa</label>
                        <select id="company_size" name="company_size" class="ga-input">
                            <option value="autonomo"  <?php selected($company_size, 'autonomo'); ?>>Autónomo / Freelance</option>
                            <option value="pyme"      <?php selected($company_size, 'pyme');     ?>>PYME (&lt;250 empleados)</option>
                            <option value="mediana"   <?php selected($company_size, 'mediana');  ?>>Mediana empresa (250-499 emp.)</option>
                            <option value="grande"    <?php selected($company_size, 'grande');   ?>>Gran empresa (500+ empleados)</option>
                            <option value="cotizada"  <?php selected($company_size, 'cotizada'); ?>>Empresa cotizada / Entidad financiera</option>
                        </select>
                    </div>

                    <div class="ga-form-group ga-full-width">
                        <label for="audit_depth">🔬 Profundidad del análisis</label>
                        <select id="audit_depth" name="audit_depth" class="ga-input">
                            <option value="basic"  <?php selected($depth, 'basic'); ?>>Básico – Solo página indicada</option>
                            <option value="medium" <?php selected($depth, 'medium');?>>Medio – + páginas de sostenibilidad y sobre nosotros</option>
                            <option value="deep"   <?php selected($depth, 'deep');  ?>>Profundo – + enlaces internos relevantes</option>
                        </select>
                    </div>

                </div><!-- .ga-form-grid -->

                <button type="submit" name="start_audit" class="ga-btn-primary">
                    🔍 Iniciar Auditoría
                </button>

                <p class="ga-disclaimer-form">
                    <strong>Aviso:</strong> Este análisis es orientativo. No constituye asesoramiento legal ni auditoría oficial conforme a ninguna normativa. Los resultados deben ser interpretados por un profesional cualificado.
                </p>
            </form>
        </div><!-- .ga-form-section -->

        <?php if (!empty($result_html)): ?>
        <div class="ga-results-section">
            <?php echo $result_html; ?>
        </div>
        <?php endif; ?>

        <div class="ga-resources-section">
            <h4>📚 Normativa de referencia</h4>
            <ul>
                <li><a href="https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:32024L0825" target="_blank" rel="noopener">Directiva 2024/825/UE – Empowering Consumers for the Green Transition</a></li>
                <li><a href="https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:52023PC0166" target="_blank" rel="noopener">Propuesta Green Claims Directive (COM/2023/166)</a></li>
                <li><a href="https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:32020R0852" target="_blank" rel="noopener">Reglamento de Taxonomía de la UE (2020/852)</a></li>
                <li><a href="https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:32022L2464" target="_blank" rel="noopener">CSRD – Directiva de Reporting de Sostenibilidad (2022/2464)</a></li>
                <li><a href="https://www.boe.es/buscar/act.php?id=BOE-A-2018-17989" target="_blank" rel="noopener">Ley 11/2018 (España) – Información no financiera y diversidad</a></li>
                <li><a href="https://www.boe.es/buscar/act.php?id=BOE-A-2022-5809" target="_blank" rel="noopener">Ley 7/2022 de Residuos y Suelo Contaminado (España)</a></li>
            </ul>
        </div>

        <?php ga_render_historico(); ?>

    </div><!-- .ga-tool -->
    <?php

    ga_enqueue_assets();
    return ob_get_clean();
}

// ============================================================
// AUDITORÍA PRINCIPAL
// ============================================================

function ga_perform_audit($url, $depth = 'basic', $sector = 'general', $company_size = 'pyme') {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '<div class="ga-error">❌ URL inválida. Introduce una URL completa (ej: https://ejemplo.com)</div>';
    }

    // Obtener páginas a analizar según profundidad
    $urls_to_analyze = ga_get_urls_to_analyze($url, $depth);

    $combined_content     = '';
    $combined_html        = '';
    $pages_analyzed       = array();
    $pages_failed         = array();

    foreach ($urls_to_analyze as $target_url) {
        $content = ga_fetch_url_content($target_url);
        if ($content && !is_string($content) === false && strlen($content) > 100) {
            $combined_html    .= $content;
            $combined_content .= wp_strip_all_tags($content) . ' ';
            $pages_analyzed[]  = $target_url;
        } else {
            $pages_failed[] = $target_url;
        }
    }

    if (empty($pages_analyzed)) {
        return '<div class="ga-error">❌ No se pudo acceder a ninguna URL. Verifica que sea accesible públicamente.</div>';
    }

    $analysis = ga_analyze_content($combined_content, $combined_html, $url, $sector, $company_size, $pages_analyzed);
    ga_save_audit($analysis, $sector, $company_size, $depth);

    return ga_generate_results($analysis, $url, $pages_analyzed, $pages_failed, $sector, $company_size);
}

// ============================================================
// URLs A ANALIZAR SEGÚN PROFUNDIDAD
// ============================================================

function ga_get_urls_to_analyze($base_url, $depth) {
    $urls = array($base_url);

    $parsed = parse_url($base_url);
    $root   = $parsed['scheme'] . '://' . $parsed['host'];

    // Rutas de páginas de sostenibilidad típicas
    $sustainability_paths = array(
        '/sostenibilidad', '/sustainability', '/rsc', '/csr',
        '/responsabilidad-social', '/medio-ambiente', '/environmental',
        '/sobre-nosotros', '/about-us', '/about', '/quienes-somos',
        '/politica-ambiental', '/environmental-policy',
        '/informe-sostenibilidad', '/sustainability-report',
        '/compromiso', '/commitment',
    );

    if ($depth === 'medium' || $depth === 'deep') {
        foreach ($sustainability_paths as $path) {
            $urls[] = rtrim($root, '/') . $path;
        }
    }

    if ($depth === 'deep') {
        // Intentar extraer enlaces internos de la página principal
        $main_content = ga_fetch_url_content($base_url);
        if ($main_content) {
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $main_content, $matches);
            $internal_links = array();
            foreach ($matches[1] as $link) {
                if (strpos($link, $root) === 0 || (strpos($link, '/') === 0 && strpos($link, '//') !== 0)) {
                    $full_link = (strpos($link, '/') === 0) ? $root . $link : $link;
                    // Filtrar solo enlaces con keywords relevantes
                    $relevant = array('eco', 'green', 'sostenib', 'sustain', 'ambiente', 'carbon', 'impact', 'rsc', 'csr', 'certif', 'report', 'annual');
                    foreach ($relevant as $kw) {
                        if (stripos($full_link, $kw) !== false) {
                            $internal_links[] = $full_link;
                            break;
                        }
                    }
                }
            }
            $urls = array_merge($urls, array_unique(array_slice($internal_links, 0, 10)));
        }
    }

    return array_unique(array_slice($urls, 0, 30));
}

// ============================================================
// FETCH URL
// ============================================================

function ga_fetch_url_content($url) {
    $response = wp_remote_get($url, array(
        'timeout'    => 20,
        'user-agent' => 'Mozilla/5.0 (compatible; GreenwashingAuditBot/4.0; +https://github.com/yelmartinezseo)',
        'sslverify'  => true,
        'redirection'=> 3,
    ));

    if (is_wp_error($response)) {
        // Reintentar sin SSL verify si falla (solo para testing)
        $response = wp_remote_get($url, array(
            'timeout'    => 15,
            'user-agent' => 'Mozilla/5.0 (compatible; GreenwashingAuditBot/4.0)',
            'sslverify'  => false,
            'redirection'=> 3,
        ));
        if (is_wp_error($response)) return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return false;

    $body = wp_remote_retrieve_body($response);
    return ($body && strlen($body) > 50) ? $body : false;
}

// ============================================================
// ANÁLISIS DE CONTENIDO
// ============================================================

function ga_analyze_content($text_content, $html_content, $url, $sector, $company_size, $pages_analyzed) {
    $issues = array();
    $score  = 100;
    $lower  = strtolower($text_content);

    // ----------------------------------------------------------
    // 1. TÉRMINOS VAGOS CON VERIFICACIÓN DE CONTEXTO
    // ----------------------------------------------------------
    $vague_terms = array(
        // ES
        'eco-friendly', 'sostenible', 'ecológico', 'ecologico', 'natural',
        'respetuoso con el medio ambiente', 'respetuoso con el planeta',
        'amigable con el medio ambiente', 'verde', 'comprometidos con el planeta',
        'comprometidos con el medio ambiente', 'responsable con el medio',
        // EN
        'eco', 'green', 'conscious', 'responsible', 'earth friendly',
        'environmentally friendly', 'planet friendly', 'climate friendly',
        'sustainable', 'environmental', 'nature friendly',
        // CAT/VAL
        'ecològic', 'sostenible', 'respectuós amb el medi',
    );

    $sustained_count  = 0;
    $unsupported_count = 0;

    foreach ($vague_terms as $term) {
        if (stripos($lower, $term) === false) continue;

        // Buscar contexto: ¿hay dato, %, enlace o certificación en los 300 chars siguientes?
        $pos = stripos($lower, $term);
        $context = substr($text_content, max(0, $pos - 50), 400);
        $has_data = ga_context_has_evidence($context, $html_content, $pos);

        if ($has_data) {
            $sustained_count++;
        } else {
            $unsupported_count++;
            if ($unsupported_count <= 5) { // máx 5 issues de este tipo para no saturar
                $issues[] = array(
                    'type'     => 'termino_vago',
                    'message'  => "Término vago sin sustanciación detectada: «{$term}»",
                    'context'  => esc_html(trim(substr($context, 0, 150))) . '…',
                    'severity' => 'medium',
                    'penalty'  => 4,
                );
                $score -= 4;
            }
        }
    }

    // ----------------------------------------------------------
    // 2. CERTIFICACIONES – VERIFICAR ENLACE Y CONTEXTO
    // ----------------------------------------------------------
    $certifications_official = array(
        'iso 14001', 'iso 14064', 'iso 50001', 'emas', 'eu ecolabel',
        'ecolabel europeo', 'etiqueta ecológica europea',
        'b corp', 'bcorp', 'leed', 'breeam',
        'fairtrade', 'comercio justo', 'fair trade',
        'organic', 'ecológico certificado',
        'rainforest alliance', 'fsc', 'pefc',
        'carbon trust', 'energy star', 'usda organic',
        'bluesign', 'cradle to cradle', 'zero discharge',
        'nordic swan', 'angel azul', 'blue angel',
    );

    foreach ($certifications_official as $cert) {
        if (stripos($lower, $cert) === false) continue;

        $pos     = stripos($lower, $cert);
        $context = substr($html_content, max(0, $pos - 20), 300);
        $has_link = ga_check_certification_link($context, $cert);

        if (!$has_link) {
            $issues[] = array(
                'type'     => 'certificacion_sin_detalles',
                'message'  => "Certificación «{$cert}» mencionada sin enlace verificable ni número de registro.",
                'context'  => '',
                'severity' => 'high',
                'penalty'  => 6,
            );
            $score -= 6;
        }
    }

    // Sellos sospechosos (no reconocidos por la UE)
    $sellos_sospechosos = ga_get_sellos_sospechosos();
    foreach ($sellos_sospechosos as $sello) {
        if (stripos($lower, $sello) !== false) {
            $issues[] = array(
                'type'     => 'sello_no_reconocido',
                'message'  => "Posible uso de sello o etiqueta no reconocido oficialmente: «{$sello}»",
                'context'  => '',
                'severity' => 'high',
                'penalty'  => 8,
            );
            $score -= 8;
            break;
        }
    }

    // ----------------------------------------------------------
    // 3. DECLARACIONES DE CARBONO SIN MÉTRICAS
    // ----------------------------------------------------------
    $carbon_terms = array(
        'carbon footprint', 'huella de carbono', 'carbon offset',
        'carbon neutral', 'carbono neutro', 'net zero', 'cero emisiones',
        'neutralidad de carbono', 'co2', 'emissions', 'emisiones',
        'climate neutral', 'clima neutro', 'compensación de carbono',
    );

    foreach ($carbon_terms as $term) {
        if (stripos($lower, $term) === false) continue;

        if (!ga_check_metrics_present($text_content)) {
            $issues[] = array(
                'type'     => 'declaracion_carbono_sin_metricas',
                'message'  => "Declaración sobre carbono/emisiones sin métricas cuantificables detectadas: «{$term}»",
                'context'  => '',
                'severity' => 'high',
                'penalty'  => 7,
            );
            $score -= 7;
            break; // una sola vez por web
        }
    }

    // ----------------------------------------------------------
    // 4. LENGUAJE ABSOLUTO
    // ----------------------------------------------------------
    $absolute_patterns = array(
        '/\b100\s*%\s*(natural|green|eco|sostenible|ecológico|biodegradable|renovable|limpio|clean|organic)\b/i',
        '/\b(completamente|totalmente|absolutamente)\s+(sostenible|ecológico|verde|natural|limpio)\b/i',
        '/\bsin\s*(ningún|ningún\s+tipo\s+de)?\s*impacto\s*(ambiental|medioambiental)\b/i',
        '/\bzero\s*(waste|emissions|carbon|impact)\b/i',
        '/\btotally\s*(green|eco|natural|sustainable)\b/i',
        '/\bcompletely\s*(sustainable|green|eco|natural)\b/i',
        '/\b(residuo\s*cero|emisiones\s*cero|impacto\s*cero)\b/i',
    );

    foreach ($absolute_patterns as $pattern) {
        if (preg_match($pattern, $text_content, $match)) {
            $issues[] = array(
                'type'     => 'lenguaje_absoluto',
                'message'  => "Lenguaje absoluto o hiperbólico sobre sostenibilidad: «" . esc_html($match[0]) . "»",
                'context'  => '',
                'severity' => 'high',
                'penalty'  => 8,
            );
            $score -= 8;
            break;
        }
    }

    // ----------------------------------------------------------
    // 5. DECLARACIONES DE BIODEGRADABILIDAD SIN CONDICIONES
    // ----------------------------------------------------------
    $biodeg_terms = array('biodegradable', 'compostable', 'compostabilidad', 'biodegradabilidad');
    foreach ($biodeg_terms as $term) {
        if (stripos($lower, $term) === false) continue;

        $pos     = stripos($lower, $term);
        $context = substr($text_content, max(0, $pos - 30), 300);
        // Condición: ¿menciona plazo, condiciones o norma (EN 13432)?
        $has_conditions = preg_match('/\b(en\s*13432|días|semanas|industrial|norma|estándar|standard|conditions?|condiciones?|plazo|\d+\s*%)\b/i', $context);

        if (!$has_conditions) {
            $issues[] = array(
                'type'     => 'declaracion_biodegradable',
                'message'  => "Declaración «{$term}» sin especificar condiciones ni norma de referencia (ej: EN 13432).",
                'context'  => esc_html(trim(substr($context, 0, 150))) . '…',
                'severity' => 'medium',
                'penalty'  => 5,
            );
            $score -= 5;
            break;
        }
    }

    // ----------------------------------------------------------
    // 6. IMÁGENES ENGAÑOSAS (solo cuando hay términos vagos activos)
    // ----------------------------------------------------------
    preg_match_all('/<img[^>]+>/i', $html_content, $images);
    $misleading_images = 0;
    foreach ($images[0] as $img) {
        if (ga_check_misleading_image($img)) $misleading_images++;
    }

    // Solo penaliza si además hay términos vagos no sustanciados
    if ($misleading_images > 2 && $unsupported_count > 0) {
        $issues[] = array(
            'type'     => 'imagen_enganosa',
            'message'  => "Combinación de términos vagos + imágenes de naturaleza sin contexto ({$misleading_images} imágenes detectadas). Puede inducir a error según Directiva 2005/29/CE.",
            'context'  => '',
            'severity' => 'low',
            'penalty'  => 3,
        );
        $score -= 3;
    }

    // ----------------------------------------------------------
    // 7. POLÍTICA DE SOSTENIBILIDAD
    // ----------------------------------------------------------
    $has_policy = ga_check_sustainability_policy($text_content);
    if (!$has_policy) {
        $penalty = ($company_size === 'grande' || $company_size === 'cotizada') ? 8 : 4;
        $issues[] = array(
            'type'     => 'falta_politica_sostenibilidad',
            'message'  => 'No se encontró referencia a política de sostenibilidad, informe ESG o estado de información no financiera.',
            'context'  => '',
            'severity' => ($company_size === 'grande' || $company_size === 'cotizada') ? 'high' : 'medium',
            'penalty'  => $penalty,
        );
        $score -= $penalty;
    }

    // ----------------------------------------------------------
    // 8. SECTOR FINANCIERO
    // ----------------------------------------------------------
    if ($sector === 'financiero') {
        $finance_terms = array(
            'fondo sostenible', 'sustainable fund', 'inversión sostenible',
            'green bond', 'bono verde', 'esg fund', 'fondo esg',
            'inversión responsable', 'sri', 'responsible investment',
            'impact investing', 'inversión de impacto',
        );
        foreach ($finance_terms as $term) {
            if (stripos($lower, $term) !== false) {
                if (!ga_check_metrics_present($text_content)) {
                    $issues[] = array(
                        'type'     => 'obligacion_financiera',
                        'message'  => "Producto financiero sostenible («{$term}») sin indicadores de sostenibilidad ni referencia a clasificación SFDR.",
                        'context'  => '',
                        'severity' => 'high',
                        'penalty'  => 9,
                    );
                    $score -= 9;
                    break;
                }
            }
        }
    }

    // ----------------------------------------------------------
    // 9. DECLARACIÓN SIN ALCANCE (genérica para todo el producto/empresa)
    // ----------------------------------------------------------
    $scope_claims = array(
        'producto sostenible', 'empresa sostenible', 'sustainable product',
        'sustainable company', 'empresa verde', 'green company',
    );
    foreach ($scope_claims as $claim) {
        if (stripos($lower, $claim) !== false) {
            // Buscar si hay definición de alcance del ciclo de vida
            $lifecycle_terms = array('ciclo de vida', 'lifecycle', 'life cycle', 'alcance', 'scope', 'fabricación', 'distribución', 'uso', 'fin de vida');
            $has_scope = false;
            foreach ($lifecycle_terms as $lt) {
                if (stripos($lower, $lt) !== false) { $has_scope = true; break; }
            }
            if (!$has_scope) {
                $issues[] = array(
                    'type'     => 'sin_alcance_definido',
                    'message'  => "Declaración «{$claim}» sin definir el alcance del ciclo de vida al que se refiere.",
                    'context'  => '',
                    'severity' => 'medium',
                    'penalty'  => 5,
                );
                $score -= 5;
                break;
            }
        }
    }

    // ----------------------------------------------------------
    // 10. BONIFICACIONES POR BUENAS PRÁCTICAS
    // ----------------------------------------------------------
    // Métricas específicas con porcentaje
    if (preg_match('/\b\d+\s*%\s*(de\s+)?(reducción|reduction|menos|less|renovable|renewable|reciclad|recycled)\b/i', $text_content)) {
        $score += 5;
    }
    // Referencia a norma ISO o estándar
    if (preg_match('/\biso\s+\d{4,5}\b/i', $text_content)) {
        $score += 3;
    }
    // Año objetivo concreto
    if (preg_match('/\b(20[2-5]\d)\s*(meta|objetivo|goal|target|compromi)/i', $text_content)) {
        $score += 3;
    }
    // Tercera parte verificadora
    if (preg_match('/\b(verificado|auditado|certificado)\s+(por|by)\b/i', $text_content)) {
        $score += 4;
    }

    $score = max(0, min(100, $score));

    // Ordenar issues por severity
    usort($issues, function($a, $b) {
        $order = array('high' => 0, 'medium' => 1, 'low' => 2);
        return $order[$a['severity']] - $order[$b['severity']];
    });

    return array(
        'url'            => $url,
        'score'          => $score,
        'issues'         => $issues,
        'total_issues'   => count($issues),
        'timestamp'      => current_time('mysql'),
        'content_length' => strlen($text_content),
        'images_count'   => count($images[0]),
        'pages_analyzed' => count($pages_analyzed),
        'sector'         => $sector,
        'company_size'   => $company_size,
        'sustained_ok'   => $sustained_count,
    );
}

// ============================================================
// FUNCIONES AUXILIARES DE ANÁLISIS
// ============================================================

function ga_context_has_evidence($context, $html_content, $pos) {
    // ¿Hay porcentaje numérico?
    if (preg_match('/\b\d+\s*(%|por\s*ciento|percent)\b/i', $context)) return true;
    // ¿Hay toneladas, kg, kWh, CO2 u otra unidad de medida?
    if (preg_match('/\b\d+[\.,]?\d*\s*(ton|kg|kw|mw|co2|tco2|gco2|litros?|m3|kwh|gwh)\b/i', $context)) return true;
    // ¿Hay enlace HTML en el entorno?
    $html_context = substr($html_content, max(0, $pos - 50), 500);
    if (preg_match('/<a[^>]+href=["\'][^"\']+["\'][^>]*>/i', $html_context)) return true;
    // ¿Hay año + meta/objetivo?
    if (preg_match('/\b(20[2-5]\d)\b/', $context)) return true;
    // ¿Hay mención a norma?
    if (preg_match('/\biso\s*\d{4,5}|emas|eu ecolabel|fsc|pefc|b\s*corp\b/i', $context)) return true;
    return false;
}

function ga_check_certification_link($html_context, $cert) {
    // Enlace en los 200 chars de contexto HTML
    if (preg_match('/<a[^>]+href=["\'][^"\']{10,}["\'][^>]*>/i', $html_context)) return true;
    // Número de registro o ID
    if (preg_match('/\b[A-Z]{2,4}[-\s]?\d{4,}\b/', $html_context)) return true;
    // URL explícita
    if (preg_match('/https?:\/\/\S+/i', $html_context)) return true;
    return false;
}

function ga_check_metrics_present($content) {
    $patterns = array(
        '/\b\d+[\.,]?\d*\s*(toneladas?|tons?|kg|tco2|gco2|co2e?)\b/i',
        '/\b(reduc\w+|lower\w*|decrease\w*|ahorro|saving)\s+\w{0,10}\s+\d+\s*%/i',
        '/\b\d+\s*%\s*(de\s+)?(reducción|menos|less|reduction)\b/i',
        '/\b(meta|objetivo|goal|target)\s+(de\s+)?(\d+|net\s+zero)/i',
        '/\bscope\s*[123]\b/i',
    );
    foreach ($patterns as $p) {
        if (preg_match($p, $content)) return true;
    }
    return false;
}

function ga_check_misleading_image($img_tag) {
    $keywords = array('nature', 'leaf', 'leaves', 'tree', 'forest', 'green', 'earth', 'planet', 'eco', 'environment', 'grass', 'field', 'meadow');
    foreach ($keywords as $kw) {
        if (stripos($img_tag, $kw) !== false) return true;
    }
    return false;
}

function ga_check_sustainability_policy($content) {
    $terms = array(
        'sustainability report', 'environmental policy', 'csr report',
        'corporate social responsibility', 'informe de sostenibilidad',
        'política ambiental', 'sustainability policy', 'environmental report',
        'esg report', 'impact report', 'estado de información no financiera',
        'einf', 'memoria de sostenibilidad', 'informe anual de sostenibilidad',
        'política de responsabilidad', 'política rsec', 'política rsc',
        'compromisos ambientales', 'objetivos de sostenibilidad',
        'informe gri', 'gri report', 'reporting de sostenibilidad',
    );
    foreach ($terms as $term) {
        if (stripos($content, $term) !== false) return true;
    }
    return false;
}

// ============================================================
// GUARDAR EN BASE DE DATOS
// ============================================================

function ga_save_audit($analysis, $sector, $company_size, $depth) {
    global $wpdb;
    $table = $wpdb->prefix . 'greenwashing_audits';
    $wpdb->insert($table, array(
        'url'          => $analysis['url'],
        'score'        => $analysis['score'],
        'total_issues' => $analysis['total_issues'],
        'sector'       => $sector,
        'company_size' => $company_size,
        'depth'        => $depth,
        'issues_json'  => wp_json_encode($analysis['issues']),
        'created_at'   => current_time('mysql'),
    ), array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s'));
}

// ============================================================
// HISTÓRICO DE AUDITORÍAS
// ============================================================

function ga_render_historico() {
    global $wpdb;
    $table = $wpdb->prefix . 'greenwashing_audits';

    // Verificar que la tabla existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) return;

    $rows = $wpdb->get_results("SELECT id, url, score, total_issues, sector, company_size, created_at FROM $table ORDER BY created_at DESC LIMIT 10");
    if (empty($rows)) return;

    echo '<div class="ga-historico-section">';
    echo '<h4>📊 Últimas 10 auditorías realizadas</h4>';
    echo '<div class="ga-historico-table-wrap"><table class="ga-historico-table">';
    echo '<thead><tr><th>URL</th><th>Puntuación</th><th>Incumplimientos</th><th>Sector</th><th>Fecha</th></tr></thead>';
    echo '<tbody>';
    foreach ($rows as $row) {
        $color = $row->score >= 80 ? '#10b981' : ($row->score >= 60 ? '#f59e0b' : '#ef4444');
        printf(
            '<tr><td class="ga-hist-url"><a href="%s" target="_blank" rel="noopener">%s</a></td><td><span class="ga-hist-score" style="background:%s">%d</span></td><td>%d</td><td>%s</td><td>%s</td></tr>',
            esc_url($row->url),
            esc_html(preg_replace('/^https?:\/\/(www\.)?/', '', $row->url)),
            esc_attr($color),
            intval($row->score),
            intval($row->total_issues),
            esc_html($row->sector),
            esc_html(date('d/m/Y H:i', strtotime($row->created_at)))
        );
    }
    echo '</tbody></table></div></div>';
}

// ============================================================
// GENERACIÓN DE RESULTADOS HTML
// ============================================================

function ga_generate_results($analysis, $url, $pages_analyzed, $pages_failed, $sector, $company_size) {
    $score        = $analysis['score'];
    $issues       = $analysis['issues'];
    $total_issues = $analysis['total_issues'];
    $normativa    = ga_get_normativa();

    if ($score >= 80)      { $color = '#10b981'; $message = '✅ Bajo riesgo de greenwashing detectado'; $class = 'ga-score-good'; }
    elseif ($score >= 60)  { $color = '#f59e0b'; $message = '⚠️ Riesgo moderado – requiere revisión'; $class = 'ga-score-medium'; }
    else                   { $color = '#ef4444'; $message = '❌ Alto riesgo de greenwashing'; $class = 'ga-score-bad'; }

    $sector_labels = array(
        'general' => 'General', 'ecommerce' => 'E-commerce', 'financiero' => 'Financiero',
        'manufactura' => 'Manufactura', 'alimentacion' => 'Alimentación', 'textil' => 'Moda/Textil',
        'construccion' => 'Construcción', 'energia' => 'Energía', 'turismo' => 'Turismo', 'servicios' => 'Servicios',
    );
    $size_labels = array(
        'autonomo' => 'Autónomo', 'pyme' => 'PYME', 'mediana' => 'Mediana empresa',
        'grande' => 'Gran empresa', 'cotizada' => 'Cotizada/Financiera',
    );

    ob_start();
    ?>
    <div class="ga-results" id="ga-results-top">

        <div class="ga-legal-disclaimer">
            ⚠️ <strong>Aviso legal:</strong> Este análisis es orientativo y no constituye asesoramiento jurídico ni auditoría oficial. Los resultados deben ser validados por un profesional cualificado antes de tomar decisiones legales o comerciales.
        </div>

        <!-- SCORE CARD -->
        <div class="ga-score-card <?php echo $class; ?>">
            <div class="ga-score-inner">
                <div class="ga-score-circle" style="background:<?php echo $color; ?>">
                    <span class="ga-score-num"><?php echo $score; ?></span>
                    <span class="ga-score-sub">/100</span>
                </div>
                <div class="ga-score-meta">
                    <h3><?php echo $message; ?></h3>
                    <table class="ga-meta-table">
                        <tr><th>URL analizada</th><td><?php echo '<a href="'.esc_url($url).'" target="_blank" rel="noopener">'.esc_html($url).'</a>'; ?></td></tr>
                        <tr><th>Páginas analizadas</th><td><?php echo count($pages_analyzed); ?> <span class="ga-meta-small">(<?php echo implode(', ', array_map(function($u){ return '<code>' . esc_html(parse_url($u, PHP_URL_PATH) ?: '/') . '</code>'; }, $pages_analyzed)); ?>)</span></td></tr>
                        <?php if (!empty($pages_failed)): ?>
                        <tr><th>Sin acceso</th><td class="ga-meta-failed"><?php echo count($pages_failed); ?> páginas no accesibles</td></tr>
                        <?php endif; ?>
                        <tr><th>Incumplimientos</th><td><strong><?php echo $total_issues; ?></strong></td></tr>
                        <tr><th>Sector</th><td><?php echo esc_html($sector_labels[$sector] ?? $sector); ?></td></tr>
                        <tr><th>Tamaño empresa</th><td><?php echo esc_html($size_labels[$company_size] ?? $company_size); ?></td></tr>
                        <tr><th>Contenido analizado</th><td><?php echo number_format($analysis['content_length']); ?> caracteres en <?php echo $analysis['images_count']; ?> elementos</td></tr>
                        <tr><th>Declaraciones OK</th><td class="ga-ok-count">✅ <?php echo intval($analysis['sustained_ok']); ?> términos con evidencia detectada</td></tr>
                        <tr><th>Fecha</th><td><?php echo date('d/m/Y H:i'); ?></td></tr>
                    </table>
                </div>
            </div>
        </div><!-- .ga-score-card -->

        <!-- ISSUES -->
        <?php if ($total_issues > 0): ?>
        <div class="ga-issues-wrap">
            <h4>📋 Incumplimientos detectados</h4>
            <p class="ga-issues-intro">Cada incumplimiento incluye la normativa específica que podría estar siendo vulnerada.</p>

            <?php foreach ($issues as $idx => $issue):
                $norm_key  = $issue['type'];
                $norm_data = $normativa[$norm_key] ?? null;
                $sev_label = array('high' => 'ALTO', 'medium' => 'MEDIO', 'low' => 'BAJO');
            ?>
            <div class="ga-issue ga-issue-<?php echo esc_attr($issue['severity']); ?>">
                <div class="ga-issue-header">
                    <span class="ga-badge ga-badge-<?php echo esc_attr($issue['severity']); ?>"><?php echo $sev_label[$issue['severity']] ?? strtoupper($issue['severity']); ?></span>
                    <span class="ga-penalty">-<?php echo $issue['penalty']; ?> pts</span>
                    <?php if ($norm_data): ?>
                    <span class="ga-issue-label"><?php echo esc_html($norm_data['label']); ?></span>
                    <?php endif; ?>
                </div>

                <p class="ga-issue-msg"><?php echo esc_html($issue['message']); ?></p>

                <?php if (!empty($issue['context'])): ?>
                <div class="ga-issue-context">
                    <strong>Contexto detectado:</strong> "<?php echo $issue['context']; ?>"
                </div>
                <?php endif; ?>

                <?php if ($norm_data && !empty($norm_data['normas'])): ?>
                <div class="ga-issue-normas">
                    <strong>📜 Normativa aplicable:</strong>
                    <ul>
                        <?php foreach ($norm_data['normas'] as $norma): ?>
                        <li><?php echo esc_html($norma); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div><!-- .ga-issues-wrap -->

        <!-- RECOMENDACIONES -->
        <div class="ga-recommendations">
            <h5>💡 Recomendaciones para mejorar</h5>
            <div class="ga-recs-grid">
                <div class="ga-rec-item">
                    <strong>📊 Sustancia las afirmaciones</strong>
                    <p>Cada declaración medioambiental debe ir acompañada de datos cuantificables, fecha y metodología de cálculo.</p>
                </div>
                <div class="ga-rec-item">
                    <strong>🏅 Certifíquese oficialmente</strong>
                    <p>Use sellos reconocidos por la UE (EU Ecolabel, EMAS, ISO 14001) y enlace al registro oficial con su número de certificado.</p>
                </div>
                <div class="ga-rec-item">
                    <strong>📏 Defina el alcance</strong>
                    <p>Especifique a qué parte del ciclo de vida se refiere cada afirmación: fabricación, uso, distribución o fin de vida.</p>
                </div>
                <div class="ga-rec-item">
                    <strong>📄 Publique su política</strong>
                    <p>Redacte y publique una política de sostenibilidad con objetivos medibles, plazos y mecanismo de seguimiento.</p>
                </div>
                <div class="ga-rec-item">
                    <strong>🔍 Verifique externamente</strong>
                    <p>Encargue una verificación de terceros independiente. Menciónela con nombre del auditor y fecha en su web.</p>
                </div>
                <div class="ga-rec-item">
                    <strong>⚖️ Evite lenguaje absoluto</strong>
                    <p>Evite "100% ecológico", "completamente sostenible" o "sin impacto". La Directiva 2024/825/UE los prohíbe expresamente sin prueba total.</p>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="ga-no-issues">
            <p>✅ No se detectaron incumplimientos significativos en el análisis.</p>
            <p><small>El análisis cubre las señales más comunes de greenwashing en texto público. Para una auditoría con validez legal, solicite una evaluación profesional.</small></p>
        </div>
        <?php endif; ?>

        <!-- ACCIONES -->
        <div class="ga-actions">
            <button onclick="window.print()" class="ga-btn-action">🖨️ Imprimir / Guardar PDF</button>
            <button onclick="ga_copy_results()" class="ga-btn-action" id="ga-copy-btn">📋 Copiar resumen</button>
            <button onclick="location.reload()" class="ga-btn-action">🔄 Nueva auditoría</button>
        </div>

        <div class="ga-audit-note">
            <strong>Nota técnica:</strong> El análisis examina el contenido HTML público de las páginas accesibles. No accede a PDFs, imágenes con texto o contenido detrás de login. Para una auditoría completa de comunicación de sostenibilidad, consulte con un profesional ESG.
        </div>

        <script>
        function ga_copy_results() {
            var text = "AUDITORÍA DE GREENWASHING\n";
            text += "URL: <?php echo esc_js($url); ?>\n";
            text += "Puntuación: <?php echo $score; ?>/100\n";
            text += "Incumplimientos: <?php echo $total_issues; ?>\n";
            text += "Sector: <?php echo esc_js($sector_labels[$sector] ?? $sector); ?>\n";
            text += "Fecha: <?php echo date('d/m/Y H:i'); ?>\n";
            <?php foreach ($issues as $i): ?>
            text += "- [<?php echo strtoupper($i['severity']); ?>] <?php echo esc_js($i['message']); ?>\n";
            <?php endforeach; ?>
            navigator.clipboard.writeText(text).then(function() {
                document.getElementById('ga-copy-btn').textContent = '✅ Copiado';
                setTimeout(function(){ document.getElementById('ga-copy-btn').textContent = '📋 Copiar resumen'; }, 2000);
            });
        }
        </script>

    </div><!-- .ga-results -->
    <?php
    return ob_get_clean();
}

// ============================================================
// ESTILOS Y SCRIPTS
// ============================================================

function ga_enqueue_assets() {
    static $done = false;
    if ($done) return;
    $done = true;

    echo '
<style>
/* ---- BASE ---- */
.ga-tool {
    max-width: 920px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 15px;
    color: #1f2937;
    line-height: 1.6;
}

/* ---- FORM ---- */
.ga-form-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 28px;
    margin-bottom: 28px;
    box-shadow: 0 2px 6px rgba(0,0,0,.07);
}
.ga-form-section h3 { margin: 0 0 6px; font-size: 22px; color: #111827; }
.ga-subtitle { color: #6b7280; margin: 0 0 24px; font-size: 14px; }
.ga-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px; }
.ga-full-width { grid-column: 1 / -1; }
.ga-form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; font-size: 14px; }
.ga-required { color: #ef4444; }
.ga-input {
    width: 100%; padding: 11px 14px; border: 1px solid #d1d5db;
    border-radius: 8px; font-size: 15px; box-sizing: border-box;
    transition: border-color .2s, box-shadow .2s; background: #fff;
}
.ga-input:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.12); }
.ga-btn-primary {
    background: #10b981; color: #fff; border: none;
    padding: 13px 32px; border-radius: 8px; font-size: 16px; font-weight: 600;
    cursor: pointer; transition: background .2s, transform .15s;
}
.ga-btn-primary:hover { background: #059669; transform: translateY(-1px); }
.ga-disclaimer-form { font-size: 12px; color: #9ca3af; margin: 14px 0 0; }

/* ---- LEGAL DISCLAIMER ---- */
.ga-legal-disclaimer {
    background: #fffbeb; border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b; border-radius: 6px;
    padding: 12px 16px; font-size: 13px; color: #78350f;
    margin-bottom: 20px; line-height: 1.5;
}

/* ---- SCORE CARD ---- */
.ga-score-card {
    border-radius: 14px; padding: 24px; margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
    border: 2px solid transparent;
}
.ga-score-good  { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border-color: #10b981; }
.ga-score-medium{ background: linear-gradient(135deg,#fffbeb,#fef3c7); border-color: #f59e0b; }
.ga-score-bad   { background: linear-gradient(135deg,#fef2f2,#fee2e2); border-color: #ef4444; }
.ga-score-inner { display: flex; align-items: flex-start; gap: 28px; flex-wrap: wrap; }
.ga-score-circle {
    width: 130px; height: 130px; border-radius: 50%;
    display: flex; flex-direction: column; justify-content: center;
    align-items: center; color: #fff; flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0,0,0,.15);
}
.ga-score-num  { font-size: 50px; font-weight: 800; line-height: 1; }
.ga-score-sub  { font-size: 18px; opacity: .85; }
.ga-score-meta { flex: 1; min-width: 280px; }
.ga-score-meta h3 { margin: 0 0 14px; font-size: 20px; }
.ga-meta-table { border-collapse: collapse; width: 100%; font-size: 14px; }
.ga-meta-table th, .ga-meta-table td { padding: 5px 10px 5px 0; vertical-align: top; text-align: left; }
.ga-meta-table th { color: #6b7280; font-weight: 600; white-space: nowrap; padding-right: 14px; }
.ga-meta-table td a { color: #0284c7; word-break: break-all; }
.ga-meta-small { color: #9ca3af; font-size: 12px; }
.ga-meta-failed { color: #dc2626; }
.ga-ok-count { color: #059669; font-weight: 600; }

/* ---- ISSUES ---- */
.ga-issues-wrap h4 { font-size: 18px; margin-bottom: 6px; }
.ga-issues-intro { font-size: 13px; color: #6b7280; margin-bottom: 16px; }
.ga-issue {
    border-left: 4px solid; border-radius: 0 10px 10px 0;
    padding: 18px 20px; margin-bottom: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,.05);
}
.ga-issue-high   { border-color: #ef4444; background: #fef2f2; }
.ga-issue-medium { border-color: #f59e0b; background: #fffbeb; }
.ga-issue-low    { border-color: #10b981; background: #f0fdf4; }
.ga-issue-header { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
.ga-badge { padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: .5px; }
.ga-badge-high   { background: #ef4444; color: #fff; }
.ga-badge-medium { background: #f59e0b; color: #fff; }
.ga-badge-low    { background: #10b981; color: #fff; }
.ga-penalty { font-weight: 700; color: #dc2626; font-size: 15px; }
.ga-issue-label { font-size: 13px; color: #6b7280; font-style: italic; }
.ga-issue-msg { margin: 6px 0 10px; font-size: 15px; color: #111827; }
.ga-issue-context {
    background: rgba(0,0,0,.04); border-radius: 6px;
    padding: 8px 12px; font-size: 13px; color: #374151;
    margin-bottom: 10px; font-style: italic;
}
.ga-issue-normas { margin-top: 10px; }
.ga-issue-normas strong { font-size: 13px; color: #374151; }
.ga-issue-normas ul { margin: 6px 0 0 16px; padding: 0; }
.ga-issue-normas li { font-size: 13px; color: #4b5563; margin-bottom: 4px; line-height: 1.5; }

/* ---- RECOMMENDATIONS ---- */
.ga-recommendations {
    background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    border: 1px solid #bae6fd; border-radius: 12px;
    padding: 22px 24px; margin-top: 22px;
}
.ga-recommendations h5 { margin: 0 0 16px; font-size: 17px; color: #0369a1; }
.ga-recs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.ga-rec-item { background: #fff; border-radius: 8px; padding: 14px 16px; font-size: 13px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.ga-rec-item strong { display: block; margin-bottom: 5px; color: #0c4a6e; font-size: 14px; }
.ga-rec-item p { margin: 0; color: #374151; }

/* ---- NO ISSUES ---- */
.ga-no-issues {
    background: #f0fdf4; border: 2px solid #bbf7d0;
    color: #166534; padding: 22px; border-radius: 12px;
    text-align: center; font-size: 17px; margin: 16px 0;
}

/* ---- ACTIONS ---- */
.ga-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
.ga-btn-action {
    padding: 11px 22px; border-radius: 8px; border: 1px solid #d1d5db;
    background: #fff; color: #374151; font-size: 14px; font-weight: 500;
    cursor: pointer; transition: all .2s; flex: 1; min-width: 160px; text-align: center;
}
.ga-btn-action:hover { background: #f9fafb; box-shadow: 0 2px 6px rgba(0,0,0,.1); transform: translateY(-1px); }

/* ---- AUDIT NOTE ---- */
.ga-audit-note {
    margin-top: 18px; padding: 12px 16px; background: #f9fafb;
    border-radius: 8px; border-left: 4px solid #9ca3af;
    font-size: 13px; color: #6b7280;
}

/* ---- RESOURCES ---- */
.ga-resources-section {
    margin-top: 28px; padding: 20px 24px;
    background: linear-gradient(135deg,#f8fafc,#f1f5f9);
    border-radius: 12px; border-left: 5px solid #0ea5e9;
}
.ga-resources-section h4 { margin: 0 0 14px; color: #0369a1; font-size: 16px; }
.ga-resources-section ul { margin: 0; padding-left: 20px; }
.ga-resources-section li { margin-bottom: 7px; font-size: 14px; }
.ga-resources-section a { color: #0284c7; text-decoration: none; }
.ga-resources-section a:hover { text-decoration: underline; }

/* ---- HISTORICO ---- */
.ga-historico-section { margin-top: 28px; }
.ga-historico-section h4 { font-size: 16px; margin-bottom: 12px; }
.ga-historico-table-wrap { overflow-x: auto; }
.ga-historico-table {
    width: 100%; border-collapse: collapse; font-size: 13px;
    border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;
}
.ga-historico-table th {
    background: #f3f4f6; padding: 9px 12px; text-align: left;
    font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;
}
.ga-historico-table td { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; color: #4b5563; }
.ga-historico-table tr:last-child td { border-bottom: none; }
.ga-hist-url a { color: #0284c7; text-decoration: none; }
.ga-hist-score {
    display: inline-block; color: #fff; font-weight: 700;
    padding: 2px 10px; border-radius: 12px; font-size: 13px;
}

/* ---- ERROR ---- */
.ga-error {
    background: #fef2f2; border: 1px solid #fecaca;
    color: #dc2626; padding: 16px 18px; border-radius: 8px;
    font-weight: 500; margin: 16px 0;
}

/* ---- RESPONSIVE ---- */
@media (max-width: 768px) {
    .ga-tool { padding: 0; }
    .ga-form-grid, .ga-recs-grid { grid-template-columns: 1fr; }
    .ga-score-inner { flex-direction: column; align-items: center; text-align: center; }
    .ga-score-meta { min-width: 0; }
    .ga-actions { flex-direction: column; }
    .ga-score-circle { width: 110px; height: 110px; }
    .ga-score-num { font-size: 42px; }
    .ga-meta-table th { display: none; }
    .ga-meta-table td { display: block; padding-left: 0; }
}
@media print {
    .ga-actions, .ga-resources-section, .ga-historico-section, .ga-form-section, .ga-btn-primary { display: none !important; }
    .ga-tool { max-width: 100%; }
    .ga-issue-normas ul { page-break-inside: avoid; }
}
</style>

<script>
jQuery(document).ready(function($){
    $(".ga-form").on("submit", function(){
        var btn = $(this).find(".ga-btn-primary");
        btn.html("⏳ Analizando...").prop("disabled", true);
        setTimeout(function(){ btn.html("🔍 Iniciar Auditoría").prop("disabled", false); }, 35000);
    });

    if($("#ga-results-top").length){
        $("html,body").animate({ scrollTop: $("#ga-results-top").offset().top - 80 }, 600);
    }

    $(".ga-score-circle").css({ transform: "scale(0)", transition: "transform .7s cubic-bezier(.68,-.55,.265,1.55)" });
    setTimeout(function(){ $(".ga-score-circle").css("transform","scale(1)"); }, 250);

    $(".ga-issue").each(function(i){
        $(this).css({ opacity: 0, transform: "translateX(-16px)", transition: "all .4s ease " + (i * 0.08) + "s" });
        setTimeout(function(el){ $(el).css({ opacity: 1, transform: "translateX(0)" }); }(this), 400 + i * 80);
    });
});
</script>
';
}


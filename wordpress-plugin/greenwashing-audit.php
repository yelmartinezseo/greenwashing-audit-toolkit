<?php
/**
 * Plugin Name: Greenwashing Audit Toolkit – Auditor Activo
 * Description: Analiza URLs en busca de greenwashing y devuelve puntaje e incumplimientos
 * Version:     3.1
 * Author:      Yel Martinez
 * Author URI:  https://github.com/yelmartinezseo
 * Plugin URI:  https://github.com/yelmartinezseo/greenwashing-audit-toolkit
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: greenwashing-audit
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Shortcode principal para mostrar el formulario de auditoría
add_shortcode('greenwashing_audit', 'ga_audit_tool');
function ga_audit_tool($atts) {
    $url = isset($_POST['audit_url']) ? esc_url_raw($_POST['audit_url']) : '';
    $result_html = '';
    
    if (!empty($url) && isset($_POST['start_audit'])) {
        $result_html = ga_perform_audit($url);
    }
    
    ob_start();
    ?>
    <div class="greenwashing-audit-tool">
        <div class="audit-form-section">
            <h3>🔍 Auditoría de Greenwashing</h3>
            <p>Analiza cualquier página web en busca de prácticas de greenwashing.</p>
            
            <form method="post" class="audit-form">
                <div class="form-group">
                    <label for="audit_url">URL a auditar:</label>
                    <input type="url" 
                           id="audit_url" 
                           name="audit_url" 
                           value="<?php echo $url; ?>" 
                           placeholder="https://ejemplo.com" 
                           required
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="audit_depth">Profundidad del análisis:</label>
                    <select id="audit_depth" name="audit_depth" class="form-control">
                        <option value="basic">Básico (página principal)</option>
                        <option value="medium">Medio (+ páginas clave)</option>
                        <option value="deep">Profundo (+ enlaces internos)</option>
                    </select>
                </div>
                
                <button type="submit" name="start_audit" class="audit-button">
                    🔍 Iniciar Auditoría
                </button>
            </form>
        </div>
        
        <?php if (!empty($result_html)): ?>
        <div class="audit-results-section">
            <?php echo $result_html; ?>
        </div>
        <?php endif; ?>
        
        <div class="audit-templates-section">
            <h4>📋 Otras opciones</h4>
            <p>
                <a href="https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=auditoria-completa.yml"
                   target="_blank" rel="noopener" class="template-link">
                    📊 Solicitar auditoría completa vía GitHub
                </a>
            </p>
            <p>
                <a href="https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=nueva-pregunta-checklist.yml"
                   target="_blank" rel="noopener" class="template-link">
                    📝 Proponer nueva pregunta al checklist
                </a>
            </p>
        </div>
    </div>
    <?php
    
    // Añadir estilos y scripts inline
    ga_enqueue_assets();
    
    return ob_get_clean();
}

// Función principal que ejecuta la auditoría
function ga_perform_audit($url) {
    // Verificar URL válida
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '<div class="audit-error">❌ URL inválida. Por favor, introduce una URL completa (ej: https://ejemplo.com)</div>';
    }
    
    // Obtener contenido de la página
    $content = ga_fetch_url_content($url);
    
    if (!$content) {
        return '<div class="audit-error">❌ No se pudo acceder a la URL. Verifica que sea accesible públicamente.</div>';
    }
    
    // Realizar análisis
    $analysis = ga_analyze_content($content, $url);
    
    // Generar resultados
    return ga_generate_results($analysis, $url);
}

// Obtener contenido de la URL
function ga_fetch_url_content($url) {
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'user-agent' => 'Greenwashing-Audit-Toolkit/3.1 (+https://github.com/yelmartinezseo/greenwashing-audit-toolkit)'
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    return $body ?: false;
}

// Analizar contenido en busca de indicadores de greenwashing
function ga_analyze_content($content, $url) {
    $issues = array();
    $score = 100;
    
    $lower_content = strtolower($content);
    
    // 1. Términos vagos
    $vague_terms = array(
        'eco-friendly', 'sostenible', 'green', 'ecológico', 'natural',
        'eco', 'conscious', 'responsible', 'verde', 'amigable con el medio ambiente',
        'climate neutral', 'carbon neutral', 'zero waste', 'respetuoso con el planeta'
    );
    
    foreach ($vague_terms as $term) {
        if (stripos($lower_content, $term) !== false) {
            $issues[] = array(
                'type' => 'termino_vago',
                'message' => "Uso de término vago o no verificable: '$term'",
                'severity' => 'medium',
                'penalty' => 3
            );
            $score -= 3;
        }
    }
    
    // 2. Certificaciones sin detalles
    $certifications = array(
        'iso 14001', 'b corp', 'leed', 'fairtrade', 'organic', 'ecolabel',
        'rainforest alliance', 'fsc', 'carbon trust'
    );
    
    foreach ($certifications as $cert) {
        if (stripos($lower_content, $cert) !== false) {
            if (!ga_check_certification_details($content, $cert)) {
                $issues[] = array(
                    'type' => 'certificacion_sin_detalles',
                    'message' => "Certificación mencionada sin detalles verificables: '$cert'",
                    'severity' => 'high',
                    'penalty' => 5
                );
                $score -= 5;
            }
        }
    }
    
    // 3. Declaraciones de carbono sin métricas
    $carbon_terms = array('carbon footprint', 'carbon offset', 'carbon neutral', 'net zero');
    foreach ($carbon_terms as $term) {
        if (stripos($lower_content, $term) !== false) {
            if (!ga_check_metrics_present($content)) {
                $issues[] = array(
                    'type' => 'declaracion_carbono_sin_metricas',
                    'message' => "Declaración de carbono sin métricas específicas: '$term'",
                    'severity' => 'high',
                    'penalty' => 6
                );
                $score -= 6;
            }
        }
    }
    
    // 4. Imágenes engañosas
    preg_match_all('/<img[^>]+>/i', $content, $images);
    foreach ($images[0] as $img) {
        if (ga_check_misleading_image($img)) {
            $issues[] = array(
                'type' => 'imagen_engañosa',
                'message' => 'Posible uso de imágenes de naturaleza/verdes sin relación directa',
                'severity' => 'low',
                'penalty' => 2
            );
            $score -= 2;
        }
    }
    
    // 5. Falta política de sostenibilidad
    if (!ga_check_sustainability_policy($content, $url)) {
        $issues[] = array(
            'type' => 'falta_politica_sostenibilidad',
            'message' => 'No se encontró política de sostenibilidad/claridad verificable',
            'severity' => 'medium',
            'penalty' => 4
        );
        $score -= 4;
    }
    
    // 6. Lenguaje de greenwashing
    $greenwashing_patterns = array(
        '/\b100%\s*(natural|green|eco)\b/i',
        '/\bcompletamente\s*(sostenible|ecológico)\b/i',
        '/\bsin\s*impacto\s*ambiental\b/i'
    );
    
    foreach ($greenwashing_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $issues[] = array(
                'type' => 'lenguaje_greenwashing',
                'message' => 'Lenguaje absoluto o de greenwashing clásico detectado',
                'severity' => 'high',
                'penalty' => 7
            );
            $score -= 7;
        }
    }
    
    // Asegurar score no negativo
    $score = max(0, $score);
    
    return array(
        'url' => $url,
        'score' => $score,
        'issues' => $issues,
        'total_issues' => count($issues),
        'timestamp' => current_time('mysql')
    );
}

// Funciones auxiliares
function ga_check_certification_details($content, $certification) {
    $pattern = '/(' . preg_quote($certification, '/') . ')[^<]*(?:<a[^>]+>|https?:\/\/)/i';
    return preg_match($pattern, $content);
}

function ga_check_metrics_present($content) {
    $metric_patterns = array(
        '/\b\d+\s*(toneladas|tons|kg|CO2|%)\b/i',
        '/\breducido\s+en\s+\d+/i',
        '/\bmetas?\s+de\s+\d+/i'
    );
    
    foreach ($metric_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }
    return false;
}

function ga_check_misleading_image($img_tag) {
    $green_keywords = array('nature', 'leaf', 'tree', 'green', 'forest', 'earth', 'planet', 'eco');
    foreach ($green_keywords as $keyword) {
        if (stripos($img_tag, $keyword) !== false) {
            $product_keywords = array('product', 'buy', 'shop', 'order', 'price');
            foreach ($product_keywords as $product) {
                if (stripos($img_tag, $product) !== false) {
                    return true;
                }
            }
        }
    }
    return false;
}

function ga_check_sustainability_policy($content, $url) {
    $policy_terms = array(
        'sustainability report',
        'environmental policy',
        'csr report',
        'corporate social responsibility',
        'informe de sostenibilidad',
        'política ambiental'
    );
    
    foreach ($policy_terms as $term) {
        if (stripos($content, $term) !== false) {
            return true;
        }
    }
    
    if (preg_match('/<a[^>]+(?:sustainability|environment|policy|report)[^>]*>/i', $content)) {
        return true;
    }
    
    return false;
}

// Generar resultados HTML
function ga_generate_results($analysis, $url) {
    $score = $analysis['score'];
    $issues = $analysis['issues'];
    $total_issues = $analysis['total_issues'];
    
    if ($score >= 80) $color = '#10b981';
    elseif ($score >= 60) $color = '#f59e0b';
    else $color = '#ef4444';
    
    if ($score >= 80) {
        $message = '✅ Buenas prácticas detectadas';
    } elseif ($score >= 60) {
        $message = '⚠️ Algunas áreas necesitan mejora';
    } else {
        $message = '❌ Significativo riesgo de greenwashing';
    }
    
    ob_start();
    ?>
    <div class="audit-results">
        <div class="score-card" style="border-color: <?php echo $color; ?>;">
            <h3>Resultados de la Auditoría</h3>
            <div class="score-display">
                <div class="score-circle" style="background: <?php echo $color; ?>;">
                    <span class="score-number"><?php echo $score; ?></span>
                    <span class="score-label">/100</span>
                </div>
                <div class="score-info">
                    <h4><?php echo $message; ?></h4>
                    <p>URL analizada: <strong><?php echo esc_url($url); ?></strong></p>
                    <p>Problemas detectados: <strong><?php echo $total_issues; ?></strong></p>
                    <p>Fecha: <?php echo date('d/m/Y H:i'); ?></p>
                </div>
            </div>
        </div>
        
        <?php if ($total_issues > 0): ?>
        <div class="issues-list">
            <h4>📋 Incumplimientos Detectados</h4>
            <div class="issues-container">
                <?php foreach ($issues as $index => $issue): 
                    $severity_class = 'severity-' . $issue['severity'];
                ?>
                <div class="issue-item <?php echo $severity_class; ?>">
                    <div class="issue-header">
                        <span class="issue-badge"><?php echo strtoupper($issue['severity']); ?></span>
                        <span class="issue-penalty">-<?php echo $issue['penalty']; ?> pts</span>
                    </div>
                    <p class="issue-message"><?php echo esc_html($issue['message']); ?></p>
                    <div class="issue-type">Tipo: <?php echo str_replace('_', ' ', $issue['type']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="recommendations">
                <h5>💡 Recomendaciones</h5>
                <ul>
                    <li>Proporciona datos específicos y verificables</li>
                    <li>Incluye certificaciones con enlaces a detalles</li>
                    <li>Evita lenguaje absoluto (100%, completamente, etc.)</li>
                    <li>Publica una política de sostenibilidad clara</li>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <div class="no-issues">
            <p>✅ No se detectaron problemas significativos de greenwashing.</p>
        </div>
        <?php endif; ?>
        
        <div class="audit-actions">
            <button onclick="window.print()" class="action-button">🖨️ Imprimir Reporte</button>
            <a href="https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=auditoria-completa.yml&title=Auditoría%20para%20<?php echo urlencode($url); ?>" 
               target="_blank" class="action-button github-button">
               📊 Solicitar Auditoría Profunda en GitHub
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Añadir estilos y scripts
function ga_enqueue_assets() {
    $css = '
    <style>
    .greenwashing-audit-tool {
        max-width: 800px;
        margin: 0 auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    
    .audit-form-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 16px;
    }
    
    .audit-button {
        background: #10b981;
        color: white;
        border: none;
        padding: 14px 28px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .audit-button:hover {
        background: #059669;
    }
    
    .audit-results {
        margin: 32px 0;
    }
    
    .score-card {
        border: 3px solid;
        border-radius: 16px;
        padding: 24px;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .score-display {
        display: flex;
        align-items: center;
        gap: 32px;
    }
    
    .score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
    }
    
    .score-number {
        font-size: 48px;
        font-weight: bold;
        line-height: 1;
    }
    
    .score-label {
        font-size: 18px;
        opacity: 0.9;
    }
    
    .issues-list {
        margin-top: 32px;
    }
    
    .issue-item {
        border-left: 4px solid;
        padding: 16px;
        margin-bottom: 12px;
        background: #fefce8;
        border-radius: 0 8px 8px 0;
    }
    
    .severity-high { border-color: #ef4444; background: #fef2f2; }
    .severity-medium { border-color: #f59e0b; background: #fffbeb; }
    .severity-low { border-color: #10b981; background: #f0fdf4; }
    
    .issue-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    
    .issue-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .severity-high .issue-badge { background: #ef4444; color: white; }
    .severity-medium .issue-badge { background: #f59e0b; color: white; }
    .severity-low .issue-badge { background: #10b981; color: white; }
    
    .audit-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        flex-wrap: wrap;
    }
    
    .action-button {
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid #cbd5e1;
        background: white;
    }
    
    .github-button {
        background: #24292e;
        color: white;
        border: none;
    }
    
    .github-button:hover {
        background: #1a1f24;
        color: white;
    }
    
    .audit-templates-section {
        margin-top: 32px;
        padding: 20px;
        background: #f0f9ff;
        border-radius: 8px;
        border-left: 4px solid #0ea5e9;
    }
    
    .template-link {
        color: #0369a1;
        text-decoration: none;
        font-weight: 500;
    }
    
    .template-link:hover {
        text-decoration: underline;
    }
    
    .audit-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 16px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .no-issues {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        margin: 20px 0;
    }
    
    .recommendations {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }
    
    .recommendations ul {
        margin: 10px 0 0 20px;
    }
    
    .recommendations li {
        margin-bottom: 8px;
    }
    
    @media (max-width: 768px) {
        .score-display {
            flex-direction: column;
            text-align: center;
        }
        
        .audit-actions {
            flex-direction: column;
        }
        
        .action-button {
            width: 100%;
            text-align: center;
        }
        
        .score-circle {
            width: 100px;
            height: 100px;
        }
        
        .score-number {
            font-size: 36px;
        }
    }
    </style>
    ';
    
    $js = '
    <script>
    jQuery(document).ready(function($) {
        // Validación del formulario
        $(".audit-form").on("submit", function(e) {
            var url = $("#audit_url").val();
            if (!url) {
                e.preventDefault();
                alert("Por favor, introduce una URL para auditar.");
                return false;
            }
            
            // Mostrar indicador de carga
            $(this).find(".audit-button").html("⏳ Analizando...");
            $(this).find(".audit-button").prop("disabled", true);
        });
        
        // Animación para resultados
        $(".score-circle").each(function() {
            var score = parseInt($(this).find(".score-number").text());
            var circle = $(this);
            
            circle.css({
                "transform": "scale(0)",
                "transition": "transform 0.5s ease-out"
            });
            
            setTimeout(function() {
                circle.css("transform", "scale(1)");
            }, 100);
        });
    });
    </script>
    ';
    
    echo $css . $js;
}

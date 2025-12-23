<?php
/**
 * Plugin Name: Greenwashing Audit Toolkit – Auditor Activo
 * Description: Analiza URLs en busca de greenwashing y devuelve puntaje e incumplimientos
 * Version:     3.2
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
    // Procesar el formulario si se envió
    if (isset($_POST['start_audit']) && isset($_POST['audit_url'])) {
        $url = esc_url_raw($_POST['audit_url']);
        $depth = isset($_POST['audit_depth']) ? sanitize_text_field($_POST['audit_depth']) : 'basic';
        $result_html = ga_perform_audit($url, $depth);
    } else {
        $url = '';
        $result_html = '';
    }
    
    ob_start();
    ?>
    <div class="greenwashing-audit-tool">
        <div class="audit-form-section">
            <h3>🔍 Auditoría de Greenwashing</h3>
            <p>Analiza cualquier página web en busca de prácticas de greenwashing.</p>
            
            <form method="post" class="audit-form" action="">
                <div class="form-group">
                    <label for="audit_url">URL a auditar:</label>
                    <input type="url" 
                           id="audit_url" 
                           name="audit_url" 
                           value="<?php echo esc_url($url); ?>" 
                           placeholder="https://ejemplo.com" 
                           required
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="audit_depth">Profundidad del análisis:</label>
                    <select id="audit_depth" name="audit_depth" class="form-control">
                        <option value="basic" <?php selected(isset($_POST['audit_depth']) && $_POST['audit_depth'] == 'basic'); ?>>Básico (página principal)</option>
                        <option value="medium" <?php selected(isset($_POST['audit_depth']) && $_POST['audit_depth'] == 'medium'); ?>>Medio (+ páginas clave)</option>
                        <option value="deep" <?php selected(isset($_POST['audit_depth']) && $_POST['audit_depth'] == 'deep'); ?>>Profundo (+ enlaces internos)</option>
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
    
    // Añadir estilos y scripts
    ga_enqueue_assets();
    
    return ob_get_clean();
}

// Función principal que ejecuta la auditoría
function ga_perform_audit($url, $depth = 'basic') {
    // Verificar URL válida
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '<div class="audit-error">❌ URL inválida. Por favor, introduce una URL completa (ej: https://ejemplo.com)</div>';
    }
    
    // Añadir protocolo si falta
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'http://' . $url;
    }
    
    // Obtener contenido de la página
    $content = ga_fetch_url_content($url);
    
    if (!$content) {
        return '<div class="audit-error">❌ No se pudo acceder a la URL. Verifica que sea accesible públicamente. Error: ' . esc_html($content) . '</div>';
    }
    
    // Realizar análisis
    $analysis = ga_analyze_content($content, $url, $depth);
    
    // Generar resultados
    return ga_generate_results($analysis, $url);
}

// Obtener contenido de la URL
function ga_fetch_url_content($url) {
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'sslverify' => false // Desactivar verificación SSL para testing
    ));
    
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message();
    }
    
    $body = wp_remote_retrieve_body($response);
    return $body ?: false;
}

// Analizar contenido en busca de indicadores de greenwashing
function ga_analyze_content($content, $url, $depth = 'basic') {
    $issues = array();
    $score = 100;
    
    $lower_content = strtolower($content);
    $html_content = $content; // Guardar contenido HTML original
    
    // Extraer texto visible (sin etiquetas HTML)
    $text_content = wp_strip_all_tags($content);
    $lower_text = strtolower($text_content);
    
    // 1. Términos vagos
    $vague_terms = array(
        'eco-friendly', 'sostenible', 'green', 'ecológico', 'natural',
        'eco', 'conscious', 'responsible', 'verde', 'amigable con el medio ambiente',
        'climate neutral', 'carbon neutral', 'zero waste', 'respetuoso con el planeta',
        'sustainable', 'environmentally friendly', 'earth friendly'
    );
    
    foreach ($vague_terms as $term) {
        if (stripos($lower_text, $term) !== false) {
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
        'rainforest alliance', 'fsc', 'carbon trust', 'energy star',
        'usda organic', 'eu ecolabel', 'bluesign'
    );
    
    foreach ($certifications as $cert) {
        if (stripos($lower_text, $cert) !== false) {
            if (!ga_check_certification_details($html_content, $cert)) {
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
    $carbon_terms = array('carbon footprint', 'carbon offset', 'carbon neutral', 'net zero', 'co2', 'emissions');
    foreach ($carbon_terms as $term) {
        if (stripos($lower_text, $term) !== false) {
            if (!ga_check_metrics_present($text_content)) {
                $issues[] = array(
                    'type' => 'declaracion_carbono_sin_metricas',
                    'message' => "Declaración de carbono sin métricas específicas detectada: '$term'",
                    'severity' => 'high',
                    'penalty' => 6
                );
                $score -= 6;
            }
        }
    }
    
    // 4. Imágenes engañosas
    preg_match_all('/<img[^>]+>/i', $html_content, $images);
    $misleading_images = 0;
    foreach ($images[0] as $img) {
        if (ga_check_misleading_image($img)) {
            $misleading_images++;
        }
    }
    
    if ($misleading_images > 0) {
        $issues[] = array(
            'type' => 'imagen_engañosa',
            'message' => "Posible uso de imágenes de naturaleza/verdes sin relación directa ($misleading_images imágenes detectadas)",
            'severity' => 'low',
            'penalty' => 2
        );
        $score -= 2;
    }
    
    // 5. Falta política de sostenibilidad
    if (!ga_check_sustainability_policy($text_content, $url)) {
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
        '/\b100%\s*(natural|green|eco|sustainable|biodegradable)\b/i',
        '/\bcompletamente\s*(sostenible|ecológico|natural)\b/i',
        '/\bsin\s*impacto\s*ambiental\b/i',
        '/\bzero\s*(waste|emissions|carbon)\b/i',
        '/\btotally\s*(green|eco|natural)\b/i'
    );
    
    foreach ($greenwashing_patterns as $pattern) {
        if (preg_match($pattern, $text_content)) {
            $issues[] = array(
                'type' => 'lenguaje_greenwashing',
                'message' => 'Lenguaje absoluto o de greenwashing clásico detectado',
                'severity' => 'high',
                'penalty' => 7
            );
            $score -= 7;
            break; // Solo contar una vez
        }
    }
    
    // 7. Check for specific numbers/percentages (good sign)
    if (preg_match('/\b\d+%\b/', $text_content)) {
        // Reducir penalización si hay porcentajes específicos
        $score += 5;
    }
    
    // Asegurar score no negativo ni mayor a 100
    $score = max(0, min(100, $score));
    
    return array(
        'url' => $url,
        'score' => $score,
        'issues' => $issues,
        'total_issues' => count($issues),
        'timestamp' => current_time('mysql'),
        'content_length' => strlen($text_content),
        'images_count' => count($images[0])
    );
}

// Funciones auxiliares
function ga_check_certification_details($content, $certification) {
    // Buscar enlaces cerca de la certificación
    $pattern = '/(' . preg_quote($certification, '/') . ').{0,100}(?:<a[^>]+>|https?:\/\/|www\.)/i';
    return preg_match($pattern, $content);
}

function ga_check_metrics_present($content) {
    $metric_patterns = array(
        '/\b\d+\s*(toneladas|tons|kg|co2|%|percent|reduction)\b/i',
        '/\breduc(ido|e|ción)\s+(en\s+)?\d+/i',
        '/\b(meta|goal|target)\s+(de|of)\s+\d+/i',
        '/\b\d+\s*(less|fewer|lower|decrease)\b/i'
    );
    
    foreach ($metric_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }
    return false;
}

function ga_check_misleading_image($img_tag) {
    $green_keywords = array('nature', 'leaf', 'tree', 'green', 'forest', 'earth', 'planet', 'eco', 'environment', 'sustainable');
    foreach ($green_keywords as $keyword) {
        if (stripos($img_tag, $keyword) !== false) {
            return true;
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
        'política ambiental',
        'sustainability policy',
        'environmental report',
        'esg report',
        'impact report'
    );
    
    foreach ($policy_terms as $term) {
        if (stripos($content, $term) !== false) {
            return true;
        }
    }
    
    return false;
}

// Generar resultados HTML
function ga_generate_results($analysis, $url) {
    $score = $analysis['score'];
    $issues = $analysis['issues'];
    $total_issues = $analysis['total_issues'];
    $content_length = $analysis['content_length'];
    $images_count = $analysis['images_count'];
    
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
                    <p>Contenido analizado: <strong><?php echo number_format($content_length); ?></strong> caracteres</p>
                    <p>Imágenes encontradas: <strong><?php echo $images_count; ?></strong></p>
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
                    <li>Usa imágenes relevantes al producto/servicio</li>
                    <li>Proporciona métricas cuantificables</li>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <div class="no-issues">
            <p>✅ No se detectaron problemas significativos de greenwashing en el análisis básico.</p>
            <p><small>Nota: Para un análisis completo, considera solicitar una auditoría profunda.</small></p>
        </div>
        <?php endif; ?>
        
        <div class="audit-actions">
            <button onclick="window.print()" class="action-button">🖨️ Imprimir Reporte</button>
            <a href="https://github.com/yelmartinezseo/greenwashing-audit-toolkit/issues/new?template=auditoria-completa.yml&title=Auditoría%20para%20<?php echo urlencode($url); ?>" 
               target="_blank" class="action-button github-button">
               📊 Solicitar Auditoría Profunda en GitHub
            </a>
            <button onclick="location.reload()" class="action-button">🔄 Nueva Auditoría</button>
        </div>
        
        <div class="audit-debug">
            <p><small><strong>Nota técnica:</strong> Esta auditoría analiza el contenido HTML público de la página. Para un análisis más profundo que incluya imágenes, enlaces internos y estructura completa del sitio, utiliza la opción "Auditoría Profunda en GitHub".</small></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Añadir estilos y scripts
function ga_enqueue_assets() {
    static $enqueued = false;
    
    if ($enqueued) return;
    $enqueued = true;
    
    $css = '
    <style>
    .greenwashing-audit-tool {
        max-width: 900px;
        margin: 0 auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        padding: 20px;
    }
    
    .audit-form-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
        box-sizing: border-box;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
        transition: all 0.3s;
        display: inline-block;
        text-align: center;
        width: 100%;
        max-width: 300px;
    }
    
    .audit-button:hover {
        background: #059669;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .audit-button:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .audit-results {
        margin: 32px 0;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .score-card {
        border: 3px solid;
        border-radius: 16px;
        padding: 24px;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }
    
    .score-display {
        display: flex;
        align-items: center;
        gap: 32px;
        flex-wrap: wrap;
    }
    
    .score-circle {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        font-weight: bold;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .score-number {
        font-size: 52px;
        font-weight: bold;
        line-height: 1;
    }
    
    .score-label {
        font-size: 20px;
        opacity: 0.9;
    }
    
    .score-info {
        flex: 1;
        min-width: 300px;
    }
    
    .score-info h4 {
        margin-top: 0;
        margin-bottom: 16px;
        font-size: 24px;
    }
    
    .score-info p {
        margin: 8px 0;
        color: #4b5563;
    }
    
    .score-info strong {
        color: #111827;
    }
    
    .issues-list {
        margin-top: 32px;
    }
    
    .issues-list h4 {
        margin-bottom: 20px;
        font-size: 20px;
        color: #374151;
    }
    
    .issue-item {
        border-left: 4px solid;
        padding: 20px;
        margin-bottom: 16px;
        background: #fefce8;
        border-radius: 0 8px 8px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .severity-high { 
        border-color: #ef4444; 
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    }
    
    .severity-medium { 
        border-color: #f59e0b; 
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    }
    
    .severity-low { 
        border-color: #10b981; 
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    }
    
    .issue-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .issue-badge {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .severity-high .issue-badge { 
        background: #ef4444; 
        color: white; 
    }
    
    .severity-medium .issue-badge { 
        background: #f59e0b; 
        color: white; 
    }
    
    .severity-low .issue-badge { 
        background: #10b981; 
        color: white; 
    }
    
    .issue-penalty {
        font-weight: bold;
        color: #dc2626;
        font-size: 16px;
    }
    
    .issue-message {
        margin: 12px 0;
        font-size: 16px;
        line-height: 1.5;
        color: #374151;
    }
    
    .issue-type {
        font-size: 14px;
        color: #6b7280;
        font-style: italic;
    }
    
    .audit-actions {
        display: flex;
        gap: 16px;
        margin-top: 32px;
        flex-wrap: wrap;
    }
    
    .action-button {
        padding: 14px 24px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid #cbd5e1;
        background: white;
        color: #374151;
        transition: all 0.3s;
        font-size: 15px;
        min-height: 48px;
        flex: 1;
        min-width: 200px;
        text-align: center;
    }
    
    .action-button:hover {
        background: #f9fafb;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        text-decoration: none;
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
        padding: 24px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 12px;
        border-left: 6px solid #0ea5e9;
    }
    
    .audit-templates-section h4 {
        margin-top: 0;
        margin-bottom: 16px;
        color: #0369a1;
    }
    
    .template-link {
        color: #0284c7;
        text-decoration: none;
        font-weight: 500;
        font-size: 16px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s;
    }
    
    .template-link:hover {
        color: #0c4a6e;
        text-decoration: underline;
    }
    
    .audit-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        font-weight: 500;
    }
    
    .no-issues {
        background: #f0fdf4;
        border: 2px solid #bbf7d0;
        color: #166534;
        padding: 24px;
        border-radius: 12px;
        text-align: center;
        margin: 20px 0;
        font-size: 18px;
    }
    
    .no-issues small {
        display: block;
        margin-top: 10px;
        font-size: 14px;
        color: #047857;
    }
    
    .recommendations {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
        padding: 24px;
        border-radius: 12px;
        margin-top: 32px;
    }
    
    .recommendations h5 {
        margin-top: 0;
        margin-bottom: 16px;
        color: #0369a1;
        font-size: 18px;
    }
    
    .recommendations ul {
        margin: 0;
        padding-left: 24px;
    }
    
    .recommendations li {
        margin-bottom: 12px;
        line-height: 1.5;
        color: #374151;
    }
    
    .audit-debug {
        margin-top: 24px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
        border-left: 4px solid #6b7280;
        font-size: 14px;
        color: #6b7280;
    }
    
    @media (max-width: 768px) {
        .greenwashing-audit-tool {
            padding: 10px;
        }
        
        .score-display {
            flex-direction: column;
            text-align: center;
            gap: 24px;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }
        
        .score-number {
            font-size: 44px;
        }
        
        .audit-actions {
            flex-direction: column;
        }
        
        .action-button {
            width: 100%;
            min-width: auto;
        }
        
        .audit-form-section,
        .audit-templates-section,
        .score-card {
            padding: 20px;
        }
        
        .issue-item {
            padding: 16px;
        }
    }
    
    @media (max-width: 480px) {
        .score-circle {
            width: 100px;
            height: 100px;
        }
        
        .score-number {
            font-size: 36px;
        }
        
        .score-label {
            font-size: 16px;
        }
        
        .issue-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .issue-badge, .issue-penalty {
            align-self: flex-start;
        }
    }
    </style>
    ';
    
    $js = '
    <script>
    jQuery(document).ready(function($) {
        // Validación del formulario
        $(".audit-form").on("submit", function(e) {
            var url = $("#audit_url").val().trim();
            if (!url) {
                e.preventDefault();
                alert("Por favor, introduce una URL para auditar.");
                $("#audit_url").focus();
                return false;
            }
            
            // Validar formato de URL básico
            var urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
            if (!urlPattern.test(url)) {
                e.preventDefault();
                alert("Por favor, introduce una URL válida (ej: ejemplo.com o https://ejemplo.com)");
                $("#audit_url").focus();
                return false;
            }
            
            // Mostrar indicador de carga
            var button = $(this).find(".audit-button");
            var originalText = button.html();
            button.html("⏳ Analizando...");
            button.prop("disabled", true);
            
            // Scroll suave a resultados
            setTimeout(function() {
                $("html, body").animate({
                    scrollTop: $(".audit-results-section").offset().top - 100
                }, 800);
            }, 500);
            
            // Restaurar botón después de 30 segundos (por si falla)
            setTimeout(function() {
                button.html(originalText);
                button.prop("disabled", false);
            }, 30000);
        });
        
        // Animación para resultados
        $(".score-circle").each(function() {
            var circle = $(this);
            circle.css({
                "transform": "scale(0)",
                "transition": "transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55)"
            });
            
            setTimeout(function() {
                circle.css("transform", "scale(1)");
            }, 300);
        });
        
        // Animación para issues
        $(".issue-item").each(function(index) {
            var item = $(this);
            item.css({
                "opacity": "0",
                "transform": "translateX(-20px)",
                "transition": "all 0.5s ease " + (index * 0.1) + "s"
            });
            
            setTimeout(function() {
                item.css({
                    "opacity": "1",
                    "transform": "translateX(0)"
                });
            }, 500 + (index * 100));
        });
        
        // Mejorar experiencia en móviles
        if ($(window).width() < 768) {
            $("#audit_url").attr("autocomplete", "off");
        }
    });
    </script>
    ';
    
    echo $css . $js;
}

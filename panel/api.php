<?php
session_name('mbv_panel');
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// --- Auth gate ---
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// --- CSRF check for mutating actions ---
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$mutating = ['producto_save', 'producto_delete', 'media_upload'];
if (in_array($action, $mutating)) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

// --- Helper: call WP REST API ---
function wp_api(string $method, string $path, array $data = [], ?string $file_path = null): array {
    $url = WP_BASE_URL . '/wp-json' . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method === 'POST' && $file_path) {
        $filename = basename($file_path);
        $mime = mime_content_type($file_path) ?: 'image/webp';
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . WP_AUTH_HEADER,
            'Content-Disposition: attachment; filename="' . $filename . '"',
            'Content-Type: ' . $mime,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_path));
    } elseif ($method === 'POST' || $method === 'PUT') {
        $body = json_encode($data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . WP_AUTH_HEADER,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
        ]);
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . WP_AUTH_HEADER,
        ]);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . WP_AUTH_HEADER,
        ]);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return ['code' => $http_code, 'body' => $decoded ?? ['raw' => $response]];
}

// --- Route actions ---
switch ($action) {

    case 'productos_list':
        $lang = $_GET['lang'] ?? 'es';
        $category = $_GET['category'] ?? '';
        $path = '/wp/v2/producto?lang=' . urlencode($lang) . '&per_page=100&_fields=id,title,acf,categoria_de_producto&acf_format=standard';
        if ($category) $path .= '&categoria_de_producto=' . urlencode($category);
        $result = wp_api('GET', $path);
        echo json_encode($result['body']);
        break;

    case 'producto_get':
        $es_id = intval($_GET['es_id'] ?? 0);
        $en_id = intval($_GET['en_id'] ?? 0);
        $es = wp_api('GET', '/wp/v2/producto/' . $es_id . '?acf_format=standard');
        $en = $en_id ? wp_api('GET', '/wp/v2/producto/' . $en_id . '?acf_format=standard') : ['body' => null];
        echo json_encode(['es' => $es['body'], 'en' => $en['body']]);
        break;

    case 'categories_list':
        $result = wp_api('GET', '/wp/v2/categoria_de_producto?per_page=50&_fields=id,name,slug,lang');
        echo json_encode($result['body']);
        break;

    case 'producto_save':
        $body = json_decode(file_get_contents('php://input'), true);
        $es_id = intval($body['es_id'] ?? 0);
        $en_id = intval($body['en_id'] ?? 0);

        $es_data = [
            'title'   => $body['nombre_es'] ?? '',
            'content' => $body['desc_es'] ?? '',
            'status'  => 'publish',
            'lang'    => 'es',
            'acf'     => [
                'descripcion'    => $body['desc_es'] ?? '',
                'medidas'        => $body['medidas'] ?? '',
                'badge'          => $body['badge'] ?? '',
                'destacado'      => !empty($body['destacado']),
                'foto_principal' => intval($body['foto_principal_id'] ?? 0) ?: null,
                'foto_hover'     => intval($body['foto_hover_id'] ?? 0) ?: null,
                'fotos_extra'    => $body['fotos_extra_ids'] ?? [],
            ],
        ];
        if (!empty($body['categoria_es_id'])) {
            $es_data['categoria_de_producto'] = [intval($body['categoria_es_id'])];
        }

        $en_data = [
            'title'   => $body['nombre_en'] ?? '',
            'content' => $body['desc_en'] ?? '',
            'status'  => 'publish',
            'lang'    => 'en',
            'acf'     => [
                'descripcion'    => $body['desc_en'] ?? '',
                'medidas'        => $body['medidas'] ?? '',
                'badge'          => $body['badge'] ?? '',
                'destacado'      => !empty($body['destacado']),
                'foto_principal' => intval($body['foto_principal_id'] ?? 0) ?: null,
                'foto_hover'     => intval($body['foto_hover_id'] ?? 0) ?: null,
                'fotos_extra'    => $body['fotos_extra_ids'] ?? [],
            ],
        ];
        if (!empty($body['categoria_en_id'])) {
            $en_data['categoria_de_producto'] = [intval($body['categoria_en_id'])];
        }

        $es_result = $es_id
            ? wp_api('POST', '/wp/v2/producto/' . $es_id, $es_data)
            : wp_api('POST', '/wp/v2/producto', $es_data);

        $en_result = $en_id
            ? wp_api('POST', '/wp/v2/producto/' . $en_id, $en_data)
            : wp_api('POST', '/wp/v2/producto', $en_data);

        echo json_encode(['ok' => true, 'es' => $es_result['body'], 'en' => $en_result['body']]);
        break;

    case 'producto_delete':
        $body = json_decode(file_get_contents('php://input'), true);
        $es_id = intval($body['es_id'] ?? 0);
        $en_id = intval($body['en_id'] ?? 0);
        $es_result = $es_id ? wp_api('DELETE', '/wp/v2/producto/' . $es_id . '?force=true') : ['body' => null];
        $en_result = $en_id ? wp_api('DELETE', '/wp/v2/producto/' . $en_id . '?force=true') : ['body' => null];
        echo json_encode(['ok' => true, 'es' => $es_result['body'], 'en' => $en_result['body']]);
        break;

    case 'media_upload':
        if (!isset($_FILES['file'])) {
            echo json_encode(['error' => 'No file received']);
            break;
        }
        $tmp  = $_FILES['file']['tmp_name'];
        $name = basename($_FILES['file']['name']);
        $result = wp_api('POST', '/wp/v2/media', [], $tmp);
        echo json_encode(['ok' => true, 'media' => $result['body']]);
        break;

    case 'dashboard_stats':
        $all = wp_api('GET', '/wp/v2/producto?lang=es&per_page=100&_fields=id,acf&acf_format=standard');
        $products   = is_array($all['body']) ? $all['body'] : [];
        $total      = count($products);
        $no_medidas = 0;
        foreach ($products as $p) {
            if (empty($p['acf']['medidas'])) $no_medidas++;
        }
        echo json_encode([
            'total_productos'     => $total,
            'sin_medidas'         => $no_medidas,
            'ultima_actualizacion' => date('d/m/Y H:i'),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
}

<?php
// ══════════════════════════════════════════════
//  HELPER FIRESTORE REST
// ══════════════════════════════════════════════

/**
 * Hace GET a la API REST de Firestore y devuelve el JSON decodificado.
 */
function firestore_get(string $url): array {
    $urlConKey = $url . (str_contains($url, '?') ? '&' : '?') . 'key=' . FIREBASE_API_KEY;

    $ch = curl_init($urlConKey);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) {
        http_response_code(502);
        echo json_encode(['error' => 'Error de red: ' . $err]);
        exit;
    }
    if ($code !== 200) {
        http_response_code($code);
        echo json_encode(['error' => 'Firestore devolvió ' . $code, 'detalle' => json_decode($body, true)]);
        exit;
    }

    return json_decode($body, true) ?? [];
}

/**
 * Convierte un documento Firestore (formato fields) a array PHP plano.
 */
function firestore_doc_to_array(array $doc): array {
    $id     = basename($doc['name'] ?? '');
    $fields = $doc['fields'] ?? [];
    $out    = ['id' => $id];

    foreach ($fields as $key => $val) {
        if (isset($val['stringValue']))    $out[$key] = $val['stringValue'];
        elseif (isset($val['integerValue'])) $out[$key] = (int)$val['integerValue'];
        elseif (isset($val['doubleValue']))  $out[$key] = (float)$val['doubleValue'];
        elseif (isset($val['booleanValue'])) $out[$key] = (bool)$val['booleanValue'];
        elseif (isset($val['timestampValue'])) $out[$key] = $val['timestampValue'];
        elseif (isset($val['nullValue']))   $out[$key] = null;
        else $out[$key] = $val; // tipo no mapeado
    }
    return $out;
}

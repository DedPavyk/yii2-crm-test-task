<?php
// Простой тест чтения данных без Yii2
header('Content-Type: application/json; charset=utf-8');

$dataFile = __DIR__ . '/../data/crm_data.json';

if (!file_exists($dataFile)) {
    echo json_encode(['error' => 'Файл данных не найден: ' . $dataFile]);
    exit;
}

$content = file_get_contents($dataFile);
if ($content === false) {
    echo json_encode(['error' => 'Не удалось прочитать файл']);
    exit;
}

$data = json_decode($content, true);
if ($data === null) {
    echo json_encode(['error' => 'Ошибка декодирования JSON: ' . json_last_error_msg()]);
    exit;
}

// Проверяем структуру
if (!isset($data['contacts']) || !isset($data['deals'])) {
    echo json_encode(['error' => 'Неверная структура данных']);
    exit;
}

// Возвращаем данные
echo json_encode([
    'success' => true,
    'contacts_count' => count($data['contacts']),
    'deals_count' => count($data['deals']),
    'contacts' => $data['contacts'],
    'deals' => $data['deals']
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

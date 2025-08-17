<?php
// Тестовый скрипт для проверки прав доступа к файлу
$dataFile = __DIR__ . '/../data/crm_data.json';

echo "Проверка прав доступа к файлу: $dataFile\n";
echo "Файл существует: " . (file_exists($dataFile) ? 'Да' : 'Нет') . "\n";
echo "Файл доступен для чтения: " . (is_readable($dataFile) ? 'Да' : 'Нет') . "\n";
echo "Файл доступен для записи: " . (is_writable($dataFile) ? 'Да' : 'Нет') . "\n";
echo "Папка доступна для записи: " . (is_writable(dirname($dataFile)) ? 'Да' : 'Нет') . "\n";

// Попытка записи тестовых данных
$testData = ['test' => 'data', 'timestamp' => time()];
$result = file_put_contents($dataFile, json_encode($testData, JSON_PRETTY_PRINT));

if ($result !== false) {
    echo "Тестовая запись успешна: $result байт\n";
    
    // Восстанавливаем оригинальные данные
    $originalData = [
        'deals' => [
            ['id' => 1, 'name' => 'Хотят люстру', 'amount' => 15000, 'contacts' => [15, 25]],
            ['id' => 14, 'name' => 'Хотят светильник', 'amount' => 8000, 'contacts' => [15]],
            ['id' => 2, 'name' => 'Пока думают', 'amount' => 4000, 'contacts' => [15, 25]],
        ],
        'contacts' => [
            ['id' => 15, 'firstName' => 'Иван', 'lastName' => 'Петров', 'deals' => [1, 14, 2]],
            ['id' => 25, 'firstName' => 'Наталья', 'lastName' => 'Сидорова', 'deals' => [1, 2]],
            ['id' => 30, 'firstName' => 'Василий', 'lastName' => 'Иванов', 'deals' => []],
        ]
    ];
    
    file_put_contents($dataFile, json_encode($originalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Оригинальные данные восстановлены\n";
} else {
    echo "Ошибка записи: " . error_get_last()['message'] . "\n";
}
?>

<?php
echo "<h1>Восстановление данных CRM</h1>";

// Путь к файлу данных
$dataFile = __DIR__ . '/../data/crm_data.json';
echo "<p>Файл данных: $dataFile</p>";

if (file_exists($dataFile)) {
    echo "<p>Файл существует</p>";
    echo "<p>Права на чтение: " . (is_readable($dataFile) ? 'Да' : 'Нет') . "</p>";
    echo "<p>Права на запись: " . (is_writable($dataFile) ? 'Да' : 'Нет') . "</p>";
} else {
    echo "<p>Файл не существует</p>";
}

// Восстанавливаем правильную структуру данных
$correctData = [
    'deals' => [
        ['id' => 1, 'name' => 'Хотят люстру', 'amount' => 15000, 'contacts' => [15, 25]],
        ['id' => 14, 'name' => 'Хотят светильник', 'amount' => 8000, 'contacts' => [15]],
        ['id' => 2, 'name' => 'Пока думают', 'amount' => 4000, 'contacts' => [15, 25]],
        ['id' => 32, 'name' => 'Ничего', 'amount' => 12, 'contacts' => []],
        ['id' => 34, 'name' => 'Чего', 'amount' => 12333, 'contacts' => []]
    ],
    'contacts' => [
        ['id' => 15, 'firstName' => 'Иван', 'lastName' => 'Петров', 'deals' => [1, 14, 2]],
        ['id' => 25, 'firstName' => 'Наталья', 'lastName' => 'Сидорова', 'deals' => [1, 2]],
        ['id' => 30, 'firstName' => 'Василий', 'lastName' => 'Иванов', 'deals' => []]
    ]
];

echo "<h2>Восстанавливаем правильную структуру данных:</h2>";
echo "<pre>" . json_encode($correctData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Записываем исправленные данные
$result = file_put_contents($dataFile, json_encode($correctData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result !== false) {
    echo "<p style='color: green;'>Данные успешно восстановлены!</p>";
    
    // Проверяем, что записалось
    $savedData = json_decode(file_get_contents($dataFile), true);
    echo "<h2>Проверка сохраненных данных:</h2>";
    echo "<pre>" . json_encode($savedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    // Проверяем связи
    echo "<h2>Проверка связей:</h2>";
    foreach ($savedData['contacts'] as $contact) {
        echo "<p><strong>{$contact['firstName']} {$contact['lastName']}</strong> (ID: {$contact['id']}) связан со сделками: ";
        if (!empty($contact['deals'])) {
            $dealNames = [];
            foreach ($contact['deals'] as $dealId) {
                foreach ($savedData['deals'] as $deal) {
                    if ($deal['id'] == $dealId) {
                        $dealNames[] = "{$deal['id']}: {$deal['name']}";
                        break;
                    }
                }
            }
            echo implode(', ', $dealNames);
        } else {
            echo "нет связанных сделок";
        }
        echo "</p>";
    }
    
    echo "<h3>Сделки:</h3>";
    foreach ($savedData['deals'] as $deal) {
        echo "<p><strong>{$deal['name']}</strong> (ID: {$deal['id']}) связана с контактами: ";
        if (!empty($deal['contacts'])) {
            $contactNames = [];
            foreach ($deal['contacts'] as $contactId) {
                foreach ($savedData['contacts'] as $contact) {
                    if ($contact['id'] == $contactId) {
                        $contactNames[] = "{$contact['id']}: {$contact['firstName']} {$contact['lastName']}";
                        break;
                    }
                }
            }
            echo implode(', ', $contactNames);
        } else {
            echo "нет связанных контактов";
        }
        echo "</p>";
    }
    
} else {
    echo "<p style='color: red;'>Ошибка при записи данных!</p>";
    $error = error_get_last();
    if ($error) {
        echo "<p>Ошибка: " . $error['message'] . "</p>";
    }
}

echo "<p><a href='check-data.html'>Перейти к проверке данных</a></p>";
echo "<p><a href='../'>Вернуться на главную</a></p>";
?>

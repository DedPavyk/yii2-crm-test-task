<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\Json;
use Yii;

class Deal extends Model
{
    public $id;
    public $name;
    public $amount;
    public $contacts = [];

    private static $dataFile = '@app/data/crm_data.json';

    /**
     * Правила валидации
     */
    public function rules()
    {
        return [
            [['name'], 'required', 'message' => 'Наименование обязательно для заполнения'],
            [['name'], 'string', 'max' => 255],
            [['amount'], 'integer', 'min' => 0],
            [['id'], 'integer'],
            [['contacts'], 'safe'],
        ];
    }

    /**
     * Подписи атрибутов
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID сделки',
            'name' => 'Наименование',
            'amount' => 'Сумма',
            'contacts' => 'Контакты',
        ];
    }

    /**
     * Получить все сделки
     */
    public static function findAll()
    {
        $data = self::loadData();
        $deals = [];
        
        foreach ($data['deals'] as $dealData) {
            $deal = new self();
            $deal->id = $dealData['id'];
            $deal->name = $dealData['name'];
            $deal->amount = $dealData['amount'];
            // Убеждаемся, что contacts - это массив
            $deal->contacts = is_array($dealData['contacts']) ? $dealData['contacts'] : [];
            $deals[] = $deal;
        }
        
        return $deals;
    }

    /**
     * Найти сделку по ID
     */
    public static function findOne($id)
    {
        $data = self::loadData();
        
        foreach ($data['deals'] as $dealData) {
            if ($dealData['id'] == $id) {
                $deal = new self();
                $deal->id = $dealData['id'];
                $deal->name = $dealData['name'];
                $deal->amount = $dealData['amount'];
                // Убеждаемся, что contacts - это массив
                $deal->contacts = is_array($dealData['contacts']) ? $dealData['contacts'] : [];
                return $deal;
            }
        }
        
        return null;
    }

    /**
     * Сохранить сделку
     */
    public function save()
    {
        Yii::info('Начало сохранения сделки: ' . json_encode($this->attributes), 'deal/save');
        
        if (!$this->validate()) {
            Yii::error('Ошибки валидации: ' . json_encode($this->errors), 'deal/save');
            return false;
        }

        try {
            // Убеждаемся, что contacts - это массив
            if (!is_array($this->contacts)) {
                if (is_string($this->contacts) && !empty($this->contacts)) {
                    // Пытаемся разобрать JSON
                    $decoded = json_decode($this->contacts, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $this->contacts = $decoded;
                    } else {
                        $this->contacts = [$this->contacts];
                    }
                } else {
                    $this->contacts = [];
                }
            }
            
            // Убираем дубликаты и пустые значения
            $this->contacts = array_filter(array_unique($this->contacts), function($value) {
                return !empty($value) && is_numeric($value);
            });
            
            $data = self::loadData();
            Yii::info('Данные загружены, сделок: ' . count($data['deals']), 'deal/save');
            
            if ($this->id) {
                // Обновление существующей
                Yii::info('Обновление существующей сделки с ID: ' . $this->id, 'deal/save');
                foreach ($data['deals'] as &$deal) {
                    if ($deal['id'] == $this->id) {
                        $oldContacts = is_array($deal['contacts']) ? $deal['contacts'] : [];
                        $deal['name'] = $this->name;
                        $deal['amount'] = $this->amount;
                        $deal['contacts'] = $this->contacts;
                        
                        // Обновляем связи в контактах
                        $this->updateContactReferences($data, $this->id, $oldContacts, $this->contacts);
                        break;
                    }
                }
            } else {
                // Создание новой
                $this->id = $this->getNextId($data);
                Yii::info('Создание новой сделки с ID: ' . $this->id, 'deal/save');
                $data['deals'][] = [
                    'id' => $this->id,
                    'name' => $this->name,
                    'amount' => $this->amount,
                    'contacts' => $this->contacts,
                ];
                
                // Добавляем связи в контактах
                foreach ($this->contacts as $contactId) {
                    foreach ($data['contacts'] as &$contact) {
                        if ($contact['id'] == $contactId && !in_array($this->id, $contact['deals'])) {
                            $contact['deals'][] = $this->id;
                        }
                    }
                }
            }
            
            $result = self::saveData($data);
            Yii::info('Результат сохранения: ' . ($result ? 'успешно' : 'ошибка'), 'deal/save');
            return $result;
            
        } catch (\Exception $e) {
            Yii::error('Исключение при сохранении сделки: ' . $e->getMessage() . ' в файле ' . $e->getFile() . ' на строке ' . $e->getLine(), 'deal/save');
            return false;
        }
    }

    /**
     * Удалить сделку
     */
    public function delete()
    {
        $data = self::loadData();
        
        // Убеждаемся, что contacts - это массив
        $contacts = is_array($this->contacts) ? $this->contacts : [];
        
        // Удаляем связи из контактов
        foreach ($contacts as $contactId) {
            foreach ($data['contacts'] as &$contact) {
                if ($contact['id'] == $contactId) {
                    if (is_array($contact['deals'])) {
                        $contact['deals'] = array_values(array_filter($contact['deals'], 
                            function($dealId) { return $dealId != $this->id; }));
                    } else {
                        $contact['deals'] = [];
                    }
                }
            }
        }
        
        // Удаляем саму сделку
        $data['deals'] = array_values(array_filter($data['deals'], 
            function($deal) { return $deal['id'] != $this->id; }));
        
        return self::saveData($data);
    }

    /**
     * Получить связанные контакты
     */
    public function getRelatedContacts()
    {
        $data = self::loadData();
        $relatedContacts = [];
        
        // Убеждаемся, что contacts - это массив
        $contacts = is_array($this->contacts) ? $this->contacts : [];
        
        foreach ($contacts as $contactId) {
            foreach ($data['contacts'] as $contactData) {
                if ($contactData['id'] == $contactId) {
                    $relatedContacts[] = $contactData;
                    break;
                }
            }
        }
        
        return $relatedContacts;
    }

    /**
     * Обновить ссылки на контакты
     */
    private function updateContactReferences(&$data, $dealId, $oldContacts, $newContacts)
    {
        // Убеждаемся, что это массивы
        $oldContacts = is_array($oldContacts) ? $oldContacts : [];
        $newContacts = is_array($newContacts) ? $newContacts : [];
        
        $toRemove = array_diff($oldContacts, $newContacts);
        $toAdd = array_diff($newContacts, $oldContacts);
        
        // Удаляем старые связи
        foreach ($toRemove as $contactId) {
            foreach ($data['contacts'] as &$contact) {
                if ($contact['id'] == $contactId) {
                    if (is_array($contact['deals'])) {
                        $contact['deals'] = array_values(array_filter($contact['deals'], 
                            function($id) use ($dealId) { return $id != $dealId; }));
                    } else {
                        $contact['deals'] = [];
                    }
                }
            }
        }
        
        // Добавляем новые связи
        foreach ($toAdd as $contactId) {
            foreach ($data['contacts'] as &$contact) {
                if ($contact['id'] == $contactId) {
                    if (!is_array($contact['deals'])) {
                        $contact['deals'] = [];
                    }
                    if (!in_array($dealId, $contact['deals'])) {
                        $contact['deals'][] = $dealId;
                    }
                }
            }
        }
    }

    /**
     * Загрузить данные из файла
     */
    public static function loadData()
    {
        $file = Yii::getAlias('@app/data/crm_data.json');
        Yii::info('Загрузка данных из файла: ' . $file, 'deal/loadData');
        
        if (!file_exists($file)) {
            Yii::info('Файл данных не существует, создаю по умолчанию', 'deal/loadData');
            $defaultData = [
                'deals' => [],
                'contacts' => []
            ];
            
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            
            $result = file_put_contents($file, Json::encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            Yii::info('Создание файла по умолчанию: ' . ($result !== false ? 'успешно' : 'ошибка'), 'deal/loadData');
            return $defaultData;
        }
        
        $content = file_get_contents($file);
        $data = Json::decode($content);
        Yii::info('Данные загружены из файла, контактов: ' . count($data['contacts']) . ', сделок: ' . count($data['deals']), 'deal/loadData');
        return $data;
    }

    /**
     * Сохранить данные в файл
     */
    public static function saveData($data)
    {
        $file = Yii::getAlias('@app/data/crm_data.json');
        Yii::info('Сохранение данных в файл: ' . $file, 'deal/saveData');
        
        $result = file_put_contents($file, Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success = $result !== false;
        Yii::info('Результат сохранения в файл: ' . ($success ? 'успешно' : 'ошибка'), 'deal/saveData');
        return $success;
    }

    /**
     * Получить следующий ID
     */
    private function getNextId($data)
    {
        $maxId = 0;
        
        foreach ($data['contacts'] as $contact) {
            if ($contact['id'] > $maxId) {
                $maxId = $contact['id'];
            }
        }
        
        foreach ($data['deals'] as $deal) {
            if ($deal['id'] > $maxId) {
                $maxId = $deal['id'];
            }
        }
        
        return $maxId + 1;
    }
}
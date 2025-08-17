<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\Json;
use Yii;

class Contact extends Model
{
    public $id;
    public $firstName;
    public $lastName;
    public $deals = [];

    private static $dataFile = '@app/data/crm_data.json';

    /**
     * Правила валидации
     */
    public function rules()
    {
        return [
            [['firstName'], 'required', 'message' => 'Имя обязательно для заполнения'],
            [['firstName', 'lastName'], 'string', 'max' => 255],
            [['id'], 'integer'],
            [['deals'], 'safe'],
        ];
    }

    /**
     * Подписи атрибутов
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID контакта',
            'firstName' => 'Имя',
            'lastName' => 'Фамилия',
            'deals' => 'Сделки',
        ];
    }

    /**
     * Получить все контакты
     */
    public static function findAll()
    {
        $data = self::loadData();
        $contacts = [];
        
        foreach ($data['contacts'] as $contactData) {
            $contact = new self();
            $contact->id = $contactData['id'];
            $contact->firstName = $contactData['firstName'];
            $contact->lastName = $contactData['lastName'];
            // Убеждаемся, что deals - это массив
            $contact->deals = is_array($contactData['deals']) ? $contactData['deals'] : [];
            $contacts[] = $contact;
        }
        
        return $contacts;
    }

    /**
     * Найти контакт по ID
     */
    public static function findOne($id)
    {
        $data = self::loadData();
        
        foreach ($data['contacts'] as $contactData) {
            if ($contactData['id'] == $id) {
                $contact = new self();
                $contact->id = $contactData['id'];
                $contact->firstName = $contactData['firstName'];
                $contact->lastName = $contactData['lastName'];
                // Убеждаемся, что deals - это массив
                $contact->deals = is_array($contactData['deals']) ? $contactData['deals'] : [];
                return $contact;
            }
        }
        
        return null;
    }

    /**
     * Сохранить контакт
     */
    public function save()
    {
        Yii::info('Начало сохранения контакта: ' . json_encode($this->attributes), 'contact/save');
        
        if (!$this->validate()) {
            Yii::error('Ошибки валидации: ' . json_encode($this->errors), 'contact/save');
            return false;
        }

        try {
            // Убеждаемся, что deals - это массив
            if (!is_array($this->deals)) {
                if (is_string($this->deals) && !empty($this->deals)) {
                    // Пытаемся разобрать JSON
                    $decoded = json_decode($this->deals, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $this->deals = $decoded;
                    } else {
                        $this->deals = [$this->deals];
                    }
                } else {
                    $this->deals = [];
                }
            }
            
            // Убираем дубликаты и пустые значения
            $this->deals = array_filter(array_unique($this->deals), function($value) {
                return !empty($value) && is_numeric($value);
            });
            
            $data = self::loadData();
            Yii::info('Данные загружены, контактов: ' . count($data['contacts']), 'contact/save');
            
            if ($this->id) {
                // Обновление существующего
                Yii::info('Обновление существующего контакта с ID: ' . $this->id, 'contact/save');
                foreach ($data['contacts'] as &$contact) {
                    if ($contact['id'] == $this->id) {
                        $oldDeals = is_array($contact['deals']) ? $contact['deals'] : [];
                        $contact['firstName'] = $this->firstName;
                        $contact['lastName'] = $this->lastName;
                        $contact['deals'] = $this->deals;
                        
                        // Обновляем связи в сделках
                        $this->updateDealReferences($data, $this->id, $oldDeals, $this->deals);
                        break;
                    }
                }
            } else {
                // Создание нового
                $this->id = $this->getNextId($data);
                Yii::info('Создание нового контакта с ID: ' . $this->id, 'contact/save');
                $data['contacts'][] = [
                    'id' => $this->id,
                    'firstName' => $this->firstName,
                    'lastName' => $this->lastName,
                    'deals' => $this->deals,
                ];
                
                // Добавляем связи в сделках
                foreach ($this->deals as $dealId) {
                    foreach ($data['deals'] as &$deal) {
                        if ($deal['id'] == $dealId && !in_array($this->id, $deal['contacts'])) {
                            $deal['contacts'][] = $this->id;
                        }
                    }
                }
            }
            
            $result = self::saveData($data);
            Yii::info('Результат сохранения: ' . ($result ? 'успешно' : 'ошибка'), 'contact/save');
            return $result;
            
        } catch (\Exception $e) {
            Yii::error('Исключение при сохранении контакта: ' . $e->getMessage() . ' в файле ' . $e->getFile() . ' на строке ' . $e->getLine(), 'contact/save');
            return false;
        }
    }

    /**
     * Удалить контакт
     */
    public function delete()
    {
        $data = self::loadData();
        
        // Убеждаемся, что deals - это массив
        $deals = is_array($this->deals) ? $this->deals : [];
        
        // Удаляем связи из сделок
        foreach ($deals as $dealId) {
            foreach ($data['deals'] as &$deal) {
                if ($deal['id'] == $dealId) {
                    if (is_array($deal['contacts'])) {
                        $deal['contacts'] = array_values(array_filter($deal['contacts'], 
                            function($contactId) { return $contactId != $this->id; }));
                    } else {
                        $deal['contacts'] = [];
                    }
                }
            }
        }
        
        // Удаляем сам контакт
        $data['contacts'] = array_values(array_filter($data['contacts'], 
            function($contact) { return $contact['id'] != $this->id; }));
        
        return self::saveData($data);
    }

    /**
     * Получить полное имя
     */
    public function getFullName()
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * Получить связанные сделки
     */
    public function getRelatedDeals()
    {
        $data = self::loadData();
        $relatedDeals = [];
        
        // Убеждаемся, что deals - это массив
        $deals = is_array($this->deals) ? $this->deals : [];
        
        foreach ($deals as $dealId) {
            foreach ($data['deals'] as $dealData) {
                if ($dealData['id'] == $dealId) {
                    $relatedDeals[] = $dealData;
                    break;
                }
            }
        }
        
        return $relatedDeals;
    }

    /**
     * Обновить ссылки на сделки
     */
    private function updateDealReferences(&$data, $contactId, $oldDeals, $newDeals)
    {
        // Убеждаемся, что это массивы
        $oldDeals = is_array($oldDeals) ? $oldDeals : [];
        $newDeals = is_array($newDeals) ? $newDeals : [];
        
        $toRemove = array_diff($oldDeals, $newDeals);
        $toAdd = array_diff($newDeals, $oldDeals);
        
        // Удаляем старые связи
        foreach ($toRemove as $dealId) {
            foreach ($data['deals'] as &$deal) {
                if ($deal['id'] == $dealId) {
                    if (is_array($deal['contacts'])) {
                        $deal['contacts'] = array_values(array_filter($deal['contacts'], 
                            function($id) use ($contactId) { return $id != $contactId; }));
                    } else {
                        $deal['contacts'] = [];
                    }
                }
            }
        }
        
        // Добавляем новые связи
        foreach ($toAdd as $dealId) {
            foreach ($data['deals'] as &$deal) {
                if ($deal['id'] == $dealId) {
                    if (!is_array($deal['contacts'])) {
                        $deal['contacts'] = [];
                    }
                    if (!in_array($contactId, $deal['contacts'])) {
                        $deal['contacts'][] = $contactId;
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
        $file = Yii::getAlias(self::$dataFile);
        Yii::info('Загрузка данных из файла: ' . $file, 'contact/loadData');
        
        if (!file_exists($file)) {
            Yii::info('Файл данных не существует, создаю по умолчанию', 'contact/loadData');
            $defaultData = [
                'deals' => [
                    ['id' => 1, 'name' => 'Хотят люстру', 'amount' => 15000, 'contacts' => [15]],
                ],
                'contacts' => [
                    ['id' => 15, 'firstName' => 'Иван', 'lastName' => 'Петров', 'deals' => [1]],
                ]
            ];
            
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            
            $result = file_put_contents($file, Json::encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            Yii::info('Создание файла по умолчанию: ' . ($result !== false ? 'успешно' : 'ошибка'), 'contact/loadData');
            return $defaultData;
        }
        
        $content = file_get_contents($file);
        $data = Json::decode($content);
        Yii::info('Данные загружены из файла, контактов: ' . count($data['contacts']) . ', сделок: ' . count($data['deals']), 'contact/loadData');
        return $data;
    }

    /**
     * Сохранить данные в файл
     */
    public static function saveData($data)
    {
        $file = Yii::getAlias(self::$dataFile);
        Yii::info('Сохранение данных в файл: ' . $file, 'contact/saveData');
        
        $result = file_put_contents($file, Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success = $result !== false;
        Yii::info('Результат сохранения в файл: ' . ($success ? 'успешно' : 'ошибка'), 'contact/saveData');
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
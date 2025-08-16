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
            $contact->deals = $contactData['deals'];
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
                $contact->deals = $contactData['deals'];
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
        if (!$this->validate()) {
            return false;
        }

        $data = self::loadData();
        
        if ($this->id) {
            // Обновление существующего
            foreach ($data['contacts'] as &$contact) {
                if ($contact['id'] == $this->id) {
                    $oldDeals = $contact['deals'];
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
        
        return self::saveData($data);
    }

    /**
     * Удалить контакт
     */
    public function delete()
    {
        $data = self::loadData();
        
        // Удаляем связи из сделок
        foreach ($this->deals as $dealId) {
            foreach ($data['deals'] as &$deal) {
                if ($deal['id'] == $dealId) {
                    $deal['contacts'] = array_values(array_filter($deal['contacts'], 
                        function($contactId) { return $contactId != $this->id; }));
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
        
        foreach ($this->deals as $dealId) {
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
        $toRemove = array_diff($oldDeals, $newDeals);
        $toAdd = array_diff($newDeals, $oldDeals);
        
        // Удаляем старые связи
        foreach ($toRemove as $dealId) {
            foreach ($data['deals'] as &$deal) {
                if ($deal['id'] == $dealId) {
                    $deal['contacts'] = array_values(array_filter($deal['contacts'], 
                        function($id) use ($contactId) { return $id != $contactId; }));
                }
            }
        }
        
        // Добавляем новые связи
        foreach ($toAdd as $dealId) {
            foreach ($data['deals'] as &$deal) {
                if ($deal['id'] == $dealId && !in_array($contactId, $deal['contacts'])) {
                    $deal['contacts'][] = $contactId;
                }
            }
        }
    }

    /**
     * Загрузить данные из файла
     */
    private static function loadData()
    {
        $file = Yii::getAlias(self::$dataFile);
        
        if (!file_exists($file)) {
            $defaultData = [
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
            
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            
            file_put_contents($file, Json::encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $defaultData;
        }
        
        return Json::decode(file_get_contents($file));
    }

    /**
     * Сохранить данные в файл
     */
    private static function saveData($data)
    {
        $file = Yii::getAlias(self::$dataFile);
        return file_put_contents($file, Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
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
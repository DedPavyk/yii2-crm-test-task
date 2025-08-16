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
            $deal->contacts = $dealData['contacts'];
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
                $deal->contacts = $dealData['contacts'];
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
        if (!$this->validate()) {
            return false;
        }

        $data = self::loadData();
        
        if ($this->id) {
            // Обновление существующей
            foreach ($data['deals'] as &$deal) {
                if ($deal['id'] == $this->id) {
                    $oldContacts = $deal['contacts'];
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
        
        return self::saveData($data);
    }

    /**
     * Удалить сделку
     */
    public function delete()
    {
        $data = self::loadData();
        
        // Удаляем связи из контактов
        foreach ($this->contacts as $contactId) {
            foreach ($data['contacts'] as &$contact) {
                if ($contact['id'] == $contactId) {
                    $contact['deals'] = array_values(array_filter($contact['deals'], 
                        function($dealId) { return $dealId != $this->id; }));
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
        
        foreach ($this->contacts as $contactId) {
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
        $toRemove = array_diff($oldContacts, $newContacts);
        $toAdd = array_diff($newContacts, $oldContacts);
        
        // Удаляем старые связи
        foreach ($toRemove as $contactId) {
            foreach ($data['contacts'] as &$contact) {
                if ($contact['id'] == $contactId) {
                    $contact['deals'] = array_values(array_filter($contact['deals'], 
                        function($id) use ($dealId) { return $id != $dealId; }));
                }
            }
        }
        
        // Добавляем новые связи
        foreach ($toAdd as $contactId) {
            foreach ($data['contacts'] as &$contact) {
                if ($contact['id'] == $contactId && !in_array($dealId, $contact['deals'])) {
                    $contact['deals'][] = $dealId;
                }
            }
        }
    }

    /**
     * Загрузить данные из файла
     */
    private static function loadData()
    {
        return Contact::loadData(); // Используем тот же метод
    }

    /**
     * Сохранить данные в файл
     */
    private static function saveData($data)
    {
        return Contact::saveData($data); // Используем тот же метод
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
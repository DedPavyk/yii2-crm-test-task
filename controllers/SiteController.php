<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\Contact;
use app\models\Deal;

class SiteController extends Controller
{
    /**
     * Настройки поведения
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'save' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Отключить CSRF для API методов
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['save', 'delete', 'get-list', 'get-details', 'get-all-contacts', 'get-all-deals'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Главная страница CRM
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * API для получения списка элементов
     */
    public function actionGetList($type)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if ($type === 'contacts') {
            $contacts = Contact::findAll();
            $result = [];
            foreach ($contacts as $contact) {
                $result[] = [
                    'id' => $contact->id,
                    'name' => $contact->getFullName(),
                ];
            }
            return $result;
        } elseif ($type === 'deals') {
            $deals = Deal::findAll();
            $result = [];
            foreach ($deals as $deal) {
                $result[] = [
                    'id' => $deal->id,
                    'name' => $deal->name,
                ];
            }
            return $result;
        }
        
        return [];
    }

    /**
     * API для получения деталей элемента
     */
    public function actionGetDetails($type, $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if ($type === 'contacts') {
            $contact = Contact::findOne($id);
            if ($contact) {
                $relatedDeals = $contact->getRelatedDeals();
                return [
                    'id' => $contact->id,
                    'firstName' => $contact->firstName,
                    'lastName' => $contact->lastName,
                    'relatedDeals' => $relatedDeals,
                ];
            }
        } elseif ($type === 'deals') {
            $deal = Deal::findOne($id);
            if ($deal) {
                $relatedContacts = $deal->getRelatedContacts();
                return [
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'amount' => $deal->amount,
                    'relatedContacts' => $relatedContacts,
                ];
            }
        }
        
        return null;
    }

    /**
     * API для сохранения элемента
     */
    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            // Получаем данные из POST или JSON
            $data = Yii::$app->request->post();
            if (empty($data)) {
                $rawData = Yii::$app->request->getRawBody();
                if ($rawData) {
                    $data = json_decode($rawData, true);
                }
            }
            
            Yii::info('Полученные данные: ' . json_encode($data), 'site/save');
            
            if (empty($data)) {
                Yii::error('Пустые данные POST', 'site/save');
                return ['success' => false, 'error' => 'Пустые данные'];
            }
            
            $type = $data['type'] ?? null;
            if (!$type) {
                Yii::error('Тип не указан', 'site/save');
                return ['success' => false, 'error' => 'Тип не указан'];
            }
            
            if ($type === 'contacts') {
                $contact = isset($data['id']) ? Contact::findOne($data['id']) : new Contact();
                if (!$contact) {
                    $contact = new Contact();
                }
                
                $contact->firstName = $data['firstName'] ?? '';
                $contact->lastName = $data['lastName'] ?? '';
                
                // Правильно обрабатываем deals - может быть массивом или строкой
                $deals = $data['deals'] ?? [];
                if (is_string($deals)) {
                    if (empty($deals)) {
                        $deals = [];
                    } else {
                        // Пытаемся разобрать JSON или создать массив из строки
                        $decoded = json_decode($deals, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $deals = $decoded;
                        } else {
                            // Если это не JSON, создаем массив из строки
                            $deals = [$deals];
                        }
                    }
                }
                $contact->deals = $deals;
                
                Yii::info('Сохранение контакта: ' . json_encode($contact->attributes), 'site/save');
                
                if ($contact->save()) {
                    return ['success' => true, 'id' => $contact->id];
                } else {
                    Yii::error('Ошибки валидации контакта: ' . json_encode($contact->errors), 'site/save');
                    return ['success' => false, 'errors' => $contact->errors];
                }
            } elseif ($type === 'deals') {
                $deal = isset($data['id']) ? Deal::findOne($data['id']) : new Deal();
                if (!$deal) {
                    $deal = new Deal();
                }
                
                $deal->name = $data['name'] ?? '';
                $deal->amount = intval($data['amount'] ?? 0);
                
                // Правильно обрабатываем contacts - может быть массивом или строкой
                $contacts = $data['contacts'] ?? [];
                if (is_string($contacts)) {
                    if (empty($contacts)) {
                        $contacts = [];
                    } else {
                        // Пытаемся разобрать JSON или создать массив из строки
                        $decoded = json_decode($contacts, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $contacts = $decoded;
                        } else {
                            // Если это не JSON, создаем массив из строки
                            $contacts = [$contacts];
                        }
                    }
                }
                $deal->contacts = $contacts;
                
                Yii::info('Сохранение сделки: ' . json_encode($deal->attributes), 'site/save');
                
                if ($deal->save()) {
                    return ['success' => true, 'id' => $deal->id];
                } else {
                    Yii::error('Ошибки валидации сделки: ' . json_encode($deal->errors), 'site/save');
                    return ['success' => false, 'errors' => $deal->errors];
                }
            }
            
            return ['success' => false, 'error' => 'Неизвестный тип'];
            
        } catch (\Exception $e) {
            Yii::error('Исключение в actionSave: ' . $e->getMessage() . ' в файле ' . $e->getFile() . ' на строке ' . $e->getLine(), 'site/save');
            return ['success' => false, 'error' => 'Внутренняя ошибка сервера: ' . $e->getMessage()];
        }
    }

    /**
     * API для удаления элемента
     */
    public function actionDelete($type, $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if ($type === 'contacts') {
            $contact = Contact::findOne($id);
            if ($contact && $contact->delete()) {
                return ['success' => true];
            }
        } elseif ($type === 'deals') {
            $deal = Deal::findOne($id);
            if ($deal && $deal->delete()) {
                return ['success' => true];
            }
        }
        
        return ['success' => false];
    }

    /**
     * API для получения всех контактов для выбора
     */
    public function actionGetAllContacts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $contacts = Contact::findAll();
        $result = [];
        foreach ($contacts as $contact) {
            $result[] = [
                'id' => $contact->id,
                'name' => $contact->getFullName(),
            ];
        }
        return $result;
    }

    /**
     * API для получения всех сделок для выбора
     */
    public function actionGetAllDeals()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $deals = Deal::findAll();
            Yii::info('Получено сделок: ' . count($deals), 'site/getAllDeals');
            
            $result = [];
            foreach ($deals as $deal) {
                $result[] = [
                    'id' => $deal->id,
                    'name' => $deal->name,
                ];
            }
            return $result;
            
        } catch (\Exception $e) {
            Yii::error('Исключение в actionGetAllDeals: ' . $e->getMessage() . ' в файле ' . $e->getFile() . ' на строке ' . $e->getLine(), 'site/getAllDeals');
            return ['error' => 'Внутренняя ошибка сервера: ' . $e->getMessage()];
        }
    }

    /**
     * API для исправления данных
     */
    public function actionFixData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $data = Contact::loadData();
            $fixed = false;
            
            // Исправляем контакты
            foreach ($data['contacts'] as &$contact) {
                if (!is_array($contact['deals'])) {
                    if (is_string($contact['deals']) && !empty($contact['deals'])) {
                        $decoded = json_decode($contact['deals'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $contact['deals'] = $decoded;
                        } else {
                            $contact['deals'] = [];
                        }
                    } else {
                        $contact['deals'] = [];
                    }
                    $fixed = true;
                }
            }
            
            // Исправляем сделки
            foreach ($data['deals'] as &$deal) {
                if (!is_array($deal['contacts'])) {
                    if (is_string($deal['contacts']) && !empty($deal['contacts'])) {
                        $decoded = json_decode($deal['contacts'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $deal['contacts'] = $decoded;
                        } else {
                            $deal['contacts'] = [];
                        }
                    } else {
                        $deal['contacts'] = [];
                    }
                    $fixed = true;
                }
            }
            
            if ($fixed) {
                Contact::saveData($data);
                return ['success' => true, 'message' => 'Данные исправлены'];
            } else {
                return ['success' => true, 'message' => 'Данные уже корректны'];
            }
            
        } catch (\Exception $e) {
            Yii::error('Ошибка при исправлении данных: ' . $e->getMessage(), 'site/fixData');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
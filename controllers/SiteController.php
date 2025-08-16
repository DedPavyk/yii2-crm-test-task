<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Contact;
use app\models\Deal;

class SiteController extends Controller
{
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
        
        $data = Yii::$app->request->post();
        $type = $data['type'];
        
        if ($type === 'contacts') {
            $contact = isset($data['id']) ? Contact::findOne($data['id']) : new Contact();
            if (!$contact) {
                $contact = new Contact();
            }
            
            $contact->firstName = $data['firstName'];
            $contact->lastName = $data['lastName'] ?? '';
            $contact->deals = $data['deals'] ?? [];
            
            if ($contact->save()) {
                return ['success' => true, 'id' => $contact->id];
            } else {
                return ['success' => false, 'errors' => $contact->errors];
            }
        } elseif ($type === 'deals') {
            $deal = isset($data['id']) ? Deal::findOne($data['id']) : new Deal();
            if (!$deal) {
                $deal = new Deal();
            }
            
            $deal->name = $data['name'];
            $deal->amount = intval($data['amount'] ?? 0);
            $deal->contacts = $data['contacts'] ?? [];
            
            if ($deal->save()) {
                return ['success' => true, 'id' => $deal->id];
            } else {
                return ['success' => false, 'errors' => $deal->errors];
            }
        }
        
        return ['success' => false];
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
}
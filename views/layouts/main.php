<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        /* Основные стили */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f8f9fa;
        }
        
        /* Основной контейнер */
        .container { 
            display: flex; 
            height: 100vh; 
            border: 1px solid #ddd;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* Меню */
        .menu { 
            width: 200px; 
            background-color: #f5f5f5; 
            border-right: 1px solid #ddd;
            border-bottom: 2px solid #4a76a8;
        }
        
        .menu-item { 
            padding: 15px 20px; 
            cursor: pointer; 
            border-bottom: 1px solid #eee; 
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .menu-item:hover:not(.active) { 
            background-color: #fff9c4; /* Светло-желтый при наведении */
        }
        
        .menu-item.active { 
            background-color: #ffeb3b !important; /* Ярко-желтый для активного */
            color: #000 !important;
            border-left-color: #ffc107 !important;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Список */
        .list { 
            width: 300px; 
            background: white; 
            border-right: 1px solid #ddd; 
            overflow-y: auto; 
        }
        
        .list-header { 
            padding: 12px 15px; 
            background-color: #f5f5f5; 
            border-bottom: 2px solid #4a76a8; 
            font-weight: 600;
            text-align: left;
        }
        
        .list-item { 
            padding: 10px 15px; 
            margin-bottom: 5px; 
            cursor: pointer; 
            border-radius: 4px; 
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            margin: 0 10px 5px 10px;
        }
        
        .list-item:hover:not(.active) { 
            background-color: #fff9c4; /* Светло-желтый при наведении */
        }
        
        .list-item.active { 
            background-color: #ffeb3b !important; /* Ярко-желтый для активного */
            color: #000 !important;
            border-left-color: #ffc107 !important;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .add-btn { 
            margin: 15px; 
            padding: 12px 20px; 
            background: #4a76a8; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            width: calc(100% - 30px);
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .add-btn:hover { 
            background: #3a5f85; 
        }
        
        /* Контент */
        .content { 
            flex: 1; 
            padding: 15px; 
            background: white; 
            overflow-y: auto; 
        }
        
        .content-header { 
            color: #2c3e50;
            margin-top: 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        /* Таблица деталей */
        .detail-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .detail-table td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #eee; 
            vertical-align: top;
            line-height: 1.5;
        }
        
        .detail-table td:first-child { 
            font-weight: 600; 
            width: 180px; 
            color: #2c3e50;
            background-color: #f9f9f9;
        }
        
        .detail-table td:first-child:after {
            content: ":";
            margin-left: 5px;
        }
        
        /* Кнопки действий */
        .action-buttons { 
            margin-top: 25px; 
        }
        
        .btn { 
            padding: 10px 18px; 
            margin-right: 12px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-edit { 
            background: #4a76a8; 
            color: white; 
        }
        
        .btn-edit:hover {
            background: #3a5f85;
        }
        
        .btn-delete { 
            background: #dc3545; 
            color: white; 
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        /* Модальное окно */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
        }
        
        .modal-content { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            width: 550px; 
            max-width: 90vw;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .modal-header { 
            font-size: 22px; 
            margin-bottom: 25px; 
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        /* Формы */
        .form-group { 
            margin-bottom: 18px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 600; 
            color: #2c3e50;
        }
        
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #4a76a8;
            box-shadow: 0 0 0 2px rgba(74, 118, 168, 0.1);
        }
        
        .form-group.required label:after { 
            content: " *"; 
            color: #dc3545; 
        }
        
        .checkbox-list { 
            max-height: 180px; 
            overflow-y: auto; 
            border: 1px solid #ddd; 
            padding: 15px; 
            border-radius: 6px;
            background-color: #fafafa;
        }
        
        .checkbox-item { 
            margin-bottom: 10px; 
            display: flex;
            align-items: center;
        }
        
        .checkbox-item input { 
            width: auto; 
            margin-right: 10px;
            transform: scale(1.1);
        }
        
        .checkbox-item label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        /* Кнопки модального окна */
        .modal-buttons { 
            text-align: right; 
            margin-top: 25px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .btn-save { 
            background: #4a76a8; 
            color: white; 
        }
        
        .btn-save:hover {
            background: #3a5f85;
        }
        
        .btn-cancel { 
            background: #6c757d; 
            color: white; 
            margin-left: 10px; 
        }
        
        .btn-cancel:hover {
            background: #545b62;
        }
        
        /* Пустое состояние */
        .empty-state { 
            text-align: center; 
            color: #6c757d; 
            font-style: italic; 
            margin-top: 60px;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                height: auto;
            }
            
            .menu, .list {
                width: 100%;
                height: auto;
                max-height: 200px;
            }
            
            .content {
                height: auto;
                min-height: 400px;
            }
            
            .modal-content {
                width: 95vw;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?= $content ?>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
<?php

$this->title = 'CRM Система';
?>

<div class="container">
    <div class="menu">
        <div class="menu-item active" data-type="deals">Сделки</div>
        <div class="menu-item" data-type="contacts">Контакты</div>
    </div>
    
    <div class="list">
        <div class="list-header">Список</div>
        <button class="add-btn" onclick="showAddModal()">Добавить</button>
        <div id="list-items"></div>
    </div>
    
    <div class="content">
        <div class="content-header">Содержимое</div>
        <div id="content-details">
            <div class="empty-state">Выберите элемент из списка</div>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления/редактирования -->
<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="modal-title">Добавить элемент</div>
        <form id="item-form">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>" />
            <div id="form-fields"></div>
            <div class="modal-buttons">
                <button type="submit" class="btn btn-save">Сохранить</button>
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Отмена</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentType = 'deals';
let currentId = null;
let editingId = null;

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    loadList();
    
    // Обработчики меню
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            currentType = this.dataset.type;
            currentId = null;
            loadList();
            showEmptyState();
        });
    });
    
    // Обработка формы
    document.getElementById('item-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveItem();
    });
});

// Загрузка списка элементов
function loadList() {
    fetch(`<?= \yii\helpers\Url::to(['site/get-list']) ?>?type=${currentType}`)
        .then(response => response.json())
        .then(data => {
            const listItems = document.getElementById('list-items');
            listItems.innerHTML = '';
            
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'list-item';
                div.dataset.id = item.id;
                div.textContent = item.name;
                
                div.addEventListener('click', function() {
                    document.querySelectorAll('.list-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    currentId = parseInt(this.dataset.id);
                    loadDetails();
                });
                
                listItems.appendChild(div);
            });
        })
        .catch(error => console.error('Ошибка загрузки списка:', error));
}

// Загрузка деталей элемента
function loadDetails() {
    fetch(`<?= \yii\helpers\Url::to(['site/get-details']) ?>?type=${currentType}&id=${currentId}`)
        .then(response => response.json())
        .then(data => {
            if (!data) {
                showEmptyState();
                return;
            }
            
            const contentDetails = document.getElementById('content-details');
            let html = '<table class="detail-table">';
            
            if (currentType === 'deals') {
                html += `
                    <tr><td>id сделки</td><td>${data.id}</td></tr>
                    <tr><td>Наименование</td><td>${data.name}</td></tr>
                    <tr><td>Сумма</td><td>${data.amount.toLocaleString()} ₽</td></tr>
                `;
                
                if (data.relatedContacts && data.relatedContacts.length > 0) {
                    data.relatedContacts.forEach(contact => {
                        html += `<tr><td>id контакта: ${contact.id}</td><td>${contact.firstName} ${contact.lastName}</td></tr>`;
                    });
                }
            } else {
                html += `
                    <tr><td>id контакта</td><td>${data.id}</td></tr>
                    <tr><td>Имя</td><td>${data.firstName}</td></tr>
                    <tr><td>Фамилия</td><td>${data.lastName}</td></tr>
                `;
                
                if (data.relatedDeals && data.relatedDeals.length > 0) {
                    data.relatedDeals.forEach(deal => {
                        html += `<tr><td>id сделки: ${deal.id}</td><td>${deal.name}</td></tr>`;
                    });
                }
            }
            
            html += '</table>';
            html += `
                <div class="action-buttons">
                    <button class="btn btn-edit" onclick="showEditModal(${data.id})">Редактировать</button>
                    <button class="btn btn-delete" onclick="deleteItem(${data.id})">Удалить</button>
                </div>
            `;
            
            contentDetails.innerHTML = html;
        })
        .catch(error => console.error('Ошибка загрузки деталей:', error));
}

// Отображение пустого состояния
function showEmptyState() {
    const contentDetails = document.getElementById('content-details');
    contentDetails.innerHTML = '<div class="empty-state">Выберите элемент из списка</div>';
}

// Модальные окна
function showAddModal() {
    editingId = null;
    document.getElementById('modal-title').textContent = 
        currentType === 'deals' ? 'Добавить сделку' : 'Добавить контакт';
    loadModalForm();
}

function showEditModal(id) {
    editingId = id;
    document.getElementById('modal-title').textContent = 
        currentType === 'deals' ? 'Редактировать сделку' : 'Редактировать контакт';
    loadModalForm();
}

function loadModalForm() {
    const formFields = document.getElementById('form-fields');
    let html = '';
    
    if (currentType === 'deals') {
        html += `
            <div class="form-group required">
                <label>Наименование:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label>Сумма:</label>
                <input type="number" id="amount" name="amount">
            </div>
            <div class="form-group">
                <label>Контакты:</label>
                <div class="checkbox-list" id="contacts-list">
                    <div>Загрузка...</div>
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="form-group required">
                <label>Имя:</label>
                <input type="text" id="firstName" name="firstName" required>
            </div>
            <div class="form-group">
                <label>Фамилия:</label>
                <input type="text" id="lastName" name="lastName">
            </div>
            <div class="form-group">
                <label>Сделки:</label>
                <div class="checkbox-list" id="deals-list">
                    <div>Загрузка...</div>
                </div>
            </div>
        `;
    }
    
    formFields.innerHTML = html;
    
    // Загружаем данные для чекбоксов
    if (currentType === 'deals') {
        loadContactsForForm();
    } else {
        loadDealsForForm();
    }
    
    // Заполняем данные если редактируем
    if (editingId) {
        loadItemDataForEdit();
    }
    
    document.getElementById('modal').style.display = 'block';
}

function loadContactsForForm() {
    fetch(`<?= \yii\helpers\Url::to(['site/get-all-contacts']) ?>`)
        .then(response => response.json())
        .then(contacts => {
            const contactsList = document.getElementById('contacts-list');
            let html = '';
            
            contacts.forEach(contact => {
                html += `
                    <div class="checkbox-item">
                        <input type="checkbox" id="contact-${contact.id}" value="${contact.id}">
                        <label for="contact-${contact.id}">${contact.name}</label>
                    </div>
                `;
            });
            
            contactsList.innerHTML = html;
        })
        .catch(error => console.error('Ошибка загрузки контактов:', error));
}

function loadDealsForForm() {
    fetch(`<?= \yii\helpers\Url::to(['site/get-all-deals']) ?>`)
        .then(response => response.json())
        .then(deals => {
            const dealsList = document.getElementById('deals-list');
            let html = '';
            
            deals.forEach(deal => {
                html += `
                    <div class="checkbox-item">
                        <input type="checkbox" id="deal-${deal.id}" value="${deal.id}">
                        <label for="deal-${deal.id}">${deal.name}</label>
                    </div>
                `;
            });
            
            dealsList.innerHTML = html;
        })
        .catch(error => console.error('Ошибка загрузки сделок:', error));
}

function loadItemDataForEdit() {
    fetch(`<?= \yii\helpers\Url::to(['site/get-details']) ?>?type=${currentType}&id=${editingId}`)
        .then(response => response.json())
        .then(data => {
            if (currentType === 'deals') {
                document.getElementById('name').value = data.name || '';
                document.getElementById('amount').value = data.amount || '';
                
                // Отмечаем выбранные контакты
                setTimeout(() => {
                    if (data.relatedContacts) {
                        data.relatedContacts.forEach(contact => {
                            const checkbox = document.getElementById(`contact-${contact.id}`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                }, 100);
            } else {
                document.getElementById('firstName').value = data.firstName || '';
                document.getElementById('lastName').value = data.lastName || '';
                
                // Отмечаем выбранные сделки
                setTimeout(() => {
                    if (data.relatedDeals) {
                        data.relatedDeals.forEach(deal => {
                            const checkbox = document.getElementById(`deal-${deal.id}`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                }, 100);
            }
        })
        .catch(error => console.error('Ошибка загрузки данных для редактирования:', error));
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

// Сохранение элемента
function saveItem() {
    const formData = new FormData();
    formData.append('type', currentType);
    
    if (editingId) {
        formData.append('id', editingId);
    }
    
    if (currentType === 'deals') {
        formData.append('name', document.getElementById('name').value);
        formData.append('amount', document.getElementById('amount').value);
        
        const selectedContacts = [];
        document.querySelectorAll('#contacts-list input[type="checkbox"]:checked').forEach(cb => {
            selectedContacts.push(parseInt(cb.value));
        });
        formData.append('contacts', JSON.stringify(selectedContacts));
    } else {
        formData.append('firstName', document.getElementById('firstName').value);
        formData.append('lastName', document.getElementById('lastName').value);
        
        const selectedDeals = [];
        document.querySelectorAll('#deals-list input[type="checkbox"]:checked').forEach(cb => {
            selectedDeals.push(parseInt(cb.value));
        });
        formData.append('deals', JSON.stringify(selectedDeals));
    }
    
    // Добавляем CSRF токен
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');
    
    fetch(`<?= \yii\helpers\Url::to(['site/save']) ?>`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            loadList();
            if (editingId) {
                currentId = editingId;
                loadDetails();
            }
        } else {
            let errorMessage = 'Ошибка сохранения';
            if (data.errors) {
                errorMessage = Object.values(data.errors).flat().join('\n');
            }
            alert(errorMessage);
        }
    })
    .catch(error => {
        console.error('Ошибка сохранения:', error);
        alert('Ошибка сохранения');
    });
}

// Удаление элемента
function deleteItem(id) {
    if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
        return;
    }
    
    fetch(`<?= \yii\helpers\Url::to(['site/delete']) ?>?type=${currentType}&id=${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadList();
            showEmptyState();
            currentId = null;
        } else {
            alert('Ошибка удаления');
        }
    })
    .catch(error => {
        console.error('Ошибка удаления:', error);
        alert('Ошибка удаления');
    });
}

// Закрытие модального окна по клику вне его
window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('modal')) {
        closeModal();
    }
});
</script>
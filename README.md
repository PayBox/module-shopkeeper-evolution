# module-shopkeeper-evolution
# MODX Shopkeeper 1.3

#### Тестировалось и писалось для MODX 1.0.14 Shopkeeper 1.3.5.

1. Регистрируемся на <a href="https://paybox.money" target="_blank">paybox.money</a>
2. Скопировать файлы сниппета в папку assets/snippets/payment/.
3. В системе управления перейти "Элементы" -> "Управление элементами" -> "Сниппеты". Нажать ссылку "Новый сниппет".
4. Название сниппета: Paybox,
Описание: Оплата через Paybox,
Открыть файл payment_snippet.txt, скопировать содержимое и вставить в поле "Код сниппета". Нажать кнопку "Сохранить".
5. В системе управления открыть для редактирования страницу, которая открывается после оформления заказа (&gotoid в eForm).
Вставить в поле "Содержимое ресурса" вызов сниппета:
[!Paybox!].
Сохранить.
   Также вызов можно вставить в шаблон страницы.
6. Проверьте чтобы на странице оформления заказа в вызове сниппета eForm был указан параметр &gotoid.
Пример:
```
[!eForm? &gotoid=`15` &formid=`shopOrderForm` &tpl=`shopOrderForm` &report=`shopOrderReport` &subject=`Новый заказ`
&eFormOnBeforeMailSent=`populateOrderData` &eFormOnMailSent=`sendOrderToManager`!]
```
где 15 - это ID страницы, которая будет открываться после отправки заказа.

В шаблоне формы shopOrderForm должен быть выпадающий список (select) для выбора метода оплаты. Пример:
```
<select name="payment" >
   <option value="При получении">При получении</option>
   <option value="webmoney">WebMoney</option>
   <option value="robokassa">Другие электронные деньги</option>
</select>
```
Добавьте строку `<option value="paybox">Paybox</option>`.
Должно выглядеть, например, так:
```
 <select name="payment" >
   <option value="При получении">При получении</option>
   <option value="paybox">Paybox</option>
   <option value="webmoney">WebMoney</option>
   <option value="robokassa">Другие электронные деньги</option>
</select>
```
Теперь после отправки заказа на следующей странице будет появляться кнопка "Оплатить сейчас".

Удачных платежей.
6. Измените настройки модуля оплаты Paybox (файл `/assets/snippets/payment/config/paybox.php`):

`PL_MERCHANT_ID` – Номер магазина в paybox.money

`PL_SECRET_KEY` - Секретный ключ в paybox.money

`PL_LIFETIME` – Время жизни счета для ПС, не поддерживающих проверку счета. 0 - не учитывается. Указывается в минутах

`PL_CURRENCY_CODE` - код валюты (\'RUR\')

`PL_TEST_MODE` – 0. Тестовый режим для проверки взаимодействия.

`PL_SUCCESS_URL` - http://имя_вашего_сайта/index.php?id=ID_страницы. ID_страницы - страница с сообщением об успешной оплате

`PL_FAIL_URL` - http://имя_вашего_сайта/index.php?id=ID_страницы. ID_страницы - страница с сообщением об отмене оплаты

`PL_DOMAIN_URL` - домен вашего сайта \*

\* Чтобы не принимать оплату по конкретной транзакции нужно поменять статус заказа на отменен или удалить заказ.

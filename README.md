# Bitrix-Entity-Helper
Небольшой, удобный хелпер для работы с сущностями Битрикса.<br />
Хелпер умеет работать с данными в Инфоблоках, Highload-блоках и системных таблицах.

## Установка
1. Установить через composer:
```sh
composer require fibit/bitrix-entity-helper
```

2. Добавить подключение автозагрузчика:
```php
require_once $_SERVER["DOCUMENT_ROOT"] . "/path/to/vendor/autoload.php";
```

## Методы
| тип  | метод | параметры | назначение |
| --- | --- | --- | --- |
| static | [getClass](#user-content-1-метод-getclass) | `entity` | инициализация класса указанной сущности |
| static | [getQuery](#user-content-2-метод-getquery) | `entity` `params` `alias` | формирование sql-запроса без исполнения в БД |
| static | [getRows](#user-content-3-метод-getrows) | `entity` `params` | получение списка элементов <sup>fetchAll</sup> |
| static | [getRow](#user-content-4-метод-getrow) | `entity` `params` | получение одного элемента <sup>fetch</sup> |
| static | [addRow](#user-content-5-метод-addrow) | `entity` `fields` | добавление элемента |
| static | [updRow](#user-content-6-метод-updrow) | `entity` `id` `fields` | обновление элемента |
| static | [delRow](#user-content-7-метод-delrow) | `entity` `id` | удаление элемента |

> [!IMPORTANT]
> `entity` может иметь один из трех типов значений:
> 1. **int** - для обычных инфоблоков
> 2. **string** - для highload-блоков
> 3. **object** - для системных сущностей

## Примеры

### 1. Метод `getClass`
```php
use \Fibit\EntityHelper as EH;

EH::getClass(1);
// Результат: \Bitrix\Iblock\Elements\ElementOffersTable

EH::getClass("Data");
// Результат: \DataTable

EH::getClass(new \Bitrix\Main\UserTable);
// Результат: \Bitrix\Main\UserTable
```

### 2. Метод `getQuery`
```php
use \Fibit\EntityHelper as EH;

EH::getQuery(
  new \Bitrix\Main\UserTable,
  array(
    "select" => array("SHORT_NAME", "EMAIL"),
    "filter" => array("=ACTIVE" => "Y"),
    "order" => array("LAST_ACTIVITY_DATE" => "DESC"),
    "limit" => 100,
  )
);
```
Результат:
```sql
SELECT 
  CONCAT(`main_user`.`LAST_NAME`, ' ', UPPER(SUBSTR(`main_user`.`NAME`, 1, 1)), '.') AS `SHORT_NAME`,
  `main_user`.`EMAIL` AS `EMAIL`
FROM `b_user` `main_user` 
WHERE `main_user`.`ACTIVE` = 'Y'
ORDER BY `main_user`.`LAST_ACTIVITY_DATE` DESC
LIMIT 0, 100
```

### 3. Метод `getRows`
```php
use \Fibit\EntityHelper as EH;

EH::getRows(
  new \Bitrix\Main\UserTable,
  array(
    "select" => array("SHORT_NAME", "EMAIL"),
    "filter" => array("=ACTIVE" => "Y"),
    "order" => array("LAST_ACTIVITY_DATE" => "DESC"),
    "limit" => 100,
  )
);
```
Результат:
```php
Array(
  [result] => Array(
    [0] => Array(
      [SHORT_NAME] => Иванов И.
      [EMAIL] => ivanov@example.com
    )
    [1] => Array(
      [SHORT_NAME] => Петров П.
      [EMAIL] => petrov@example.com
    )
    ...
    [99] => Array(
      [SHORT_NAME] => Сидоров С.
      [EMAIL] => sidorov@example.com
    )
  )

  [count] => 100
  [nav] => 
  [time] => 0.010181903839111
)
```

### 4. Метод `getRow`
```php
use \Fibit\EntityHelper as EH;

EH::getRow(
  new \Bitrix\Main\UserTable,
  array(
    "select" => array("SHORT_NAME", "EMAIL"),
  )
);
```
Результат:
```php
Array(
  [result] => Array(
    [SHORT_NAME] => Иванов И.
    [EMAIL] => ivanov@example.com
  )

  [time] => 0.0097489356994629
)
```

### 5. Метод `addRow`
```php
use \Fibit\EntityHelper as EH;

EH::addRow(
  "Data",
  array(
    "UF_NAME" => "Row 1",
    "UF_XML_ID" => "row-1",
  )
);
```
Результат:
```php
Array(
  [result] => 1 // ID элемента
  [time] => 0.005182203839221
)
```

### 6. Метод `updRow`
```php
use \Fibit\EntityHelper as EH;

EH::updRow(
  "Data",
  1, // ID обновляемого элемента
  array(
    "UF_NAME" => "Row 2",
  )
);
```
Результат:
```php
Array(
  [result] => 1 // ID элемента
  [time] => 0.004221103835001
)
```

### 7. Метод `delRow`
```php
use \Fibit\EntityHelper as EH;

EH::delRow(
  "Data",
  1, // ID удаляемого элемента
);
```
Результат:
```php
Array(
  [result] => true
  [time] => 0.001111100000001
)
```

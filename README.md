# Bitrix-Entity-Helper
Удобный хелпер для работы с сущностями Битрикса.<br />
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
Метод поддерживает вызовы [ExpressionField](https://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/expressionfield/__construct.php) и [ReferenceField](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=011735).
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
Метод поддерживает вызовы [ExpressionField](https://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/expressionfield/__construct.php) и [ReferenceField](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=011735).
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

#### 3.1. Постраничная навигация
Если вы установите параметр `count_total => true` в методе, то результат выполнения запроса вернет объект `nav`. Этот объект можно будет использовать в компоненте `bitrix:main.pagenavigation` для отображения постраничной навигации. По умолчанию будет загружаться 20 записей, но вы можете изменить это, указав другое значение для `limit` при вызове метода.
```php
use \Fibit\EntityHelper as EH;

$data = EH::getRows(
  new \Bitrix\Main\UserTable,
  array(
    "count_total" => true,
    ...
    "limit" => 20
  )
);
```
```php
<?$APPLICATION->IncludeComponent(
  "bitrix:main.pagenavigation",
  "",
  array(
    "NAV_OBJECT" => $data["nav"],
    "SEF_MODE" => "N"
  ),
  true
);?>
```
> [!IMPORTANT]
> Определение текущей страницы в навигационной цепочке происходит на основании данных из URL, поэтому в случае использования формы, например для фильтрации, следует отправлять её методом GET.

#### 3.2. Пример работы с ExpressionField

Например, у нас есть highload-инфоблок "Operations" со структурой:
| ID  | UF_DATETIME | UF_MEMBER | UF_SUM |
| --- | --- | --- | --- |
| 1 | 01.01.2024 10:00:00 | Иванов И. | 100 |
| 2 | 01.01.2024 10:20:00 | Иванов И. | 100 |
| 3 | 01.01.2024 12:50:00 | Петров П. | 100 |

И нам нужно получить сумму `UF_SUM` по каждому `UF_MEMBER` за период `с 01.01.24 00:00 по 01.01.24 23:59`:
```php
use \Fibit\EntityHelper as EH;

EH::getRows(
  "Operations",
  array(
    "select" => array("UF_MEMBER", "EF_SUM"),
    "filter" => array(
      "><UF_DATETIME" => array(
        "01.01.2024 00:00:00",
        "01.01.2024 23:59:59"
      )
    ),
    "runtime" => array(
      new \Bitrix\Main\Entity\ExpressionField(
        "EF_SUM",
        "SUM(%s)",
        "UF_SUM"
      )
    )
  )
);
```
Результат:
```php
Array(
  [result] => Array(
    [0] => Array(
      [UF_MEMBER] => Иванов И.
      [EF_SUM] => 200
    )
    [1] => Array(
      [UF_MEMBER] => Петров П.
      [EF_SUM] => 100
    )
  )
  [count] => 2
  [nav] => 
  [time] => 0.020122900039128
)
```

### 4. Метод `getRow`
Метод поддерживает вызовы [ExpressionField](https://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/expressionfield/__construct.php) и [ReferenceField](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=011735).
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

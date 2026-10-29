# Junior PHP/Laravel — теория и учебные заметки

Накопительный конспект занятий 22–24 октября 2026. Это не шпаргалка: цель — через неделю восстановить ход мысли, понять причину результата и связать её с Laravel. Примеры рассчитаны на PHP 8+.

## День 1 — 22 октября: типы, значения и условия

### Нестрогое и строгое сравнение

Оператор == сравнивает значения после возможного приведения типов. Оператор === сравнивает и значение, и тип. В прикладном коде базовый выбор — строгое сравнение: так условие не зависит от скрытой «помощи» PHP.

~~~php
var_dump(0 == false);   // true: PHP приводит значения
var_dump(0 === false);  // false: int и bool различаются
var_dump('15' + 5);     // int(20): строка участвует в арифметике как число
var_dump('15' . 5);     // string(3) "155": точка склеивает строки
~~~

Такое автоматическое преобразование называют coercion. Оно удобно в коротком скрипте, но опасно на границе данных: 0 из формы, false из бизнес-логики и пустая строка — разные состояния. В Laravel входящие данные часто строки, поэтому валидация и явные приведения надёжнее, чем ==.

### isset, empty, ?? и ?: отвечают на разные вопросы

| Конструкция | Вопрос | Нюанс |
|---|---|---|
| isset($x) | Переменная/ключ существует и не null? | Для null — false. |
| empty($x) | Значение «пустое»? | Пусты: null, false, 0, '0', '', [], отсутствующий ключ. |
| $x ?? $default | Отсутствует ли значение или равно ли null? | Сохраняет false, 0, ''. |
| $x ?: $default | Truthy ли значение? | Заменит и false, и 0, и пустую строку. |

~~~php
$data = ['name' => '', 'confirmed' => false, 'age' => 0, 'middle' => null];

echo $data['name'] ?? 'Unknown'; // ''
var_dump($data['confirmed'] ?? true); // false
echo $data['age'] ?: 18; // 18
var_dump(isset($data['middle'])); // false
var_dump(array_key_exists('middle', $data)); // true
~~~

В Laravel input(..., default) похож по намерению на получение значения с default, но правила фреймворка нельзя подменять empty() на глаз.

Самопроверка: при $x = 0 что вернут $x ?? 10 и $x ?: 10?  
Ответ: 0 и 10; у ?? ноль не null, у ?: он ложный.

### Ссылки и объекты

Скаляр и массив передаются по значению (PHP оптимизирует это через copy-on-write). Амперсанд создаёт ссылку — второе имя той же переменной.

~~~php
function increase(int &$value): void { $value += 5; }
$total = 15;
increase($total);
echo $total; // 20
~~~

Про объекты точнее говорить не «передаются по ссылке», а «переменные содержат идентификатор одного объекта». Изменение свойства видно всем, кто смотрит на него; переназначение параметра на новый объект переменную вызывающего кода не заменяет.

~~~php
function rename(User $user): void { $user->name = 'Ivan'; }
function replace(User $user): void { $user = new User('Petr'); }
~~~

В Laravel изменение свойства Eloquent-модели меняет этот экземпляр в памяти, но save() — отдельная запись в БД.

## День 2 — 23 октября: массивы, копирование и функции

### Массивы и ключи

PHP-массив — упорядоченная хеш-таблица. «Индексированный» и «ассоциативный» — разговорные названия одного типа: ключи бывают integer и string.

~~~php
$names = ['Alex', 'Maria']; // 0, 1
$user = ['name' => 'Ivan', 'age' => 25];
$names = [2 => 'Alex'];
$names[] = 'Maria'; // ключ 3, а не 1
$names[0] = 'Petr'; // отдельный элемент
~~~

В $user['name'] строка name — ключ, Ivan — значение. Это модель данных Request::validated() и toArray() в Laravel.

### foreach и висячая ссылка

Без & переменная цикла получает значение. С & она ссылается на элемент и остаётся ссылкой на последний элемент после цикла.

~~~php
$numbers = [10, 20, 30];
foreach ($numbers as &$number) { $number *= 2; }
// [20, 40, 60], но $number связан с последним элементом
foreach ($numbers as $number) { }
var_dump($numbers); // [20, 40, 40]
unset($number); // писать сразу после цикла по ссылке
~~~

Последняя итерация второго цикла записывает 40 через старую ссылку в последний элемент. Правило: закончил foreach (... as &$value) — сразу unset($value).

### clone и поверхностная копия

clone создаёт новый объект верхнего уровня. Простые свойства независимы, но вложенные объекты остаются общими: это shallow copy.

~~~php
class Address { public string $city = 'Moscow'; }
class User {
    public string $name = 'Alex';
    public Address $address;
    public function __construct() { $this->address = new Address(); }
}
$first = new User();
$second = clone $first;
$second->name = 'Ivan';
$second->address->city = 'Kazan';
// $first: Alex/Kazan; $second: Ivan/Kazan
~~~

Неверно говорить, что вложенные объекты «перезаписываются»: это один общий объект, чьё свойство изменили. Для независимой вложенной части нужен явный клон:

~~~php
public function __clone()
{
    $this->address = clone $this->address;
}
~~~

Самопроверка: изменили только $copy->address->city. Изменится ли имя оригинала? А город без __clone()?  
Ответ: имя — нет; город — да, поскольку address общий.

### Видимость, static, closures и array_map

Локальная функция не видит внешнюю переменную автоматически. global подключает глобальную переменную, но зависимость предпочтительнее передавать аргументом. Локальная static сохраняется между вызовами.

~~~php
function nextNumber(): int { static $n = 0; return ++$n; }
nextNumber(); // 1
nextNumber(); // 2
~~~

Closure захватывает внешнюю лексическую переменную через use; это не делает её глобальной.

~~~php
$rate = 2;
$byValue = function (int $value) use ($rate): int { return $value * $rate; };
$byReference = function (int $value) use (&$rate): int { return $value * $rate; };
$rate = 5;
echo $byValue(10);     // 20: запомнено 2
echo $byReference(10); // 50: читается та же переменная
$arrow = fn (int $value): int => $value * $rate;
echo $arrow(4); // 20
~~~

Стрелочная функция удобна для короткого выражения; обычная — когда нужен многострочный блок или захват по ссылке. В Laravel closures встречаются в маршрутах, query builder и Collection::map().

array_map() создаёт новый массив, не меняя входной.

~~~php
$prices = [100, 200, 300];
$discount = 10;
$discounted = array_map(
    fn (int $price): float => $price * (1 - $discount / 100),
    $prices,
); // [90.0, 180.0, 270.0]
~~~

Для денег лучше хранить минимальные единицы как int и явно выбрать округление: float может давать двоичные погрешности.

## День 3 — 24 октября: строгие типы и полиморфизм

### strict_types: аргументы и return проверяются в разных местах

declare(strict_types=1) — настройка конкретного файла, а не всего проекта. Для аргументов важен файл, где вызывают функцию; для возврата — файл, где функция определена.

~~~php
// strict-caller.php
declare(strict_types=1);
require 'weak-function.php';
echo twice('10'); // TypeError: строгий вызывающий файл не приводит string к int

// weak-function.php
function twice(int $x): int { return $x * 2; }
~~~

Исключение: int допустим там, где ожидается float, даже в strict mode.

~~~php
declare(strict_types=1);
function tax(float $amount): float { return $amount * 0.2; }
echo tax(100); // 20.0
~~~

Обратное float → int в строгом режиме не допускается. В слабом оно могло усечь дробную часть, но такое неявное преобразование с потерей точности deprecated в современных PHP. Исправлять нужно контрактом или явным преобразованием с понятным правилом.

~~~php
function label(?string $name): string { return $name ?? 'Guest'; }
function addTax(int|float $amount): float { return $amount * 1.2; }
~~~

?string равно string|null; union int|float честно обозначает, что допустимы оба типа. Return type — часть договора: в строгом файле определения return 3.8 из функции : int — ошибка, а (int) 3.8 — явное усечение, которое ещё надо бизнес-обоснованно выбрать.

### Практика: calculatePaidTotal

~~~php
function calculatePaidTotal(array $orders): float
{
    $sum = 0;
    foreach ($orders as $order) {
        if ($order['paidAt'] !== null) {
            $sum += $order['total'];
        }
    }
    return (float) $sum;
}
~~~

$sum = 0 задаёт начальный результат; !== null проверяет именно наличие отметки об оплате, а не truthiness; += накапливает; (float) согласует return type. В реальном Laravel-коде ещё валидируют структуру заказа и обычно считают деньги в копейках.

### Интерфейс, abstract class, protected и DI

Интерфейс описывает публичный контракт — что объект умеет. Абстрактный класс нужен, когда есть общий код или защищённые помощники. implements подписывает под интерфейс, extends наследует класс.

~~~php
interface LoggerInterface { public function log(string $message): void; }

abstract class BaseLogger implements LoggerInterface
{
    protected function formatMessage(string $message): string
    {
        return '[LOG] ' . $message;
    }
}

final class FileLogger extends BaseLogger
{
    public function log(string $message): void
    {
        echo $this->formatMessage($message);
    }
}
~~~

Абстрактный BaseLogger вправе не реализовать log(). Первый конкретный наследник обязан реализовать контракт или тоже быть abstract. protected доступен классу и наследникам, но не внешнему коду.

Полиморфизм означает, что потребитель работает с контрактом, а не с одним классом:

~~~php
function writeAudit(LoggerInterface $logger, string $message): void
{
    $logger->log($message);
}
writeAudit(new FileLogger(), 'Order created');
// DatabaseLogger можно подставить без изменения writeAudit()
~~~

Это прямая подготовка к Laravel DI: контейнер связывает интерфейс с реализацией и подставляет её в сервис/контроллер. Самостоятельная практика FormatterInterface → BaseFormatter → UppercaseFormatter показала уверенное применение этой схемы.

Самопроверка: почему new BaseLogger() нельзя? Почему FileLogger видит formatMessage(), а внешний код нет?  
Ответ: abstract-класс не создаётся; protected открыт наследникам, но не внешнему коду.

## Маршрут повторения

1. Для вывода всегда выписывать тип и значение выражения.
2. Для ссылок и clone рисовать, какие имена указывают на одну ячейку/объект.
3. Для типов отмечать два места: вызов функции и её определение.
4. Для ООП сначала назвать контракт, затем решать, нужен ли общий abstract-код.

## День 4 — 26 октября: контракт, полиморфизм и ручная DI

### Повторение точных моделей

Повторение трёх прежних ловушек прошло на 5/5. Здесь важно не просто помнить ответ, а различать, что именно остаётся общим или допустимым. `int` можно передать в параметр `float` даже при `declare(strict_types=1)`: целое представимо как число с плавающей точкой без потери дробной части. Обратное направление, `float` в `int`, несимметрично, потому что способно потерять дробную часть.

`clone` создаёт новый объект верхнего уровня, но не копирует автоматически объекты, лежащие в его свойствах. Поэтому без `__clone()` оригинал и копия смотрят на один `Address`. После `foreach ($items as &$item)` имя `$item` продолжает быть ссылкой на последний элемент; `unset($item)` разрывает эту связь имени, не удаляя элемент массива.

Полезная проверка для всех трёх случаев: спросить себя, что именно здесь одно и то же — допустимый тип, вложенный объект или ячейка массива.

### Интерфейс и абстрактный класс

Интерфейс задаёт публичное обещание потребителю: любой логгер принимает строку через `log()`. Абстрактный класс нужен только там, где реализациям действительно есть чем поделиться: форматированием, проверкой или защищённым помощником. Если общего поведения нет, интерфейса достаточно.

~~~php
interface LoggerInterface
{
    public function log(string $message): void;
}

abstract class BaseLogger implements LoggerInterface
{
    protected function decorate(string $message): string
    {
        return '[app] ' . $message;
    }
}

final class ConsoleLogger extends BaseLogger
{
    public function log(string $message): void
    {
        echo $this->decorate($message) . PHP_EOL;
    }
}
~~~

Конкретный `ConsoleLogger` обязан реализовать `log()`. Метод `decorate()` доступен ему как наследнику, но не внешнему коду: это смысл `protected`. Интерфейсы и абстрактные классы разобраны на 4.5/5; осталось закреплять выбор абстрактной базы только при наличии настоящего общего кода.

### Полиморфизм: зависимость от способности, а не от класса

Полиморфизм проявляется там, где потребитель принимает интерфейс и не зависит от названия конкретного класса. `UserService` создаёт пользователя и просит логгер сообщить о событии; способ логирования для него не важен.

~~~php
final class UserService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function createUser(string $name): void
    {
        // Здесь могла бы быть запись пользователя в хранилище.
        $this->logger->log("User {$name} created");
    }
}

$service = new UserService(new ConsoleLogger());
$service->createUser('Alex');
~~~

Вместо консоли можно передать реализацию для теста, не меняя `UserService`:

~~~php
final class ArrayLogger implements LoggerInterface
{
    public array $messages = [];

    public function log(string $message): void
    {
        $this->messages[] = $message;
    }
}
~~~

Тема полиморфизма выполнена на 5/5: замена реализации не требует переписывать бизнес-сценарий.

### Dependency Injection до Laravel

Dependency Injection — не магия Laravel, а способ распределить ответственность. Сервис получает готовую зависимость снаружи, вместо того чтобы создавать конкретный логгер внутри себя. Так решение, какой логгер использовать, остаётся в точке сборки программы.

~~~php
$logger = new ConsoleLogger();
$service = new UserService($logger);
$service->createUser('Alex');
~~~

Первоначально затруднение было в самостоятельной сборке цепочки: интерфейс выглядел как препятствие для создания объекта. Верная модель: создаётся конкретный `ConsoleLogger`; он реализует `LoggerInterface`; поэтому его можно передать в параметр конструктора, требующий этот интерфейс. Задача `LoggerInterface` → `ConsoleLogger` → `UserService` после разбора решена успешно.

`new` создаёт конкретный объект сразу, и его зависимости нужно передать вручную. Laravel Service Container автоматизирует ту же сборку: анализирует конструкторы, создаёт конкретные классы и применяет зарегистрированное связывание, когда встречает интерфейс. Контейнер не создаёт интерфейс сам по себе — ему нужно правило, какую реализацию подставить.

~~~php
// В сервис-провайдере Laravel:
$this->app->bind(LoggerInterface::class, ConsoleLogger::class);
~~~

### `void`, `echo` и `return`

`void` означает, что метод выполняется ради действия и не отдаёт вызывающему коду полезный результат. Внутри него можно записывать данные, вызывать логгер и даже делать `echo`, но вывод на экран — это побочный эффект, а не возвращаемое значение.

~~~php
function announce(string $message): void
{
    echo $message;
}
~~~

`return;` в таком методе допустим только как ранний выход. `return 'Ready';` нарушит контракт `void`. Поэтому присваивание ниже лишнее: результатом вызова будет `null`, а не созданный пользователь.

~~~php
$result = $service->createUser('Alex'); // лишняя переменная
~~~

Нужно просто вызвать `$service->createUser('Alex');`. Если вызывающему коду правда нужен созданный пользователь, договор метода следует изменить, например на `: User`, и вернуть объект. Выбор между `void` и `return` определяется нуждами вызывающего кода, а не наличием `echo`.


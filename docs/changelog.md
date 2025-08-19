<!--
@file: docs/changelog.md
@description: Хронологический журнал изменений проекта
@dependencies: docs/Project.md, docs/tasktracker.md
@created: 2025-08-19
-->

## [2025-08-19] - Инициализация документации проекта
### Добавлено
- Базовые документы в `docs/`: `Project.md`, `tasktracker.md`, `changelog.md`, `Diary.md`, `qa.md`

## [2025-08-19] - Заполнены ответы в QA
### Добавлено
- Ответы на вопросы в `docs/qa.md` (версии, лицензия, slug/text-domain, i18n, WP-CLI, capabilities, .gitignore)

### Изменено
- Уточнены минимальные требования: PHP 8.1+

## [2025-08-19] - Подготовка плагина к PCP: метаданные, i18n, экранирование
### Добавлено
- Заголовок плагина: `Plugin URI`, `Requires at least`, `Tested up to`, `Requires PHP`, `License`, `License URI`, `Text Domain`, `Domain Path`, `Author URI`
- Загрузка text-domain через `plugins_loaded`
- Каталог `languages/`

### Изменено
- Переименование плагина в заголовке на `MksDdn Postman Collection`
- Переводимые строки и экранирование в `Postman_Admin`
- Сообщение об ошибке переведено на английский (PCP/i18n)

### Исправлено
- Санитизация входящих значений из `$_POST` в `Postman_Admin`

## [2025-08-19] - Локализация и дистрибутивная подготовка
### Добавлено
- `languages/mksddn-postman-collection.pot` для i18n
- `readme.txt` для публикации в WordPress.org
- `.gitignore` для GitHub

### Изменено
- Н/Д

### Исправлено
- Н/Д

## [2025-08-19] - WP-CLI экспорт коллекции
### Добавлено
- CLI: команда `wp mksddn-postman export` для вывода в файл или STDOUT

### Изменено
- Readme: добавлены инструкции по WP-CLI

### Исправлено
- Н/Д

## [2025-08-19] - План без CI
### Изменено
- Документация уточнена: исключили GitHub Actions/CI, проверки PCP/PHPCS выполняются локально

### Исправлено
- Н/Д

## [2025-08-19] - Локальный PHPCS
### Добавлено
- `phpcs.xml.dist` с правилами WPCS и PHPCompatibility (локальные проверки)

### Изменено
- Н/Д

### Исправлено
- Н/Д
### Исправлено
- Н/Д

### Изменено
- Н/Д

### Исправлено
- Н/Д



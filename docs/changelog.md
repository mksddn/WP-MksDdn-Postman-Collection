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

### Исправлено
- Н/Д

### Изменено
- Н/Д

### Исправлено
- Н/Д



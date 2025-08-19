<!--
@file: docs/Diary.md
@description: Дневник наблюдений, решений и проблем проекта
@dependencies: docs/Project.md, docs/tasktracker.md, docs/qa.md
@created: 2025-08-19
-->

### 2025-08-19
**Наблюдения**
- Исходный MU-плагин оформлен как обычный плагин с классами `Postman_Admin`, `Postman_Generator`, `Postman_Options`, `Postman_Routes`.
- Присутствуют базовые меры безопасности: `ABSPATH`-guard, `manage_options`, `check_admin_referer`.
- Часть HTML-вывода в админке требует системного экранирования для соответствия WPCS/PCP.

**Решения**
- Оформить отдельный плагин `mksddn-postman-collection` для публикации в WordPress.org, документацию хранить в `docs/` в корне GitHub.
- Включить i18n, обновить заголовки плагина, подготовить readme для каталога.
- Настроить PHPCS и PCP в CI.

**Проблемы**
- Не подтверждены минимальные версии PHP/WP, лицензия, `text-domain` и `slug` для каталога. Вынесено в `docs/qa.md`.



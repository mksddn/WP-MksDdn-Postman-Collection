<!--
@file: docs/qa.md
@description: Вопросы по архитектуре, требованиям и релизному процессу
@dependencies: docs/Project.md
@created: 2025-08-19
-->

### Вопросы и ответы
1. Минимальные версии окружения
   - Ответ: `Requires at least: 6.2`, `Tested up to: 6.6`, `Requires PHP: 8.1`.
2. Слаг и text-domain
   - Ответ: использовать `mksddn-postman-collection` для каталога и `Text Domain: mksddn-postman-collection` (совпадает с именем директории плагина).
3. Лицензия
   - Ответ: `License: GPL-2.0-or-later`, `License URI: https://www.gnu.org/licenses/gpl-2.0.html`.
4. Автор и ссылки
   - Ответ: `Author: mksddn`, `Author URI: https://github.com/mksddn`, `Plugin URI: https://github.com/mksddn/WP-MksDdn-Postman-Collection`.
5. Мультисайт-совместимость
   - Ответ: поддерживается по умолчанию (админ-страница на уровне сайта, генерация на сайт-базе; специальных network-фич нет).
6. Интернационализация (i18n)
   - Ответ: да. Добавить `load_plugin_textdomain()`, папку `languages/`, `.pot` файл. Языки: базовый `en_US`, локализация `ru_RU` планируется.
7. WP-CLI команды
   - Ответ: да, добавить команду `wp mksddn-postman export --file=postman_collection.json` (по умолчанию вывод в stdout; опция `--include=pages,posts,cpt,options`).
8. Capabilities
   - Ответ: на MVP оставить `manage_options`. В дальнейшем ввести кастомную capability `mksddn_postman_manage` и фильтр для переопределения.
9. Совместимость с MU-плагином
   - Ответ: миграция не требуется (плагин не хранит постоянные данные). Рекомендуется удалить/отключить MU-версию, чтобы избежать дублирования меню/действий.
10. .gitignore для GitHub
   - Ответ: добавить исключения: `.DS_Store`, `.idea/`, `.vscode/`, `node_modules/`, `vendor/`, `dist/`, `build/`, `coverage/`, `*.zip`, `*.log`, `*.cache`, `*.bak`, `composer.lock` (если не публикуем vendor), `exports/`, `postman_collection*.json`. Для SVN будет публиковаться только `mksddn-postman-collection/`.



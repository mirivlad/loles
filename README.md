# LoLeS

**LoLeS (Local Learning System)** — самостоятельно размещаемая система управления обучением для учебных центров и организаций. Название проекта не ограничивает область его применения.

[![Container image](https://github.com/mirivlad/loles/actions/workflows/container.yml/badge.svg)](https://github.com/mirivlad/loles/actions/workflows/container.yml)
[GitHub](https://github.com/mirivlad/loles) · [GHCR](https://github.com/mirivlad/loles/pkgs/container/loles)

> **Статус:** разработка. Существующий интерфейс и бизнес-логика ещё нуждаются в аудите и доработке. Не используйте эту версию для реальных персональных данных или публичного интернет-сервиса без дополнительной проверки безопасности.

## Возможности текущего кода

- Предприятия, группы, пользователи и роли (администратор, менеджер, куратор, учащийся).
- Курсы, модули, категории, тесты и банк вопросов.
- Индивидуальные и групповые назначения, заявки на обучение.
- Кабинет учащегося, просмотр курсов, тестов и расписания.
- Русскоязычный интерфейс (в процессе доработки).

Полноценное прохождение тестов и отчётность о результатах **ещё не завершены**.

## Docker / Portainer

Для LoLeS необходимы **два контейнера**:

| Сервис | Назначение |
| --- | --- |
| `app` — `ghcr.io/mirivlad/loles:latest` | PHP 8.3, Apache, Laravel |
| `db` — `mariadb:11.4` | База данных, не открывается наружу |

В `compose.yaml` уже описаны health checks, автоматические миграции и роли, настройки для Portainer и постоянные тома `db_data` и `app_storage`. При пересоздании контейнеров эти тома **сохраняются**.

### Вариант 1. Portainer (рекомендуется)

1. В Portainer откройте **Stacks → Add stack**. Укажите имя `loles`.
2. Выберите **Repository** (Git) и введите URL `https://github.com/mirivlad/loles`, ветку `refs/heads/main` и путь к Compose-файлу `compose.yaml`. Альтернативно можно вставить содержимое `compose.yaml` в Web editor.
3. В разделе **Environment variables** задайте следующие параметры (секреты генерируйте локально):

   | Переменная | Значение |
   | --- | --- |
   | `APP_KEY` | **Обязательно.** `base64:` + результат команды `openssl rand -base64 32` |
   | `DB_PASSWORD` | **Обязательно.** Случайный стойкий пароль без пробелов |
   | `APP_URL` | Адрес сайта, например `http://192.168.1.50:8088` или `https://loles.example.org` |
   | `LOLES_PORT` | Необязательно: порт хоста, по умолчанию `8088` |
   | `TRUSTED_PROXIES` | Необязательно: IP/CIDR доверенного обратного прокси |
   | `SESSION_SECURE_COOKIE` | `true` при использовании HTTPS |

   Пример генерации значений в терминале:

   ```bash
   printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32)"
   printf 'DB_PASSWORD=%s\n' "$(openssl rand -hex 24)"
   ```

4. Нажмите **Deploy the stack**. После запуска обоих контейнеров приложение выполнит миграции и создаст системные роли. **Демонстрационные пользователи с известными паролями не создаются.**
5. Создайте первого администратора. В Portainer откройте **Containers → loles-app → Console**, выберите `/bin/sh`, подключитесь и запустите:

   ```bash
   php artisan loles:create-admin
   ```

   Введите имя, email и пароль по запросу. После этого входите через `/login`. Публичная регистрация учащихся в текущей версии приложения пока доступна.

**Важно:** пароли и `APP_KEY` нельзя менять при обычном обновлении. При замене `DB_PASSWORD` в существующем томе MariaDB пароль пользователя базы автоматически не меняется; его необходимо изменить в самой БД. Потеря `APP_KEY` приводит к недействительности существующих шифрованных данных и сессий.

### Вариант 2. Docker Compose

Требуется Docker Engine и Docker Compose plugin.

```bash
git clone https://github.com/mirivlad/loles.git
cd loles
cp .env.docker.example .env
```

Отредактируйте `.env`, установите `APP_KEY`, `DB_PASSWORD`, `APP_URL` и при необходимости `LOLES_PORT`. Затем:

```bash
docker compose up -d
docker compose ps
docker compose exec app php artisan loles:create-admin
```

По умолчанию сайт доступен на порту **8088** хоста; если настроена другая переменная `LOLES_PORT`, используйте её. Docker не требуется собирать локально — используется готовый образ из GHCR.

### Обновление: Pull + Redeploy

Каждый push в `main` запускает GitHub Actions и публикацию нового `ghcr.io/mirivlad/loles:latest`. Дождитесь успешной сборки образа.

В **Portainer**: откройте **Stacks → loles → Editor / Update stack**, включите **Pull latest image** и выполните **Update / Re-deploy**. Названия пунктов немного зависят от версии Portainer. В стеке, созданном из Git, при необходимости также обновите конфигурацию из репозитория.

В **Docker Compose**:

```bash
git pull --ff-only
docker compose pull
docker compose up -d
```

Перед обновлениями с миграциями БД сделайте резервную копию. **Не используйте `docker compose down -v`** — флаг `-v` удаляет тома и данные.

### Резервное копирование

Для Docker Compose (из каталога проекта с настроенным `.env`):

```bash
docker compose exec -T db sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' > loles-db.sql
docker compose exec -T app tar czf - -C /var/www/html storage > loles-storage.tar.gz
```

Сохраняйте файлы резервных копий вне хоста приложения; используйте права, ограничивающие доступ к содержимому резервных копий.

### HTTPS и reverse proxy

- Установите `APP_URL=https://loles.example.org`.
- Направьте reverse proxy на опубликованный порт LoLeS (обычно `8088`) и передавайте `Host`, `X-Forwarded-Proto` и `X-Forwarded-For`.
- Задайте `TRUSTED_PROXIES` как IP/CIDR **конкретного доверенного прокси**. Не используйте `*`, если приложение доступно напрямую извне.
- Установите `SESSION_SECURE_COOKIE=true`.
- Не публикуйте MariaDB в интернет. Ограничьте доступ к тестовому стенду через firewall/VPN.

### Диагностика

```bash
docker compose ps
docker compose logs -f --tail=150 app
docker compose logs -f --tail=100 db
docker compose exec app php artisan migrate:status
```

Если новый образ не скачивается, убедитесь, что сборка GitHub Actions завершилась, а GHCR-пакет `loles` открыт для публичного чтения (при необходимости **Package settings → Change visibility → Public**).

## Разработка

Технологии: PHP 8.3+, Laravel (версия закреплена в `composer.lock`), Blade, MariaDB/MySQL, Spatie Laravel Permission, TinyMCE.

Для разработки без Docker понадобятся PHP и расширения из `Dockerfile`, Composer и MySQL/MariaDB. Настройте локальную `.env` на основе `.env.example`, затем:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan loles:create-admin
php artisan serve
```

Не запускайте полный `DatabaseSeeder` на рабочей системе: исходный `UserSeeder` создаёт тестовые аккаунты с известными паролями. Отдельный сидер ролей безопасен для повторного применения.

## Что дальше

Первый этап — стабильное развертывание. Далее — исправление найденных ошибок, UX/UI менеджера, импорт учащихся и предприятий, назначение курсов и тестов, отчёты и полноценное тестирование.

## Лицензия

В репозитории пока отсутствует отдельный файл `LICENSE`. Перед публикацией как открытого программного обеспечения необходимо выбрать и оформить лицензию. Метаданные исходного Laravel-шаблона не заменяют лицензию проекта.

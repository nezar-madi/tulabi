# طلابي (tulabi)

منصة للمدرّسين المستقلين في ليبيا: صفحة لكل مدرّس بفصوله، والطلاب يسجّلون ويطلبون الانضمام، والمدرّس يقبلهم ويسجّل دفعاتهم.

- المواصفات: [docs/spec.md](docs/spec.md)
- ملاحظات التطوير: [CLAUDE.md](CLAUDE.md)

## التشغيل محليًا

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build
php artisan serve --port=8010
```

## الاختبارات

```bash
php artisan test
npm run test:js
vendor/bin/pint
```

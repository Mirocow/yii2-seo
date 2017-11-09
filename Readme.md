##### Install

```bash
./yii migrate --migrationPath="@vendor/mirocow/yii2-seo/migrations"
```

##### Behavior

```php
    public function behaviors()
    {
        return [
            'seo' => [
              'class' => 'mirocow\seo\MetaFieldsBehavior',
              'userCanEdit' => Yii::$app->has('user') && Yii::$app->user->can(User::ROLE_ADMIN),
            ],            
        ];
    }
```
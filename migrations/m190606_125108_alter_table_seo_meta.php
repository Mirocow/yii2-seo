<?php

use yii\db\Migration;

/**
 * Class m190606_125108_alter_table_seo_meta
 */
class m190606_125108_alter_table_seo_meta extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('seo_meta', 'content', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190606_125108_alter_table_seo_meta cannot be reverted.\n";

        return false;
    }

}

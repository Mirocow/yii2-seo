<?php

use yii\db\Migration;

/**
 * Class m180912_200231_alter_table_seo_meta
 */
class m180912_200231_alter_table_seo_meta extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%seo_meta}}', 'id', $this->primaryKey(11));
        $this->addColumn('{{%seo_meta}}', 'hash', $this->string(32)->unique());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180912_200231_alter_table_seo_meta cannot be reverted.\n";

        return false;
    }

}
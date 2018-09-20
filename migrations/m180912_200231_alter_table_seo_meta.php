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
        $this->addPrimaryKey('pk_seo_meta', 'seo_meta', ['key']);
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

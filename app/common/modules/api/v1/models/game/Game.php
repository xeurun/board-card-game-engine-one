<?php

namespace common\modules\api\v1\models\game;

/**
 * This is the model class for table "word".
 *
 * @property integer $id
 * @property string  $content
 * @property integer $created_at
 * @property integer $updated_at
 */
class Game extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{game}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
        ];
    }
}

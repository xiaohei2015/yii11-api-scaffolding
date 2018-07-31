<?php

/**
 * This is the model class for table "user_auths".
 *
 * The followings are the available columns in table 'user_auths':
 * @property string $id
 * @property string $user_id
 * @property integer $app_type
 * @property string $app_user_id
 * @property string $access_token
 * @property string $nickname
 * @property string $avatar
 * @property integer $add_time
 * @property integer $update_time
 * @property integer $is_bind
 */
class UserAuths extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'user_auths';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('app_type, add_time, update_time, is_bind', 'numerical', 'integerOnly'=>true),
			array('user_id', 'length', 'max'=>20),
			array('app_user_id, access_token, nickname, avatar', 'length', 'max'=>255),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, user_id, app_type, app_user_id, access_token, nickname, avatar, add_time, update_time, is_bind', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => '平台用户id',
			'app_type' => '第三方类型1微信2QQ3新浪微博',
			'app_user_id' => '第三方用户唯一标示',
			'access_token' => '第三方access_tocken',
			'nickname' => '第三方昵称',
			'avatar' => '第三方头像',
			'add_time' => '创建时间',
			'update_time' => '更新时间',
			'is_bind' => '是否绑定平台账户',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('app_type',$this->app_type);
		$criteria->compare('app_user_id',$this->app_user_id,true);
		$criteria->compare('access_token',$this->access_token,true);
		$criteria->compare('nickname',$this->nickname,true);
		$criteria->compare('avatar',$this->avatar,true);
		$criteria->compare('add_time',$this->add_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('is_bind',$this->is_bind);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return UserAuths the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}

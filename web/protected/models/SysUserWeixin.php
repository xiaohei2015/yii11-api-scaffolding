<?php

/**
 * This is the model class for table "sys_user_weixin".
 *
 * The followings are the available columns in table 'sys_user_weixin':
 * @property string $id
 * @property string $wxid
 * @property string $unionid
 * @property string $nick_name
 * @property string $avatar
 * @property integer $sex
 * @property string $country
 * @property string $province
 * @property string $city
 * @property string $language
 * @property integer $subscribe
 * @property integer $subscribe_time
 * @property string $bind_uid
 * @property integer $add_time
 */
class SysUserWeixin extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'sys_user_weixin';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sex, subscribe, subscribe_time, add_time', 'numerical', 'integerOnly'=>true),
			array('wxid, unionid', 'length', 'max'=>32),
			array('nick_name, country, province, city', 'length', 'max'=>128),
			array('avatar', 'length', 'max'=>256),
			array('language', 'length', 'max'=>64),
			array('bind_uid', 'length', 'max'=>20),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, wxid, unionid, nick_name, avatar, sex, country, province, city, language, subscribe, subscribe_time, bind_uid, add_time', 'safe', 'on'=>'search'),
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
			'SysUserBasic'=>array(self::BELONGS_TO, 'SysUserBasic', 'bind_uid'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '编号',
			'wxid' => '微信OPENID',
			'unionid' => '微信UNIONID',
			'nick_name' => '微信昵称',
			'avatar' => '微信头像URL',
			'sex' => '性别(1：男,2:女)',
			'country' => '国家',
			'province' => '省份',
			'city' => '城市',
			'language' => '语言',
			'subscribe' => '是否关注(0:默认，1：关注，2：取消关注)',
			'subscribe_time' => '关注时间',
			'bind_uid' => '绑定的用户ID',
			'add_time' => '创建时间',
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
		$criteria->compare('wxid',$this->wxid,true);
		$criteria->compare('unionid',$this->unionid,true);
		$criteria->compare('nick_name',$this->nick_name,true);
		$criteria->compare('avatar',$this->avatar,true);
		$criteria->compare('sex',$this->sex);
		$criteria->compare('country',$this->country,true);
		$criteria->compare('province',$this->province,true);
		$criteria->compare('city',$this->city,true);
		$criteria->compare('language',$this->language,true);
		$criteria->compare('subscribe',$this->subscribe);
		$criteria->compare('subscribe_time',$this->subscribe_time);
		$criteria->compare('bind_uid',$this->bind_uid,true);
		$criteria->compare('add_time',$this->add_time);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return SysUserWeixin the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}

<?php

/**
 * This is the model class for table "sys_msg_auth_log".
 *
 * The followings are the available columns in table 'sys_msg_auth_log':
 * @property string $id
 * @property string $phone
 * @property integer $send_time
 * @property string $code
 * @property string $ip
 * @property integer $failed_count
 * @property integer $status
 */
class SysMsgAuthLog extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'sys_msg_auth_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('phone, send_time', 'required'),
			array('send_time, failed_count, status', 'numerical', 'integerOnly'=>true),
			array('phone', 'length', 'max'=>20),
			array('code, ip', 'length', 'max'=>32),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, phone, send_time, code, ip, failed_count, status', 'safe', 'on'=>'search'),
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
			'phone' => '电话',
			'send_time' => '发送时间',
			'code' => '发送代码',
			'ip' => '验证码请求发送的IP',
			'failed_count' => '验证失败次数',
			'status' => '0未使用，1已使用',
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
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('send_time',$this->send_time);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('ip',$this->ip,true);
		$criteria->compare('failed_count',$this->failed_count);
		$criteria->compare('status',$this->status);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return SysMsgAuthLog the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}

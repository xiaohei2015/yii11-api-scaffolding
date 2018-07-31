<?php
/**
 * 参数验证
 */

class ParamValidator {

	// 允许的请求方式
	public static $requestMethods = array('get','post','header');
	// 数据类型
	public $dataTypes = array(
		'int',
		'number',
		'string',
		'letter', // all letters
		'json',
	);

	public static $paramConfigKeys = array(
		'name',          # 参数名称
		'title',		 # 参数应用名称
		'defaultValue',  # 参数如果为空，定义的默认值
		'requestMethod', # 参数请求方式
		'required',      # 是否为必须参数
		'allowEmpty',    # 是否允许为空 1,0
		'type',          # 参数数据类型 defined in $this->dataTypes.
		'length',        # 数据长度
		'pattern',       # 参数需要匹配的正则规则
		'call_back',     # 参数回调函数
		'copyAs',        # 复制参数配置
	);


	//// 常用配置生成方法 start

	/**
	 * string类型
	 * @param $param
	 * @param array $extra_config
	 */
	public static function stringParam( $param, $extra_config = array() )
	{
		$config = array(
			'name' => $param,
			'type' => 'string',
			'allowEmpty' => 0,
			'required'   => 1,
			'requestMethod' => 'post',
		);

		empty( $extra_config ) or self::pushExtraConfig($config,$extra_config);
		return $config;
	}


	/**
	 * 数字类型的参数  例如user_id, group_id
	 * @param $name
	 * @param array $extra_config
	 * @return array
	 */
	public static function numberParam( $name, $extra_config = array() )
	{
		$config = array(
			'name' => $name,
			'type' => 'number',
			'required'   => 1,
			'allowEmpty' => 0,
			'requestMethod' => 'post',
		);

		empty( $extra_config ) or self::pushExtraConfig($config,$extra_config);
		return $config;
	}

	/**
	 * json类型
	 * @param $name
	 * @param array $extra_config
	 */
	public static function jsonParam( $name, $extra_config = array() )
	{
		$config = array(
			'name'          => $name,
			'type'          => 'json',
			'required'      => 1,
			'allowEmpty'    => 0,
			'requestMethod' => 'post',
			'call_back'     => 'json_decode',
		);

		empty( $extra_config ) or self::pushExtraConfig($config,$extra_config);
		return $config;
	}


	/**
	 * session_id参数的配置
	 * @param array $extra_config
	 * @return array
	 */
	public static function sessionidParam($extra_config=array())
	{
		$config = array(
			'name' => 'sessionid',
			'type' => 'string',
			'required' => 1,
			'allowEmpty' => 0,
		);

		empty( $extra_config ) or self::pushExtraConfig($config,$extra_config);

		return $config;
	}


	/**
	 * 创建配置 支持批量  要求批量设置的数据类型必须统一
	 * @param array $params
	 * @param string $type
	 * @param $extra_config
	 */
	public static function makeConfig( array $params, $type ,$extra_config = array() )
	{
		if( empty( $params ) ) return array();

		$method = strtolower($type).'Param';
		if( !in_array( $method, get_class_methods('ParamValidator') ) ){
			throw new CException( "ParamValidator do not have a method named {$method}" );
		}

		$config = array();
		foreach( $params as $paramName ) {
			$config[] = self::$method( $paramName, $extra_config );
		}

		return $config;
	}


	//// 常用配置生成方法 end


	/**
	 * 推入模板之外的配置
	 * @param $paramconfig
	 * @param array $extra_config
	 */
	private static function pushExtraConfig( &$paramconfig, array $extra_config ){
		if( !empty( $extra_config ) ) {
			foreach ($extra_config as $key => $value) {
				in_array( $key ,self::$paramConfigKeys ) and $paramconfig[$key] = $value;
			}
		}
	}


	/**
	 * 格式化参数配置数据
	 * @param array $paramConfig
	 * @return array
	 * @throws CException
	 */
	public function validateConfig( array $paramConfig )
	{
		if( empty( $paramConfig ) ){
			throw new CException("Param config should not be an empty array.");
		}

		// validate param configs and reformat config
		$formatedConfig = array();
		foreach( $paramConfig as $config ) {
			if( !isset( $config['name'] ) )
				throw new CException("Param config must include the param name key.");
			$formatConfig = array_combine( self::$paramConfigKeys, array_fill(0,count(self::$paramConfigKeys),''));
			$config = array_merge( $formatConfig,$config );

			// bind json_decode call_back for json data
			if( $config['type'] == 'json' ){
				$config['call_back'] = 'json_decode';
			}

			// save formatted config
			$formatedConfig[$config['name']] = $config;
		}
		return $formatedConfig;
	}


	/**
	 * 验证数据格式
	 * @param $value
	 * @param $type
	 * @return mixed
	 * @throws CException
	 */
	public function validateDataType( $value, $type )
	{	
		$type = strtolower($type);
		if( !in_array($type,$this->dataTypes) )
			throw new CException("Unregistered data type {$type}.");
		$methodName = 'ctype'.ucfirst($type);
		if( !method_exists($this,$methodName) ){
			throw new CException("Param validator does not have the {$methodName} method.");
		}else{
			return $this->$methodName($value);
		}
	}

	/**
	 * 数据类型验证 - number
	 * @param $value
	 * @return bool
	 */
	private function ctypeNumber( $value )
	{
		return is_numeric($value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	private function ctypeLetter( $value )
	{
		return ctype_alpha($value);
	}

	/**
	 * 数据类型验证 - String [可打印字符]
	 * @param $value
	 * @return bool
	 */
	private function ctypeString( $value )
	{
		return is_string($value);
	}

	/**
	 * 数据类型验证 - Json
	 * @param $value
	 * @return bool
	 */
	private function ctypeJson( $value )
	{
		return (bool)json_decode(json_encode($value),true);
	}

	/**
	 * 数据类型验证 - int
	 * @param $value
	 * @return bool
	 */
	private function ctypeInt( $value )
	{
		return is_int($value);
	}

	/**
	 * 正则验证
	 * @param $value
	 * @param $pattern
	 * @return bool
	 */
	public function ctypeRegx( $value, $pattern )
	{
		return (bool)preg_match($pattern, $value);
	}

	/**
	 * 长度验证
	 * @param $value
	 * @param $length
	 * @return bool
	 */
	public function validateLength( $value, $length )
	{
		return mb_strlen($value,'utf-8') <= $length ? true : false;
	}

}
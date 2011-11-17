<?

namespace ActiveRedis;

abstract class Model {
	
	protected $associated;
	protected $attributes;
	protected $isDirty;
	protected $isNew;
	
	static $primaryKey = 'id';
	static $keySeparator = ':';
	static $table = array(
		'behaviors' => array('AutoAssociate', 'AutoTimestamp', 'DeepSave'),
	);
	
	// Not yet supported
	static $index; // which properties to index
	
	// Callbacks
	static $callbacks;
	
	// Behaviors
	static $behaviors;
	
	// Associations
	static $associations;
	
	// For reference returns
	public static $null;
	
	// For meta information
	protected $meta;
	
	function __construct($id = null, $isNew = true) 
	{	
		$this->isNew = $isNew;
		
		if (is_array($id)) {
			$this->setAttributes($id);
		}
	}
	
	function &__get($var)
	{
		// Dynamic Initialization
		$method = 'get' . ucfirst($var);
		if (method_exists($this, $method)) {
			$result = $this->$method();
			return $result;
		}
		
		// Association
		elseif ($association = $this->table()->association($var)) {
			$this->isDirty[$var] = true;
			if (!isset($this->associated[$var])) {
				$this->associated[$var] = $association->associated($this);
			}
			return $this->associated[$var];
		}
		
		// Normal init
		elseif (!isset($this->attributes[$var])) {
			$this->attributes[$var] = null;
		}
		
		return $this->attributes[$var];
	}
	
	function __set($var, $val)
	{
		// Dynamic Initialization
		$method = 'set' . ucfirst($var);
		if (method_exists($this, $method)) {
			$result = $this->$method();
			return $result;
		}
		
		// Association
		elseif ($association = $this->table()->association($var)) {
			$this->isDirty[$var] = true;
			return $this->associated[$var] = $val;
		}
		
		// Default is to set attributes
		$this->isDirty[$var] = true;
		return $this->attributes[$var] = $val;
	}
	
	function __unset($var)
	{
		if (isset($this->attributes[$var])) {
			unset($this->attributes[$var]);
		} elseif (isset($this->associated[$var])) {
			unset($this->associated[$var]);
		}
	}
	
	function __isset($var)
	{
		return isset($this->attributes[$var]) || isset($this->associated[$var]);
	}
	
	
	/**
	 * Bind a callback to a particular event name
	 * 
	 * @param string $eventName
	 * @param callback $callback
	 * @return bool
	 */
	public static function bind($eventName, $callback) 
	{
		return static::table()->bind($eventName, $callback);
	}
	
	/**
	 * Trigger callbacks associated with a particular event name
	 * 
	 * @param string $eventName
	 * @param mixed $args null | array of arguments
	 * @return mixed
	 */
	public static function trigger($eventName, $args = null)
	{
		if (is_null($args)) {
			$args = array(&$this);
		}
		return static::table()->trigger($eventName, $args);
	}
	
	/**
	 * Get the database adapter associated with this model
	 * 
	 * @return Adapter
	 */
	static function db() 
	{
		return static::table()->db();
	}
	
	/**
	 * Get the table object associated with this model
	 * 
	 * @return Table
	 */
	static function table() 
	{
		return Table::instance(get_called_class());
	}
	
	/**
	 * Create a new instance of this model
	 * Automatically saves to the database
	 */
	static function create($config = null) 
	{
		$model = new static($config);
		$model->save();
		return $model;
	}
	
	/**
	 * Retrieve an instance of this model from the database
	 * 
	 * @param mixed $id
	 * @return Model
	 */
	static function find($id) 
	{
		// Instantiate new class
		$class = get_called_class();
		if ($data = static::db()->get(static::table()->key($id))) {
			return new $class(static::unserialize($data));
		}
		
		// Not found!
		throw new Exception('Not found');
	}
	
	/**
	 * Unserialize data from the database
	 * 
	 * @param array $data
	 * @return Model
	 */
	static function unserialize($data) 
	{
		return new static(json_decode($data), false);
	}
	
	/**
	 * Serialize a model for storage in the database
	 * 
	 * @return string
	 */
	public function serialize() 
	{
		return json_encode($this->toArray());
	}
	
	public function associated($name = null)
	{
		if (is_null($name)) {
			return $this->associated;
		}
		if ($name && isset($this->associated[$name])) {
			return $this->associated[$name];
		}
	}
	
	public function meta($get, $set = null)
	{
		Log::vebug(get_class($this) . '::' . __FUNCTION__ . "($get, " . json_encode($set) . ")");
		if (!is_null($set)) {
			$this->meta[$get] = $set;
			return $this->meta[$get];
		}
		if (isset($this->meta[$get])) {
			return $this->meta[$get];
		}
	}
	
	/**
	 * Gets or sets an attribute by name
	 * 
	 * @param string $get
	 * @param mixed $set optional
	 * @return mixed
	 */
	function attr($get, $set = null) 
	{
		if (!is_null($set)) {
			return $this->setAttribute($get, $set);
		}
		return $this->getAttribute($get);
	}
	
	function getAssociated($name) 
	{
		if (isset($this->$name)) {
			return $this->$name;
		}
	}
	
	function getAttributes($list) {
		$result = array();
		foreach ($list as $id => $val) {
			if ($id && is_string($id)) {
				$result[$id] = $this->getAttribute($id) ?: $val;
			} else {
				$result[$val] = $this->getAttribute($val);
			}
		}
	}
	
	function addAttribute($var, $val)
	{
		$this->attributes[$var] = isset($this->attributes[$var]) ? (array) $this->attributes[$var] : array();
		return $this->mergeAttribute($var, $val);
	}
	
	function mergeAttribute($var, $val)
	{
		if ((!isset($this->attributes[$var])) || (!is_array($this->attributes[$var]))) {
			return $this->setAttribute($var, $val);
		}
		return $this->setAttribute($var, array_merge($this->attributes[$var], (array) $val));
	}
	
	function setAttributes($attributes) {
		foreach ($attributes as $var => $val) {
			$this->setAttribute($var, $val);
		}
	}
	
	function setAttribute($var, $val) 
	{
		if (!isset($this->attributes[$var]) || ($this->attributes[$var] !== $val)) {
			$this->isDirty[$var] = true;
		}
		return $this->attributes[$var] = $val;
	}
	
	function getAttribute($var) 
	{
		if (isset($this->attributes[$var])) {
			return $this->attributes[$var];
		}
		Log::notice('Undefined attribute ' . $var . ' in ' . get_class($this));
	}
	
	static function foreignKey() 
	{
		if (!static::$foreignKey) {
			static::$foreignKey = lcfirst(array_pop(explode('\\', get_called_class()))) . '_' . ($leftClass::$primaryKey ?: 'id');
		}
		return static::$foreignKey;
	}
	
	static function primaryKey() 
	{
		return static::$primaryKey ?: 'id';
	}
	
	function primaryKeyValue($setValue = false) 
	{
		if ($setValue === false) {
			if ($value = $this->getAttribute($this->primaryKey())) {
				return $value;
			}
			$setValue = $this->table()->nextUnique($this->primaryKey());
		}
		return $this->setAttribute($this->primaryKey(), $setValue);
	}
	
	function getId() {
		return $this->primaryKeyValue();
	}
	
	function key(/* polymorphic */) 
	{
		$keys = array_flatten(array($this->primaryKeyValue(), func_get_args()));
		return $this->table()->key($keys);
	}
	
	function isDirty($var = null) {
		if ($var) {
			return isset($this->isDirty[$var]) && $this->isDirty[$var];
		}
		return $this->isDirty;
	}
	
	function isNew() {
		return $this->isNew;
	}
	
	function delete() {
		return $this->db()->rem($this->key());
	}
	
	function save($validate = true) 
	{
		if ($validate) {
			if (!$this->validate()) {
				return false;
			}
		}
		$isNew = $this->isNew();
		if ($isNew || $this->isDirty()) 
		{
			$this->trigger('beforeSave', array(&$this, $isNew));
			if ($this->isNew()) {
				$result = $this->insert();
			} else {
				$result = $this->update();
			}
			$this->isNew = false;
			$this->isDirty = array();
			$this->trigger('afterSave', array(&$this, $isNew, $result));
			return $result;
		}
		return true; // record is already up-to-date
	}
	
	protected function insert() 
	{
		if ($success = $this->table()->insert($this)) {
			// $this->isNew = false;
			// $this->isDirty = false;
			return $success;
		}
	}
	
	protected function update() 
	{
		if ($this->isNew()) {
			throw new Exception('Cannot update new record');
		}
		if ($success = $this->table()->update($this)) {
			// $this->dirty = false;
			return $success;
		}
	}
	
	function toArray() 
	{
		return (array) $this->attributes;
		
		// User-defined allowed in-row attributes
		if ($this->attributes) {
			$allowed = array_flip((array) $this->attributes);
		}
		
		// Generate allowed attributes automatically 
		else {
			
			// Start with all public object variables
			$allowed = get_public_object_vars($this);
			
			// Remove associations, but put their foreignKey in its place
			$associations = (array) $this->table()->associations();
			Log::temp(get_class($this) . ' associations ' . var_export($associations, 1));
			$allowed = array_diff_key($allowed, $associations);
			foreach ($associations as $association) {
				if (is_object($association) && isset($association->foreignKey)) {
					$allowed[$association->foreignKey] = 1;
				}
			}
		}
		
		// Return the array!
		$ra =  array_intersect_key(get_object_vars($this), $allowed);
		Log::temp(get_class($this) . ' toArray() => ' . print_r($ra, 1));
		return $ra;
	}
	
	function toString() {
		return $this->key();
	}
	
	function __toString() {
		return $this->toString();
	}
	
	function validate($data = null) 
	{
		// Allow user to supply arbitrary data to validate
		// If you don't like this, override it in your model.
		$data or $data = $this->toArray();
		
		// Run user validators
		foreach ($data as $var => $val) {
			if (method_exists($this, $validateMethod = 'validate' . ucfirst($var))) {
				$result = $this->$validateMethod($val);
				if ($result === false || is_string($result)) {
					throw new Invalid($result ?: 'Invalid');
				}
			}
		}
		
		// Valid unless invalid
		return true;
	}
	
}

?>
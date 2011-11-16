<?

namespace ActiveRedis;

abstract class Model {
	
	protected $associated;
	protected $attributes;
	protected $isDirty;
	protected $isNew;
	
	static $primaryKey = 'id';
	static $keySeparator = ':';
	static $table;
	
	// Not yet supported
	static $index; // which properties to index
	
	// Callbacks
	static $callbacks;
	
	// Behaviors
	static $behaviors = array('DeepSave');
	
	// Associations
	static $associations;
	
	// For reference returns
	public static $null;
	
	function __construct($id = null, $isNew = true) 
	{	
		$this->isNew = $isNew;
		
		if (is_array($id)) {
			$this->setAttributes($id);
		}
	}
	
	function &__get($var) 
	{
		// Dynamic getters
		if (method_exists($this, $method = 'get' . ucfirst($var))) {
			$result = $this->$method();
			return $result;
		}
		
		// Associations
		if ($association = $this->table()->association($var)) {
			Log::debug(get_class($this) . '::' . __FUNCTION__ . "($var) is an association => " . get_class($association));
			if (!isset($this->associated[$var])) {
				$this->associated[$var] = $association->delegate($this);
			}
			return $this->associated[$var];
		}
		
		// Attributes
		if (!isset($this->attributes[$var])) {
			$this->attributes[$var] = null;
		}
		return $this->attributes[$var];
	}
	
	function __set($var, $val)
	{
		// Dynamic setters first
		if (method_exists($this, $method = 'set' . ucfirst($var))) {
			return $this->$method();
		}
		
		// Associations
		if (($val instanceof Model) && ($association = $this->table()->association($var))) {
			
			Log::debug(get_class($this) . '::' . __FUNCTION__ . "($var) is an association => " . get_class($association));
			
			// Associate the models
			$association->associate($this, $val);
			
			// Store the association for DeepSave, etc
			if ($association::$poly) {
				$this->associated[$var][] = $val;
			} else {
				$this->associated[$var] = $val;
			}
			
			// Return value of __set is ignored by PHP
			return $val;
		}
		
		return $this->mergeAttribute($var, $val);
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
	function serialize() 
	{
		return json_encode($this->toArray());
	}
	
	/**
	 * Check to see if a particular association has been
	 * invoked / cached for this model
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function associatedKeyExists($name) 
	{
		return isset($this->associated[$name]);
	}
	
	/**
	 * @param mixed $name optional
	 * @param mixed $set optional
	 */
	public function &associated($name = null, $set = false)
	{
		if (is_null($name)) {
			return $this->associated;
		}
		if ($set !== false) {
			$this->associated[$name] = $set;
		}
		elseif (!isset($this->associated[$name])) {
			$this->associated[$name] = null;
		}
		return $this->associated[$name];
	}
	
	/**
	 * Get a table association object by name
	 * 
	 * @param string name
	 * @return mixed Association | null
	 */
	function association($name)
	{
		if ($association = $this->table()->association($name)) {
			return $association->delegate($this);
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
		if (!isset($this->attributes[$var])) {
			$this->attributes[$var] = array();
		} elseif (!is_array($this->attributes[$var])) {
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
		$this->attributes[$var] = $val;
		return $this->attributes[$var];
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
	
	function id() {
		return $this->primaryKeyValue();
	}
	
	function key(/* polymorphic */) 
	{
		$keys = array_flatten(array($this->primaryKeyValue(), func_get_args()));
		return $this->table()->key($keys);
	}
	
	function isDirty() {
		return $this->isDirty;
	}
	
	function isNew() {
		return $this->isNew;
	}
	
	function delete() {
		return $this->db()->del($this->key());
	}
	
	function save($validate = true) 
	{
		if ($validate) {
			if (!$this->validate()) {
				return false;
			}
		}
		$isNew = $this->isNew();
		if ($isNew || $this->isDirty()) {
			$this->trigger('beforeSave', array(&$this, $isNew));
			if ($isNew) {
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
			$this->isNew = false;
			$this->isDirty = false;
			return $success;
		}
	}
	
	protected function update() 
	{
		if ($this->isNew()) {
			throw new Exception('Cannot update new record');
		}
		if ($success = $this->table()->update($this)) {
			$this->dirty = false;
		}
	}
	
	function toArray() 
	{
		return (array) $this->attributes;
		
		// User-defined allowed in-row attributes
		if ($this->attributes) {
			$allowed = array_keys($this->attributes);
		}
		
		// Generate allowed attributes automatically 
		else {
			
			// Start with all public object variables
			$allowed = get_public_object_vars($this);
			
			// Remove associations, but put their foreignKey in its place
			$assocations = $this->table()->associations();
			$allowed = array_diff_key($allowed, $associations);
			foreach ($associations as $association) {
				$allowed[$association->foreignKey] = 1;
			}
		}
		
		// Return the array!
		return array_intersect_key(get_object_vars($this), $allowed);
	}
	
	function toString() {
		return $this->__toString();
	}
	
	function __toString() {
		return $this->key();
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
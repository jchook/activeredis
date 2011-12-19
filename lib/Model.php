<?

namespace ActiveRedis;

abstract class Model {
	
	protected $associated;
	protected $attributes;
	protected $isDirty;
	protected $isNew;
	
	static $primaryKey = 'id';
	static $keySeparator = ':';
	static $table = array();
	
	// Variables accessible via __get or __set
	// Still in planning phase
	static $accessible;
	
	// Indexes
	static $indexes; 
	
	// Callbacks
	static $callbacks;
	
	// Behaviors
	static $behaviors;
	
	// Associations
	static $associations;
	
	// For meta information
	protected $meta;
	
	/**
	 * Create an instance of this model
	 * 
	 * @param mixed $id primary key value or an array of attribute => value pairs.
	 *                  NOTE that if you want to add association objects, you must
	 *                  do this outside of the object construction, or via create.
	 */
	function __construct($id = null, $isNew = true) 
	{	
		$this->isNew = $isNew;
		
		if (is_array($id)) {
			$this->populate($id);
		} elseif ($id) {
			$this->primaryKeyValue($id);
		}
	}
	
	/**
	 * Dynamic get attribute / association
	 */
	function &__get($var)
	{
		// Dynamic Initialization
		$method = 'get' . ucfirst($var);
		if (method_exists($this, $method)) {
			return $this->$method();
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
		
		// Returned by reference
		return $this->attributes[$var];
	}
	
	/**
	 * Support isset for attributes / associations
	 */
	function __isset($var)
	{
		return isset($this->attributes[$var]) || isset($this->associated[$var]);
	}
	
	/**
	 * Dynamic set attribute / association
	 */
	function __set($var, $val)
	{
		// Dynamic Initialization
		$method = 'set' . ucfirst($var);
		if (method_exists($this, $method)) {
			return $this->$method($val);
		}
		
		// Association
		elseif ($association = $this->table()->association($var)) {
			$this->isDirty[$var] = true;
			return $this->associated[$var] = $val;
		}
		
		// Attribute
		$this->setAttribute($var, $val);
	}
	
	/**
	 * Support unset for attributes / associations
	 */
	function __unset($var)
	{
		if (isset($this->attributes[$var])) {
			unset($this->attributes[$var]);
		} elseif (isset($this->associated[$var])) {
			unset($this->associated[$var]);
		}
	}
	
	/**
	 * Dynamic finder methods. These are kind of dumb.
	 * 
	 * @deprecated 2011-12-11
	 */
	static function __callStatic($fn, $args)
	{
		if (substr($fn, 0, 6) == 'findBy') {
			$fn = lcfirst(substr($fn, 6));
			if (method_exists(get_called_class(), $fn)) {
				return call_user_func_array('static::' . $fn, $args);
			} else {
				return static::findBy(explode('_', $fn), $args);
			}
		}
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
	public function trigger($eventName, $args = null)
	{
		return static::table()->trigger($eventName, array_force($args));
	}
	
	/**
	 * Get the database adapter associated with this model
	 * 
	 * @return Adapter
	 */
	public static function db() 
	{
		return static::table()->db();
	}
	
	/**
	 * Get the table object associated with this model
	 * 
	 * @return Table
	 */
	public static function table() 
	{
		return Table::instance(get_called_class());
	}
	
	/**
	 * Create a new instance of this model
	 * Automatically saves to the database
	 */
	public static function create($config = null, $save = true) 
	{
		$class = get_called_class();
		$model = new $class;
		$save and $model->save();
		return $model;
	}
	
	/**
	 * Retrieve a record by primary key value and instantiate it
	 * 
	 * @param mixed $id
	 * @return Model
	 * @throws NotFound
	 */
	public static function find($id) 
	{
		// Instantiate new class
		$class = get_called_class();
		if ($data = static::db()->get(static::table()->key($id))) {
			$model = new $class(static::unserialize($data));
			static::trigger('afterFind', array($model));
		}
		
		// Not found!
		throw new NotFound;
	}
	
	/**
	 * Retrieve all of the records and instantiate them
	 */
	public static function findAll($ids)
	{
		return array_map('static::find', $ids);
	}
	
	/**
	 * Find any records matching an id within the given set.
	 * DOES NOT throw NotFound
	 * 
	 * @param array $ids
	 * @return array
	 */
	public static function findAny($ids)
	{
		$ids = (array) $ids;
		$result = array();
		foreach ($ids as $id) {
			try {
				if ($temp = static::find($id)) {
					$result[] = $temp;
				}
			} catch (NotFound $e) {}
		}
		return $result;
	}
	
	/**
	 * Find by functions do not throw an exception
	 * 
	 * @param string $keyPrefix
	 * @param string $keySuffix
	 * @return mixed
	 */
	public static function findBy($keyPrefix, $keySuffix)
	{
		try {
			return static::find(array($keyPrefix, $keySuffix));
		} catch(NotFound $e) {}
		return null;
	}
	
	/**
	 * Unserialize data from the database
	 * 
	 * @param string $data
	 * @return Model
	 */
	public static function unserialize($data) 
	{
		return new static(json_decode($data), false);
	}
	
	/**
	 * Serialize a model for storage in the database
	 * 
	 * @return string
	 */
	public function serialize($data) 
	{
		return json_encode($this->toArray());
	}
	
	/**
	 * Retrieve associated model(s) by name
	 * Returns all associated models if no name is given
	 * 
	 * @param mixed $name optional
	 * @return mixed by reference
	 * @throws Exception
	 */
	public function &associated($name = null)
	{
		if ($name){
			if (isset($this->associated[$name])) {
				return $this->associated[$name];
			}
			if ($association = $this->table()->association($name)) {
				$args = func_get_args();
				$args[0] = $this;
				$this->associated[$name] = call_user_func_array(array($association, 'associated'), $args);
				return $this->associated[$name];
			}
			$this->associated[$name] = null;
			return $this->associated[$name];
		}
		if (is_null($name)) {
			return $this->associated;
		}
		throw new Exception('Invalid use of ' . get_class($this) . '::associated()');
	}
	
	/**
	 * Check to see if an association has already been instantiated
	 */
	public function isAssociated($name = null)
	{
		return isset($this->associated[$name]);
	}
	
	/**
	 * Get or set meta information about this model
	 * that will NOT be saved to the database
	 * 
	 * @param string $key
	 * @param mixed $set optional
	 * @return mixed by reference
	 */
	public function &meta($key, $set = null)
	{
		Log::vebug(get_class($this) . '::' . __FUNCTION__ . "($key, " . json_encode($set) . ")");
		if (!is_null($set)) {
			$this->meta[$key] = $set;
			return $this->meta[$key];
		}
		if (!isset($this->meta[$key])) {
			$this->meta[$key] = null;
		}
		return $this->meta[$key];
	}
	
	/**
	 * Gets or sets an attribute by name
	 * 
	 * @param string $get
	 * @param mixed $set optional
	 * @return mixed
	 */
	public function attr($get, $set = null) 
	{
		if (!is_null($set)) {
			return $this->setAttribute($get, $set);
		}
		return $this->getAttribute($get);
	}
	
	/**
	 * Returns an associative array of name => value pairs for a set
	 * of attributes. If you do not specify a default, non-existent 
	 * attributes will be excluded from the returned array.
	 * 
	 * EX:
	 * $model->c = 'hello';
	 * $model->getAttributes(array('a', 'b' => null, 'c' => 'default'));
	 * // yields array('b' => null, 'c' => 'hello')
	 * 
	 * @param array $list
	 * @return array
	 */
	public function getAttributes($list) {
		$result = array();
		foreach ($list as $id => $val) {
			if ($id && is_string($id)) {
				$result[$id] = $this->getAttribute($id) ?: $val;
			} elseif ($this->hasAttribute($val)) {
				$result[$val] = $this->getAttribute($val);
			}
		}
	}
	
	public function addAttribute($name, $value)
	{
		$this->attributes[$name][] = $value;
	}
	
	/**
	 * Set attributes via name => $value pairs
	 * 
	 * @param array $attributes
	 * @return null
	 */
	public function setAttributes($attributes, $makeDirty = true) {
		foreach ($attributes as $var => $val) {
			$this->setAttribute($var, $val, $makeDirty);
		}
	}
	
	/**
	 * Set a single attribute value by name
	 * 
	 * @param string $var
	 * @param mixed $val
	 * @param bool $makeDirty
	 * @return mixed $val
	 */
	public function setAttribute($name, $value, $makeDirty = true) 
	{
		if ($makeDirty && (!isset($this->attributes[$name]) || ($this->attributes[$name] !== $value))) {
			$this->isDirty[$name] = true;
		}
		return $this->attributes[$name] = $value;
	}
	
	/**
	 * Get a single attribute by name
	 * 
	 * @param string $var
	 * @return mixed null if the attribute does not exist
	 */
	function getAttribute($name) 
	{
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		Log::notice('Undefined attribute ' . $name . ' in ' . get_class($this));
	}
	
	/**
	 * True if the attribute named exists
	 * 
	 * @param string $var name
	 * @return bool
	 */
	function hasAttribute($name)
	{
		return isset($this->attributes[$name]);
	}
	
	/**
	 * Return the primary key for this model
	 * 
	 * @return string 'id' by default
	 */
	static function primaryKey() 
	{
		return static::$primaryKey ?: 'id';
	}
	
	/**
	 * Get OR Set OR Init the VALUE of the primary key of this model
	 * !!! NOTE: If a value is not already given, one is created !!!
	 * 
	 * @param mixed $setValue optional
	 * @return mixed
	 */
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
	
	/**
	 * Shorthand dynamic getter for primaryKeyValue. 
	 * use $this->id
	 */
	function getId() {
		return $this->primaryKeyValue();
	}
	
	/**
	 * Get the storage key for this model
	 */
	function key(/* polymorphic */) 
	{
		$keys = array_flatten(array($this->primaryKeyValue(), func_get_args()));
		return $this->table()->key($keys);
	}
	
	/**
	 * True if the attribute named has (potentially) been modified
	 * since it was retrieved from the database. If no attribute name
	 * is supplied, this method returns this->isDirty exactly
	 * 
	 * @param mixed $name optional
	 * @return mixed
	 */
	function isDirty($name = null) {
		if ($name) {
			return isset($this->isDirty[$name]) && $this->isDirty[$name];
		}
		return $this->isDirty;
	}
	
	/**
	 * True if the model has not been saved to the database yet
	 * 
	 * @return bool
	 */
	function isNew() {
		return (bool) $this->isNew;
	}
	
	/**
	 * Remove this model from the database
	 * 
	 * @return bool
	 */
	function delete() {
		if ($this->db()->rem($this->key())) {
			$this->isNew = true;
			return true;
		}
		return false;
	}
	
	/**
	 * Save this model to the database
	 * 
	 * @param bool $validate before saving
	 * @return mixed $success
	 */
	function save($validate = true) 
	{
		if ($validate) {
			if ($this->validate() === false) {
				Log::debug('save invalid');
				return false;
			}
		}
		Log::debug('save');
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
		Log::debug(get_class($this) . ' save() called unnecessarily');
		return true; // record is already up-to-date
	}
	
	/**
	 * Insert a new row into the table for this model
	 * 
	 * @return mixed $success
	 */
	protected function insert() 
	{
		Log::debug(get_class($this) . ' insert');
		if ($success = $this->table()->insert($this)) {
			// $this->isNew = false;
			// $this->isDirty = false;
			return $success;
		}
	}
	
	/**
	 * Update the table row for this model
	 * 
	 * @return mixed $success
	 */
	protected function update() 
	{
		Log::debug(get_class($this) . ' update');
		if ($this->isNew()) {
			throw new Exception('Cannot update new record');
		}
		if ($success = $this->table()->update($this)) {
			// $this->dirty = false;
			return $success;
		}
	}
	
	/**
	 * Populate this object with data
	 */
	function populate($data)
	{
		if (is_array($data))
			foreach ($data as $name => $value)
				if ($name && is_string($name))
					$this->$name = $value;
	}
	
	/**
	 * Get a simple array representation of this model
	 * 
	 * @return array
	 */
	function toArray() 
	{
		return (array) $this->attributes;
	}
	
	/**
	 * Get a simple string representation of this model
	 * 
	 * @return string
	 */
	function toString() {
		return $this->key();
	}
	
	/**
	 * Support the PHP automatic string casting
	 * 
	 * @return string
	 */
	function __toString() {
		return $this->toString();
	}
	
	/**
	 * Validate this model or arbitrary data that could be
	 * attributes of this model
	 * 
	 * @throws Invalid
	 * @return true
	 */
	function validate($data = null) 
	{
		// Allow user to supply arbitrary data to validate
		// If you don't like this, override it in your model.
		$data or $data = $this->toArray();
		
		// Run user validators
		foreach ($data as $var => $val) {
			if (method_exists($this, $validateMethod = 'validate' . ucfirst($var))) {
				$result = $this->$validateMethod($val);
				if ($result === false || ($result && is_string($result))) {
					throw new Invalid($result ?: 'Invalid');
				}
			}
		}
		
		// Valid unless invalid
		return true;
	}
	
}

?>
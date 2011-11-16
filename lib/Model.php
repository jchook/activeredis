<?

namespace ActiveRedis;

abstract class Model {
	
	protected $associated;
	protected $attributes;
	protected $isDirty;
	protected $isNew;
	
	protected static $primaryKey = 'id';
	protected static $keySeparator = ':';
	protected static $table;
	
	// Not yet supported
	protected static $index; // which properties to index
	
	// Callbacks
	protected static $callbacks;
	
	// Behaviors
	protected static $behaviors = array('DeepSave');
	
	// Associations
	protected static $associations;
	
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
			if (!isset($this->assicated[$var])) {
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
			// if ($association::$poly) {
			// 	$this->associated[$var][] = $val;
			// } else {
				$this->associated[$var] = $val;
			// }
			
			// Return value of __set is ignored by PHP
			return $val;
		}
		
		return $this->mergeAttribute($var, $val);
	}
	
	public static function bind($callbackName, $callback) {
		return static::table()->bind($callbackName, $callback);
	}
	
	public static function trigger($callbackName, $args = null) {
		if (is_null($args)) {
			$args = array(&$this);
		}
		return static::table()->trigger($callbackName, $args);
	}
	
	static function db() {
		return static::table()->db();
	}
	
	static function table() {
		if (!is_object(static::$table)) {
			Log::debug(get_called_class() . '::table() loading Table object');
			if (is_string(static::$table)) {
				static::$table = array('name' => static::$table);
			}
			static::$table = new Table(array_merge(array(
					'name' => static::$table ?: basename(strtr(get_called_class(), "\\", '/')),
					'model' => get_called_class(),
					'callbacks' => static::$callbacks ?: array(),
					'primaryKey' => static::$primaryKey ?: 'id',
					'associations' => static::$associations ?: array(),
				), (array) static::$table));
		}
		return static::$table;
	}
	
	static function create($config = null) {
		$model = new static($config);
		$model->save();
		return $model;
	}
	
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
	
	static function unserialize($data) {
		return new static(json_decode($data), false);
	}
	
	function serialize() {
		return json_encode($this->toArray());
	}
	
	public function associatedKeyExists($name) 
	{
		return isset($this->associated[$name]);
	}
	
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
	
	function association($name)
	{
		if ($association = $this->table()->association($name)) {
			return $association->delegate($this);
		}
	}
	
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
		$this->attributes = (array) $this->attributes;
		return $this->mergeAttribute($var, $var);
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
	
	function setAttribute($var, $val) {
		if (!isset($this->attributes[$var]) || ($this->attributes[$var] !== $val)) {
			$this->isDirty[$var] = true;
		}
		return $this->attributes[$var] = $val;
	}
	
	function getAttribute($var) {
		if (isset($this->attributes[$var])) {
			return $this->attributes[$var];
		}
		Log::notice('Undefined attribute ' . $var . ' in ' . $this);
	}
	
	static function foreignKey() {
		if (!static::$foreignKey) {
			static::$foreignKey = lcfirst(array_pop(explode('\\', get_called_class()))) . '_' . ($leftClass::$primaryKey ?: 'id');
		}
		return static::$foreignKey;
	}
	
	static function primaryKey() {
		return static::$primaryKey ?: 'id';
	}
	
	function primaryKeyValue($setValue = false) {
		if ($setValue === false) {
			return $this->getAttribute($this->primaryKey());
		} else {
			return $this->setAttribute($this->primaryKey(), $setValue);
		}
	}
	
	function id() {
		return $this->primaryKeyValue();
	}
	
	function key(/* polymorphic */) {
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
	
	function save($validate = true) {
		if ($validate) {
			$this->validate();
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
	
	protected function insert() {
		if ($success = $this->table()->insert($this)) {
			$this->isNew = false;
			$this->isDirty = false;
			return $success;
		}
	}
	
	protected function update() {
		if ($this->isNew()) {
			throw new Exception('Cannot update new record');
		}
		if ($success = $this->table()->update($this)) {
			$this->dirty = false;
		}
	}
	
	function toArray() 
	{
		return $this->attributes;
		
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
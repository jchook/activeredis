<?

namespace ActiveRedis;

abstract class Model {
	
	protected $attributes;
	protected $attribute; // reserved!
	protected $isDirty;
	protected $isNew;
	
	protected static $primaryKey = 'id';
	protected static $keySeparator = ':';
	protected static $table;
	
	// Callbacks
	protected static $callbacks;
	
	// Associations
	protected static $associations;
	
	// Not yet supported
	protected static $index; // which properties to index
	
	function __construct($id = null, $isNew = true) {
		
		$this->isNew = $isNew;
		
		if (is_array($id)) {
			$this->attributes = $id;
		}
		elseif ($id) {
			
		}
	}
	
	function __get($var) {
		if (method_exists($this, $method = 'get' . ucfirst($var))) {
			return $this->$method();
		}
		return $this->getAttribute($var);
	}
	
	function __set($var, $val) {
		if (method_exists($this, $method = 'set' . ucfirst($var))) {
			return $this->$method($val);
		}
		$this->setAttribute($var, $val);
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
	
	static function find($id) {
		
		/* Explode id:xyz into array(id, xyz);
		if (is_string($id) && strpos(static::$keySeparator, $id)) {
			$id = explode(static::$keySeparator, $id);
		}
		
		// Check for index
		if (is_array($id) && isset($id[1])) {
			if (!isset(static::$index[$id[0]])) {
				throw new Exception('No index defined for field: ' . $id[0]);
			}
		}
		*/
		
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
	
	function setAttribute($var, $val) {
		$this->isDirty[$var] = true;
		return $this->attributes[$var] = $val;
	}
	
	function getAttribute($var) {
		if (isset($this->attributes[$var])) {
			return $this->attributes[$var];
		}
	}
	
	static function primaryKey() {
		return static::$primaryKey;
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
	
	function toArray() {
		return (array) $this->attributes;
	}
	
	function toString() {
		return $this->__toString();
	}
	
	function __toString() {
		return $this->key();
	}
	
	function validate($data = null) {
		
		$data or $data = $this->toArray();
		
		foreach ($data as $var => $val) {
			if (method_exists($this, $validateMethod = 'validate' . ucfirst($var))) {
				$result = $this->$validateMethod($val);
				if ($result === false || is_string($result)) {
					throw new Invalid($result ?: 'Invalid');
				}
			}
		}
		
		return true;
	}
	
}

?>
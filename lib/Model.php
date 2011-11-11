<?

namespace ActiveRedis;

abstract class Model {
	
	protected $attributes;
	protected $dirty;
	protected $new;
	
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
		
		$this->new = $isNew;
		
		if (is_array($id)) {
			$this->attributes = $id;
		}
		elseif ($id) {
			
		}
	}
	
	function __get($var) {
		if (method_exists($this, $method = 'set' . ucfirst($var))) {
			return $this->$method($val);
		}
		return $this->getAttribute($var);
	}
	
	function __set($var, $val) {
		if (method_exists($this, $method = 'get' . ucfirst($var))) {
			return $this->$method();
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
					'name' => static::$table ?: strtolower(basename(strtr(get_called_class(), "\\", '/'))),
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
		$this->dirty[$var] = true;
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
		return $this->dirty;
	}
	
	function isNew() {
		return $this->new;
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
			$this->new = false;
			$this->dirty = false;
			$this->trigger('afterSave', array(&$this, $isNew, $result));
			return $result;
		}
		return true; // record is already up-to-date
	}
	
	protected function insert() {
		if ($success = $this->table()->insert($this)) {
			$this->isNew = false;
			$this->dirty = false;
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



/**
 * Associations
 */
abstract class Association 
{
	public $left;
	public $right;
	public $through;
	
	function __construct($leftClass, $rightClass, $options = null) 
	{	
		$this->left = $leftClass;
		$this->right = $rightClass;
		
		if (is_array($options)) {
			foreach ($options as $var => $val) {
				$this->$var = $val;
			}
		}
		
		// default "through"
		if (!$this->as) {
			$this->as = lcfirst(array_pop(explode("\\", $this->right)));
		}
	}
	
	function attach($table) {}
	function associate($left, $right) {}
}

abstract class HasManySorted extends HasMany {
	
	abstract function zscore($left);
	
	function associate($left, $right) {
		$left->db()->zadd($left->key($this->as), $this->zscore($left), $right->primaryKeyValue());
	}
}

class HasMany extends Association 
{
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function associatedModels($left) {
		$rightClass = $this->right;
		$left->db()->get($left->key($this->as));
	}
	
	function associate($left, $right) {
		$left->db()->sadd($left->get($this->as), $right->primaryKeyValue());
	}
	
	function beforeDelete($left) {
		$left->db()->srem($left->key($this->as), $left->primaryKeyValue());
	}
}

class BelongsTo extends Association 
{
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function beforeDelete($left) {
		$left->db()->del($left->key($this->as));
	}
}


/**
 * Exceptions
 */
class Exception extends \Exception {}

class AttributeNotFound extends Exception {}

class UserException extends Exception {}

class NotFound extends UserException {}
	
class Invalid extends UserException {}

?>
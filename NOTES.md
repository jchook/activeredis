# Notes

- Remove `setModel` and `getModel` from `Database`
- Make `Database` respond to actual Redis calls like `get()`, `set()`, `sadd()`
	- Perhaps use a ->query() method
	- Perhaps override __call() and forward to ->query()
	- Up to you...
- Tables are here to stay.
- Write tests for the rest of everything
- Reduce number of mocks needed by consolidating like functionality

- The idea of a storage engine... would affect all behaviors too


- Need to fix Redisent pr use something more robust for pagination of large sets
- Table::getModel() should change...
	- Should use a more generic Table::query($query) or something
	- Remember we want this to be generic and work for PDO etc in the future

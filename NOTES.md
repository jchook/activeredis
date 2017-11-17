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

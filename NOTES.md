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
	- This is handled by Tables?

- Indexes should store an encoded primary key, not the "DB key" string

- Need to fix Redisent pr use something more robust for pagination of large sets
- Table::getModel() should change...
	- Should use a more generic Table::query($query) or something
	- Remember we want this to be generic and work for PDO etc in the future


---

2017-12-20

- Table needs like.. addIndex() and removeIndex() indexModel() deindexModel()?
- Table needs to query properly using indexes
	- SINTER for unindexed multiple where conditions
	- SSCAN for indexed where conditions (single or multiple, should be the same)
	- ERROR if unindexed single where condition b/c naw
- Table indexes need to be array of keys
- Table encodeData should sort keys
- Table module should be dissolved
	- No need for "Table", maybe "Basic", but not even that. Flatten plz.
	- Advanced extensions of basic layer can have their own namespaces.
	- We will make interfaces for stuff like Model, and keep TableInterface.


- TABLE should emit events, not model.
- Also call them beforeSaveModel, etc not beforeSave
- Index multiple columns at once
-
